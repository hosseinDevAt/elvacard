<?php

namespace App\Services;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentVerificationResult;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\UnknownPaymentGatewayException;
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
        DB::transaction(function () use ($payment, $verification) {
            $locked = $this->lockPayment($payment->id);

            if ($locked->status !== PaymentStatus::PENDING) {
                return;
            }

            $locked->status = PaymentStatus::SUCCESS;
            $locked->paid_amount = $locked->amount;
            $locked->paid_at = now();
            $locked->metadata = array_merge($locked->metadata ?? [], [
                'provider_transaction_id' => $verification->providerTransactionId ?? $verification->providerReference,
                'verified_amount' => (int) $verification->amount,
            ]);
            $locked->save();

            $order = $locked->order;
            $order->payment_status = PaymentStatusEnum::PAID;
            $order->save();

            if ($this->stateMachine->canTransition($order, OrderStatusEnum::CONFIRMED)) {
                $this->stateMachine->transition($order, OrderStatusEnum::CONFIRMED);
            }
        });

        return PaymentStatus::SUCCESS;
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
}
