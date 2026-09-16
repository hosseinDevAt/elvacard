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
 * Mirrors GatewayPaymentCore: the provider refund call is always made outside
 * the database transaction so a slow or failing provider cannot hold a
 * database lock. The refund only becomes COMPLETED after a successful
 * provider call AND a successful transactional state update.
 */
final class RefundCore
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly RefundConstraintService $constraints,
        private readonly OrderStateMachine $stateMachine,
    ) {}

    /**
     * Process a refund for a gateway payment.
     *
     * 1. Validate refundability inside a locked transaction
     * 2. Create PENDING refund record
     * 3. Call provider outside the transaction
     * 4. Mark COMPLETED or FAILED inside a second transaction
     *
     * @throws RefundConstraintViolationException
     */
    public function processRefund(Payment $payment, int $amount, ?string $reason = null): Refund
    {
        if ($payment->method !== PaymentMethod::GATEWAY) {
            throw new RefundException('بازگشت وجه درگاهی برای این روش پرداخت قابل استفاده نیست.');
        }

        $this->constraints->assertRefundable($payment, $amount);

        $refund = $this->createPendingRefund($payment, $amount, $reason);

        $gateway = $this->resolveGateway((string) $payment->gateway);

        if ($gateway === null) {
            $this->markFailed($refund, [
                'reason' => 'unknown_gateway',
                'detail' => 'درگاه پرداخت یافت نشد.',
            ]);

            return $refund->fresh();
        }

        $request = new PaymentRefundRequest(
            orderId: $payment->order_id,
            amount: $amount,
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

            return $refund->fresh();
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

            return $refund->fresh();
        }

        $this->completeRefund($refund, [
            'provider_refund_id' => $result->providerRefundId,
        ]);

        Log::info('Gateway refund completed', [
            'refund_id' => $refund->id,
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $amount,
            'admin_id' => auth()->id(),
        ]);

        return $refund->fresh();
    }

    private function createPendingRefund(Payment $payment, int $amount, ?string $reason): Refund
    {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            return Refund::create([
                'payment_id' => $payment->id,
                'amount' => $amount,
                'status' => RefundStatus::PENDING,
                'reason' => $reason,
                'metadata' => [],
            ]);
        });
    }

    private function completeRefund(Refund $refund, array $metadata): void
    {
        DB::transaction(function () use ($refund, $metadata) {
            $locked = $this->lockRefund($refund->id);

            if ($locked->status !== RefundStatus::PENDING) {
                return;
            }

            $locked->status = RefundStatus::COMPLETED;
            $locked->refunded_at = now();
            $locked->metadata = array_merge($locked->metadata ?? [], $metadata);
            $locked->save();

            $payment = $this->lockPayment($locked->payment_id);
            $order = $this->lockOrder($payment->order_id);

            if ($this->constraints->isFullyRefunded($payment)) {
                $order->payment_status = PaymentStatusEnum::REFUNDED;
                $order->save();
            }
        });
    }

    private function markFailed(Refund $refund, array $reasonMetadata): void
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
