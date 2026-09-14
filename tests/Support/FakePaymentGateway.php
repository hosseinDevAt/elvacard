<?php

namespace Tests\Support;

use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentInitiationResult;
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

    public ?int $verificationAmountOverride = null;

    public int $initiateCalls = 0;

    public int $verifyCalls = 0;

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
}
