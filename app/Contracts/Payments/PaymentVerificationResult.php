<?php

namespace App\Contracts\Payments;

/**
 * Normalized outcome of a gateway verify() call.
 *
 * Provider-side identifiers are translated into provider-agnostic fields so
 * the Payment Core stores/uses them without knowing a specific provider.
 */
final readonly class PaymentVerificationResult
{
    /**
     * @param  array<string, mixed>  $metadata  Provider-specific diagnostic data.
     *                                          Must never contain secrets/credentials.
     */
    public function __construct(
        public bool $success,
        public ?string $providerTransactionId = null,
        public ?string $providerReference = null,
        public ?int $amount = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public static function success(
        int $amount,
        string $providerReference,
        ?string $providerTransactionId = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            providerTransactionId: $providerTransactionId,
            providerReference: $providerReference,
            amount: $amount,
            metadata: $metadata,
        );
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            message: $message,
            metadata: $metadata,
        );
    }
}
