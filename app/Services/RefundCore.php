<?php

namespace App\Services;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentRefundRequest;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Exceptions\RefundException;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Provider-agnostic refund orchestration for gateway payments.
 *
 * Reservation acquires the payment row lock BEFORE validation and row
 * creation, so concurrent refund requests are serialized at the database
 * level. The provider call is made outside the database transaction.
 * Completion re-validates the raw aggregate under a payment lock before
 * declaring full refund.
 */
final class RefundCore
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly RefundConstraintService $constraints,
    ) {}

    /**
     * Process a refund for a gateway payment.
     *
     * @throws RefundConstraintViolationException
     */
    public function processRefund(Payment $payment, int $amount, ?string $reason = null): Refund
    {
        if ($payment->method !== PaymentMethod::GATEWAY) {
            throw new RefundException('بازگشت وجه درگاهی برای این روش پرداخت قابل استفاده نیست.');
        }

        $refund = $this->reserveRefund($payment, $amount, $reason);

        $gateway = $this->resolveGateway((string) $payment->gateway);

        if ($gateway === null) {
            return $this->markFailed($refund, [
                'reason' => 'unknown_gateway',
                'detail' => 'درگاه پرداخت یافت نشد.',
            ]);
        }

        $this->runProviderAttempt($refund, $gateway);

        return $refund->fresh();
    }

    /**
     * Authoritative reservation: acquire the payment row lock, re-validate
     * constraints, and create a PENDING refund inside the same transaction.
     */
    private function reserveRefund(Payment $payment, int $amount, ?string $reason): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            $locked = $this->lockPayment($payment->id);

            $this->constraints->assertSuccessfulPayment($locked);
            $this->constraints->assertPositiveAmount($amount);
            $this->constraints->assertAmountWithinRefundable($locked, $amount);
            $this->constraints->assertNoInFlightRefund($locked);

            return Refund::create([
                'payment_id' => $locked->id,
                'amount' => $amount,
                'status' => RefundStatus::PENDING,
                'reason' => $reason,
                'metadata' => [],
            ]);
        });
    }

    /**
     * Invoke the provider gateway for the refund and handle the result.
     */
    private function runProviderAttempt(Refund $refund, PaymentGateway $gateway): void
    {
        $payment = Payment::findOrFail($refund->payment_id);

        $request = new PaymentRefundRequest(
            orderId: $payment->order_id,
            amount: (int) $refund->amount,
            paymentTransactionId: (string) $payment->transaction_id,
            paymentProviderTransactionId: $payment->metadata['provider_transaction_id'] ?? null,
        );

        try {
            $result = $gateway->refund($request);
        } catch (\Throwable $e) {
            $this->markFailed($refund, [
                'reason' => 'provider_exception',
                'detail' => $e->getMessage(),
            ]);

            Log::error('Gateway provider exception during refund', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $gateway->name(),
                'exception' => $e->getMessage(),
            ]);

            return;
        }

        if (! $result->success) {
            $this->markFailed($refund, [
                'reason' => 'provider_refund_failed',
                'detail' => $result->message,
            ]);

            Log::warning('Gateway refund failed', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id,
                'gateway' => $gateway->name(),
            ]);

            return;
        }

        $this->completeRefund($refund, [
            'provider_refund_id' => $result->providerRefundId,
        ]);
    }

    /**
     * Complete a refund reservation under a payment lock with a raw aggregate
     * integrity re-check.
     *
     * If the raw balance is negative after excluding this refund, the provider
     * may have already moved money — the refund is marked COMPLETED with an
     * integrity_violation flag but the order is NOT marked REFUNDED.
     */
    private function completeRefund(Refund $refund, array $metadata): void
    {
        DB::transaction(function () use ($refund, $metadata) {
            $payment = $this->lockPayment($refund->payment_id);
            $locked = $this->lockRefund($refund->id);

            if ($locked->status !== RefundStatus::PENDING) {
                return;
            }

            $reservedExcludingSelf = Refund::where('payment_id', $payment->id)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', [
                    RefundStatus::PENDING->value,
                    RefundStatus::COMPLETED->value,
                ])
                ->sum('amount');

            $rawBalance = (int) $payment->paid_amount - (int) $reservedExcludingSelf - (int) $locked->amount;

            if ($rawBalance < 0) {
                $locked->status = RefundStatus::COMPLETED;
                $locked->refunded_at = now();
                $locked->metadata = array_merge($locked->metadata ?? [], $metadata, [
                    'integrity_violation' => true,
                    'integrity_reason' => 'completed_refund_exceeds_received_amount',
                    'raw_balance_after' => $rawBalance,
                ]);
                $locked->save();

                Log::critical('Gateway refund COMPLETED despite negative raw balance; provider may have moved money', [
                    'refund_id' => $locked->id,
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => (int) $locked->amount,
                    'raw_balance' => $rawBalance,
                ]);

                return;
            }

            $locked->status = RefundStatus::COMPLETED;
            $locked->refunded_at = now();
            $locked->metadata = array_merge($locked->metadata ?? [], $metadata);
            $locked->save();

            if ($this->constraints->isFullyRefunded($payment)) {
                $order = $this->lockOrder($payment->order_id);
                $order->payment_status = PaymentStatusEnum::REFUNDED;
                $order->save();
            }
        });
    }

    private function markFailed(Refund $refund, array $reasonMetadata): Refund
    {
        DB::transaction(function () use ($refund, $reasonMetadata) {
            $locked = $this->lockRefund($refund->id);

            if ($locked->status !== RefundStatus::PENDING) {
                return;
            }

            $locked->status = RefundStatus::FAILED;
            $locked->metadata = array_merge($locked->metadata ?? [], $reasonMetadata);
            $locked->save();
        });

        return $refund->fresh();
    }

    private function resolveGateway(string $gatewayName): ?PaymentGateway
    {
        try {
            return $this->gatewayManager->resolve($gatewayName);
        } catch (UnknownPaymentGatewayException) {
            return null;
        }
    }

    private function lockRefund(int $refundId): Refund
    {
        $query = Refund::query()->where('id', $refundId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new \RuntimeException('Refund not found.');
    }

    private function lockPayment(int $paymentId): Payment
    {
        $query = Payment::query()->where('id', $paymentId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new \RuntimeException('Payment not found.');
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new \RuntimeException('Order not found.');
    }
}
