<?php

namespace App\Contracts\Payments;

/**
 * Normalized outcome of a gateway refund() call.
 *
 * Provider-side identifiers are translated into provider-agnostic fields so
 * the Refund Core stores/uses them without knowing a specific provider.
 */
final readonly class PaymentRefundResult
{
    /**
     * @param  array<string, mixed>  $metadata  Provider-specific diagnostic data.
     *                                          Must never contain secrets/credentials.
     */
    public function __construct(
        public bool $success,
        public ?string $providerRefundId = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public static function success(
        ?string $providerRefundId = null,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            providerRefundId: $providerRefundId,
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
