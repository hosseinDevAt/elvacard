<?php

namespace Tests\Support;

use App\Contracts\Payments\IdempotentRefundAwarePaymentGateway;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Payments\PaymentInitiationRequest;
use App\Contracts\Payments\PaymentInitiationResult;
use App\Contracts\Payments\PaymentRefundRequest;
use App\Contracts\Payments\PaymentRefundResult;
use App\Contracts\Payments\PaymentVerificationResult;
use App\Contracts\Payments\RefundLookupAwarePaymentGateway;
use App\Contracts\Payments\RefundRetrieveRequest;
use App\Contracts\Payments\RefundRetrieveResult;

/**
 * Test-only fake used to prove the PaymentGateway contract is implementable
 * and that the Payment Core stays provider-agnostic.
 *
 * Supports provider-side idempotency deduplication and refund lookup for
 * reconciliation tests. The ledger models a real provider's behavior:
 * the first attempt with a given idempotency key is "processed"; repeated
 * attempts with the same key are deduped.
 */
final class FakePaymentGateway implements IdempotentRefundAwarePaymentGateway, PaymentGateway, RefundLookupAwarePaymentGateway
{
    public bool $failOnInitiate = false;

    public bool $failOnVerify = false;

    public bool $throwOnInitiate = false;

    public bool $throwOnVerify = false;

    public ?int $verificationAmountOverride = null;

    public bool $failOnRefund = false;

    public bool $timeoutOnRefund = false;

    public int $initiateCalls = 0;

    public int $verifyCalls = 0;

    public int $refundCalls = 0;

    public int $processedRefunds = 0;

    public int $lookupCalls = 0;

    public ?PaymentRefundRequest $lastRefundRequest = null;

    public ?PaymentInitiationRequest $lastInitiationRequest = null;

    public ?string $lastProviderReference = null;

    /** @var array<string, mixed> */
    public array $lastCallbackData = [];

    public ?RefundRetrieveResult $lookupResult = null;

    public bool $throwOnLookup = false;

    public ?RefundRetrieveRequest $lastRetrieveRequest = null;

    public bool $supportsIdempotentRetry = true;

    /** @var string[] All idempotency keys observed across refund() calls. */
    public array $observedRefundIdempotencyKeys = [];

    public bool $lastRefundWasDedupe = false;

    /**
     * Provider-side ledger keyed by idempotency key.
     *
     * The first attempt for a key writes the result here (models the
     * provider's own record). Subsequent same-key attempts return the
     * stored result (deduplication) without a new real-world movement.
     *
     * @var array<string, PaymentRefundResult>
     */
    public array $ledger = [];

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
        $this->lastRefundWasDedupe = false;
        $this->observedRefundIdempotencyKeys[] = $request->idempotencyKey;

        $key = $request->idempotencyKey;

        // Provider-side idempotent deduplication.
        if (isset($this->ledger[$key])) {
            if (! $this->supportsIdempotentRetry) {
                throw new \RuntimeException("Provider refuses duplicate attempt for key [{$key}].");
            }

            $this->lastRefundWasDedupe = true;

            return $this->ledger[$key];
        }

        // Simulate timeout / connection failure after the request may have
        // reached the provider.  The provider stores its own record; on a
        // subsequent same-key call the ledger returns the eventual outcome.
        if ($this->timeoutOnRefund) {
            $this->processedRefunds++;

            $this->ledger[$key] = PaymentRefundResult::success(
                providerRefundId: 'RFN-IDEM-'.$key,
                metadata: ['provider' => $this->providerName, 'eventual_outcome' => true],
            );

            throw new \RuntimeException("Provider unreachable during refund for {$this->providerName}.");
        }

        // Confirmed rejection: provider explicitly states the refund did NOT happen.
        if ($this->failOnRefund) {
            $result = PaymentRefundResult::failure('بازگشت وجه ناموفق بود.', [
                'provider' => $this->providerName,
            ]);

            $this->ledger[$key] = $result;

            return $result;
        }

        // Confirmed success.
        $this->processedRefunds++;

        $result = PaymentRefundResult::success(
            providerRefundId: 'RFN-'.$request->paymentTransactionId,
            metadata: ['provider' => $this->providerName],
        );

        $this->ledger[$key] = $result;

        return $result;
    }

    public function retrieveRefund(RefundRetrieveRequest $request): RefundRetrieveResult
    {
        $this->lookupCalls++;
        $this->lastRetrieveRequest = $request;

        if ($this->throwOnLookup) {
            throw new \RuntimeException("Provider lookup unreachable for {$this->providerName}.");
        }

        return $this->lookupResult
            ?? RefundRetrieveResult::unknown(
                'Provider lookup not configured for this test.',
                ['provider' => $this->providerName],
            );
    }

    public function supportsIdempotentRefundRetry(): bool
    {
        return $this->supportsIdempotentRetry;
    }
}
