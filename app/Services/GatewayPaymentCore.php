<?php

namespace App\Services;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentVerificationResult;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Provider-agnostic gateway callback orchestration.
 *
 * The callback is never treated as proof of payment: the payment only becomes
 * SUCCESS after a successful server-side verification AND an exact amount
 * match against the authoritative Payment.amount. Repeated/racing callbacks
 * are made idempotent by a row lock plus a terminal-status guard.
 */
final class GatewayPaymentCore
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly OrderStateMachine $stateMachine,
        private readonly PaymentConstraintService $constraints,
    ) {}

    public function handleCallback(Payment $payment, array $callbackData): PaymentStatus
    {
        if ($this->isTerminal($payment->status)) {
            return $payment->status;
        }

        $gateway = $this->resolveGateway((string) $payment->gateway);

        if ($gateway === null) {
            return $payment->status;
        }

        $order = $payment->order;

        $request = new PaymentInitiationRequest(
            orderId: $order->id,
            amount: (int) $payment->amount,
            orderReference: $order->reference,
            callbackUrl: route('checkout.payment.callback', $payment->gateway),
        );

        $verification = $gateway->verify(
            $request,
            (string) $payment->transaction_id,
            $callbackData,
        );

        if (! $verification->success) {
            return $this->fail($payment, ['reason' => 'provider_verification_failed']);
        }

        if ((int) $verification->amount !== (int) $payment->amount) {
            return $this->fail($payment, [
                'reason' => 'amount_mismatch',
                'expected_amount' => (int) $payment->amount,
                'returned_amount' => (int) $verification->amount,
            ]);
        }

        return $this->succeed($payment, $verification);
    }

    private function succeed(Payment $payment, PaymentVerificationResult $verification): PaymentStatus
    {
        return DB::transaction(function () use ($payment, $verification): PaymentStatus {
            $locked = $this->lockPayment($payment->id);

            if ($locked->status !== PaymentStatus::PENDING) {
                return $locked->status;
            }

            $order = $this->lockOrder($locked->order_id);

            // The order might have been cancelled or completed while the payment
            // was pending at the provider, or another payment may have already
            // settled it. CANCELLED + PAID / COMPLETED + PAID / double-success
            // are forbidden states, so the success transition is rejected and
            // the attempt is marked failed instead.
            try {
                $this->constraints->assertPayable($order);
                $this->constraints->assertSingleSuccessfulPayment($order);
            } catch (PaymentConstraintViolationException $e) {
                $this->reject($locked, [
                    'reason' => 'order_not_payable',
                    'detail' => $e->getMessage(),
                ]);

                return PaymentStatus::FAILED;
            }

            $locked->status = PaymentStatus::SUCCESS;
            $locked->paid_amount = $locked->amount;
            $locked->paid_at = now();
            $locked->metadata = array_merge($locked->metadata ?? [], [
                'provider_transaction_id' => $verification->providerTransactionId ?? $verification->providerReference,
                'verified_amount' => (int) $verification->amount,
            ]);
            $locked->save();

            $order->payment_status = PaymentStatusEnum::PAID;
            $order->save();

            if ($this->stateMachine->canTransition($order, OrderStatusEnum::CONFIRMED)) {
                $this->stateMachine->transition($order, OrderStatusEnum::CONFIRMED);
            }

            return PaymentStatus::SUCCESS;
        });
    }

    private function reject(Payment $payment, array $reasonMetadata): void
    {
        $payment->status = PaymentStatus::FAILED;
        $payment->metadata = array_merge($payment->metadata ?? [], $reasonMetadata);
        $payment->save();
    }

    private function fail(Payment $payment, array $reasonMetadata): PaymentStatus
    {
        DB::transaction(function () use ($payment, $reasonMetadata) {
            $locked = $this->lockPayment($payment->id);

            if ($locked->status !== PaymentStatus::PENDING) {
                return;
            }

            $locked->status = PaymentStatus::FAILED;
            $locked->metadata = array_merge($locked->metadata ?? [], $reasonMetadata);
            $locked->save();
        });

        return PaymentStatus::FAILED;
    }

    private function resolveGateway(string $gatewayName): ?PaymentGateway
    {
        try {
            return $this->gatewayManager->resolve($gatewayName);
        } catch (UnknownPaymentGatewayException) {
            return null;
        }
    }

    private function isTerminal(PaymentStatus $status): bool
    {
        return in_array($status, [
            PaymentStatus::SUCCESS,
            PaymentStatus::FAILED,
            PaymentStatus::CANCELLED,
        ], true);
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
