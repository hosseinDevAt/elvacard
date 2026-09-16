<?php

namespace Tests\Support;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Contracts\Payments\PaymentRefundRequest;
use App\Contracts\Payments\PaymentRefundResult;
use App\Contracts\Payments\PaymentVerificationResult;

/**
 * Minimal test-only gateway that implements only the base PaymentGateway
 * contract — no RefundLookupAwarePaymentGateway, no IdempotentRefundAwarePaymentGateway.
 *
 * Used to verify behavior when a provider does not support refund retrieval
 * or idempotent retry (e.g. remain REVIEW with no blind re-invocation).
 */
final class BasicFakePaymentGateway implements PaymentGateway
{
    public bool $failOnRefund = false;

    public bool $timeoutOnRefund = false;

    public int $refundCalls = 0;

    public array $observedRefundIdempotencyKeys = [];

    public function __construct(private readonly string $providerName = 'basic-fake') {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        return PaymentInitiationResult::success(
            redirectUrl: 'https://redir.example.test/pay/'.$request->orderReference,
            providerReference: 'BREF-'.$request->orderReference,
            metadata: ['provider' => $this->providerName],
        );
    }

    public function verify(PaymentInitiationRequest $request, string $providerReference, array $callbackData): PaymentVerificationResult
    {
        return PaymentVerificationResult::success(
            amount: $request->amount,
            providerReference: $providerReference,
            providerTransactionId: 'BTXN-'.$providerReference,
            metadata: ['provider' => $this->providerName],
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        $this->refundCalls++;
        $this->observedRefundIdempotencyKeys[] = $request->idempotencyKey;

        if ($this->timeoutOnRefund) {
            throw new \RuntimeException("Provider timeout for {$this->providerName}.");
        }

        if ($this->failOnRefund) {
            return PaymentRefundResult::failure('Refund failed.', [
                'provider' => $this->providerName,
            ]);
        }

        return PaymentRefundResult::success(
            providerRefundId: 'BREF-'.$request->paymentTransactionId,
            metadata: ['provider' => $this->providerName],
        );
    }
}
