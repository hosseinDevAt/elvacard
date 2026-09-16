<?php

namespace App\Services;

use App\Contracts\Payments\IdempotentRefundAwarePaymentGateway;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentRefundRequest;
use App\Contracts\Payments\RefundLookupAwarePaymentGateway;
use App\Contracts\Payments\RefundRetrieveRequest;
use App\Contracts\Payments\RefundRetrieveResult;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundLookupStatus;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Exceptions\RefundException;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Provider-agnostic refund orchestration for gateway payments.
 *
 * Reservation uses a payment-row lock to serialize concurrent refund requests.
 * The provider call is always made outside the database transaction. Completion
 * re-validates the raw aggregate under a payment lock before declaring
 * full refund.
 */
final class RefundCore
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly RefundConstraintService $constraints,
    ) {}

    /**
     * Process a new gateway refund for a payment.
     *
     * If an unresolved REVIEW refund exists, throws — use retryReviewRefund()
     * to resolve it first.
     *
     * @throws RefundConstraintViolationException
     */
    public function processRefund(Payment $payment, int $amount, ?string $reason = null): Refund
    {
        if ($payment->method !== PaymentMethod::GATEWAY) {
            throw new RefundException('بازگشت وجه درگاهی برای این روش پرداخت قابل استفاده نیست.');
        }

        $this->assertNoOpenReview($payment->id);

        $refund = $this->reserveRefund($payment, $amount, $reason);

        $gateway = $this->resolveGateway((string) $payment->gateway);

        if ($gateway === null) {
            return $this->markFailed($refund, [
                'reason' => 'unknown_gateway',
                'detail' => 'درگاه پرداخت یافت نشد.',
            ]);
        }

        return $this->runProviderAttempt($refund, $gateway);
    }

    /**
     * Retry / reconcile an existing REVIEW refund.
     *
     * 1. Provider lookup (if supported) → success → COMPLETED; failure → FAILED.
     * 2. Idempotent same-key retry (if supported and lookup unknown).
     * 3. Otherwise remain REVIEW and throw.
     *
     * Records the reconciliation attempt and, on terminal resolution, the
     * resolving admin and timestamp in the refund metadata.
     *
     * @throws RefundConstraintViolationException
     */
    public function retryReviewRefund(int $refundId): Refund
    {
        $refund = Refund::findOrFail($refundId);

        if ($refund->status !== RefundStatus::REVIEW) {
            throw new RefundConstraintViolationException('تنها بازگشت‌های در حال بررسی قابل بررسی مجدد هستند.');
        }

        $payment = Payment::findOrFail($refund->payment_id);

        $resolverId = auth()->id();
        $attemptMetadata = [
            'resolved_by' => $resolverId,
            'resolved_at' => now()->toIso8601String(),
        ];

        $this->bumpReconciliationAttempts($refund);
        $refund = $refund->fresh();

        Log::info('Refund reconciliation attempt initiated', [
            'refund_id' => $refund->id,
            'payment_id' => $refund->payment_id,
            'order_id' => $payment->order_id,
            'gateway' => (string) $payment->gateway,
            'amount' => (int) $refund->amount,
            'resolver_admin_id' => $resolverId,
            'reconciliation_attempts' => (int) ($refund->metadata['reconciliation_attempts'] ?? 1),
            'method' => 'admin_lookup_or_retry',
        ]);

        $gateway = $this->resolveGateway((string) $payment->gateway);

        if ($gateway === null) {
            throw new RefundConstraintViolationException('درگاه پرداخت یافت نشد.');
        }

        $lookup = $gateway instanceof RefundLookupAwarePaymentGateway
            ? $this->tryLookup($gateway, $payment, $refund)
            : null;

        if ($lookup !== null) {
            $resolved = match ($lookup->status) {
                RefundLookupStatus::SUCCESS => $this->completeRefund($refund, [
                    'provider_refund_id' => $lookup->providerRefundId,
                    'reconciled_via' => 'provider_lookup',
                    ...$attemptMetadata,
                ]),
                RefundLookupStatus::CONFIRMED_FAILURE => $this->markFailed($refund, [
                    'reason' => 'confirmed_by_lookup',
                    'detail' => $lookup->message,
                    ...$attemptMetadata,
                ]),
                default => $this->attemptIdempotentRetryOrRemainReview($refund, $gateway, $attemptMetadata),
            };
        } else {
            $resolved = $this->attemptIdempotentRetryOrRemainReview($refund, $gateway, $attemptMetadata);
        }

        $this->logReconciliationOutcome($refund, $resolved, $resolverId);

        return $resolved;
    }

    /**
     * Attempt a safe idempotent retry or leave the refund in REVIEW.
     *
     * @throws RefundConstraintViolationException
     */
    private function attemptIdempotentRetryOrRemainReview(Refund $refund, PaymentGateway $gateway, array $extraMetadata = []): Refund
    {
        if ($gateway instanceof IdempotentRefundAwarePaymentGateway && $gateway->supportsIdempotentRefundRetry()) {
            return $this->runProviderAttempt($refund, $gateway, $extraMetadata);
        }

        Log::warning('Refund remains under review: outcome unknown and safe retry unavailable', [
            'refund_id' => $refund->id,
            'payment_id' => $refund->payment_id,
        ]);

        throw new RefundConstraintViolationException('نتیجه بازگشت وجه هنوز مشخص نیست؛ لطفاً بعداً بررسی کنید.');
    }

    /**
     * @throws RefundConstraintViolationException
     */
    private function assertNoOpenReview(int $paymentId): void
    {
        $has = Refund::where('payment_id', $paymentId)
            ->where('status', RefundStatus::REVIEW->value)
            ->exists();

        if ($has) {
            throw new RefundConstraintViolationException('یک بازگشت در حال بررسی وجود دارد. لطفاً ابتدا نتیجه آن را مشخص کنید.');
        }
    }

    /**
     * Authoritative reservation: acquire the payment row lock, re-validate
     * constraints, and create a PENDING refund with a persisted idempotency key.
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
                'metadata' => [
                    'idempotency_key' => Str::uuid()->toString(),
                    'created_by' => auth()->id(),
                    'created_at' => now()->toIso8601String(),
                ],
            ]);
        });
    }

    /**
     * Invoke the provider gateway for the refund and handle the result.
     */
    private function runProviderAttempt(Refund $refund, PaymentGateway $gateway, array $extraMetadata = []): Refund
    {
        $payment = Payment::findOrFail($refund->payment_id);
        $key = $refund->metadata['idempotency_key'] ?? '';

        $request = new PaymentRefundRequest(
            orderId: $payment->order_id,
            amount: (int) $refund->amount,
            paymentTransactionId: (string) $payment->transaction_id,
            paymentProviderTransactionId: $payment->metadata['provider_transaction_id'] ?? null,
            idempotencyKey: $key,
        );

        try {
            $result = $gateway->refund($request);
        } catch (\Throwable $e) {
            $this->markReview($refund, [
                'reason' => 'provider_unknown',
                'detail' => $e->getMessage(),
            ]);

            Log::error('Gateway provider exception during refund (outcome unknown)', [
                'refund_id' => $refund->id,
                'payment_id' => $refund->payment_id,
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
                ...$extraMetadata,
            ]);

            Log::warning('Gateway refund failed (confirmed)', [
                'refund_id' => $refund->id,
                'payment_id' => $refund->payment_id,
                'order_id' => $payment->order_id,
                'gateway' => $gateway->name(),
            ]);

            return $refund->fresh();
        }

        return $this->completeRefund($refund, [
            'provider_refund_id' => $result->providerRefundId,
            ...$extraMetadata,
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
    private function completeRefund(Refund $refund, array $metadata): Refund
    {
        DB::transaction(function () use ($refund, $metadata) {
            $payment = $this->lockPayment($refund->payment_id);
            $locked = $this->lockRefund($refund->id);

            if (! in_array($locked->status, [RefundStatus::PENDING, RefundStatus::REVIEW], true)) {
                return;
            }

            $reservedExcludingSelf = Refund::where('payment_id', $payment->id)
                ->where('id', '!=', $locked->id)
                ->whereIn('status', [
                    RefundStatus::PENDING->value,
                    RefundStatus::REVIEW->value,
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

        return $refund->fresh();
    }

    private function markFailed(Refund $refund, array $reasonMetadata): Refund
    {
        DB::transaction(function () use ($refund, $reasonMetadata) {
            $locked = $this->lockRefund($refund->id);

            if (! in_array($locked->status, [RefundStatus::PENDING, RefundStatus::REVIEW], true)) {
                return;
            }

            $locked->status = RefundStatus::FAILED;
            $locked->metadata = array_merge($locked->metadata ?? [], $reasonMetadata);
            $locked->save();
        });

        return $refund->fresh();
    }

    private function markReview(Refund $refund, array $reasonMetadata): Refund
    {
        DB::transaction(function () use ($refund, $reasonMetadata) {
            $locked = $this->lockRefund($refund->id);

            if (! in_array($locked->status, [RefundStatus::PENDING, RefundStatus::REVIEW], true)) {
                return;
            }

            $locked->status = RefundStatus::REVIEW;
            $locked->metadata = array_merge($locked->metadata ?? [], $reasonMetadata);
            $locked->save();
        });

        return $refund->fresh();
    }

    private function bumpReconciliationAttempts(Refund $refund): void
    {
        DB::transaction(function () use ($refund) {
            $locked = $this->lockRefund($refund->id);

            if ($locked->status !== RefundStatus::REVIEW) {
                return;
            }

            $attempts = (int) ($locked->metadata['reconciliation_attempts'] ?? 0);

            $locked->metadata = array_merge($locked->metadata ?? [], [
                'reconciliation_attempts' => $attempts + 1,
            ]);
            $locked->save();
        });
    }

    private function logReconciliationOutcome(Refund $refund, Refund $resolved, int|string|null $resolverId): void
    {
        Log::info('Refund reconciliation resolved', [
            'refund_id' => $refund->id,
            'payment_id' => $refund->payment_id,
            'status' => $resolved->status->value,
            'resolver_admin_id' => $resolverId,
            'reconciled_via' => $resolved->metadata['reconciled_via'] ?? null,
            'reconciliation_attempts' => (int) ($resolved->metadata['reconciliation_attempts'] ?? 1),
        ]);
    }

    private function tryLookup(
        RefundLookupAwarePaymentGateway $gateway,
        Payment $payment,
        Refund $refund,
    ): ?RefundRetrieveResult {
        $key = $refund->metadata['idempotency_key'] ?? '';

        try {
            return $gateway->retrieveRefund(new RefundRetrieveRequest(
                orderId: $payment->order_id,
                idempotencyKey: $key,
                paymentTransactionId: (string) $payment->transaction_id,
                paymentProviderTransactionId: $payment->metadata['provider_transaction_id'] ?? null,
            ));
        } catch (\Throwable $e) {
            Log::warning('Refund provider lookup failed', [
                'refund_id' => $refund->id,
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
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
