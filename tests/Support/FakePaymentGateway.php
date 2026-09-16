<?php

namespace Tests\Support;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Contracts\Payments\PaymentRefundRequest;
use App\Contracts\Payments\PaymentRefundResult;
use App\Contracts\Payments\PaymentVerificationResult;

/**
 * Test-only fake used to prove the PaymentGateway contract is implementable
 * and that the Payment Core stays provider-agnostic.
 *
 * This fake is never registered in production configuration, the resolver, or
 * any user-facing UI (see PaymentGatewayAbstractionTest).
 */
final class FakePaymentGateway implements PaymentGateway
{
    public bool $failOnInitiate = false;

    public bool $failOnVerify = false;

    public bool $throwOnInitiate = false;

    public bool $throwOnVerify = false;

    public ?int $verificationAmountOverride = null;

    public bool $failOnRefund = false;

    public bool $throwOnRefund = false;

    public int $initiateCalls = 0;

    public int $verifyCalls = 0;

    public int $refundCalls = 0;

    public ?PaymentRefundRequest $lastRefundRequest = null;

    public ?PaymentInitiationRequest $lastInitiationRequest = null;

    public ?string $lastProviderReference = null;

    /** @var array<string, mixed> */
    public array $lastCallbackData = [];

    public function __construct(private readonly string $providerName = 'fake') {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult
    {
        $this->initiateCalls++;
        $this->lastInitiationRequest = $request;

        if ($this->throwOnInitiate) {
            throw new \RuntimeException("Provider unreachable for {$this->providerName}.");
        }

        if ($this->failOnInitiate) {
            return PaymentInitiationResult::failure('امکان شروع پرداخت وجود ندارد.', [
                'provider' => $this->providerName,
            ]);
        }

        return PaymentInitiationResult::success(
            redirectUrl: "https://redir.example.test/pay/{$request->orderReference}",
            providerReference: "REF-{$request->orderReference}-{$this->initiateCalls}",
            metadata: ['provider' => $this->providerName],
        );
    }

    public function verify(PaymentInitiationRequest $request, string $providerReference, array $callbackData): PaymentVerificationResult
    {
        $this->verifyCalls++;
        $this->lastProviderReference = $providerReference;
        $this->lastCallbackData = $callbackData;

        if ($this->throwOnVerify) {
            throw new \RuntimeException("Provider unreachable during verification for {$this->providerName}.");
        }

        if ($this->failOnVerify) {
            return PaymentVerificationResult::failure('تأیید پرداخت ناموفق بود.', [
                'provider' => $this->providerName,
            ]);
        }

        return PaymentVerificationResult::success(
            amount: $this->verificationAmountOverride ?? $request->amount,
            providerReference: $providerReference,
            providerTransactionId: "TXN-{$providerReference}",
            metadata: ['raw_status' => $callbackData['status'] ?? 'OK'],
        );
    }

    public function refund(PaymentRefundRequest $request): PaymentRefundResult
    {
        $this->refundCalls++;
        $this->lastRefundRequest = $request;

        if ($this->throwOnRefund) {
            throw new \RuntimeException("Provider unreachable during refund for {$this->providerName}.");
        }

        if ($this->failOnRefund) {
            return PaymentRefundResult::failure('بازگشت وجه ناموفق بود.', [
                'provider' => $this->providerName,
            ]);
        }

        return PaymentRefundResult::success(
            providerRefundId: "RFN-{$request->paymentTransactionId}",
            metadata: ['provider' => $this->providerName],
        );
    }
}
