<?php

namespace App\Contracts\Payments;

/**
 * Normalized outcome of a gateway initiate() call.
 *
 * Redirect information and failure reasons are exposed through this shape so
 * the Payment Core never depends on provider-specific response formats.
 */
final readonly class PaymentInitiationResult
{
    /**
     * @param  array<string, mixed>  $metadata  Provider-specific diagnostic data.
     *                                          Must never contain secrets/credentials.
     */
    public function __construct(
        public bool $success,
        public ?string $redirectUrl = null,
        public ?string $providerReference = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public static function success(string $redirectUrl, string $providerReference, array $metadata = []): self
    {
        return new self(
            success: true,
            redirectUrl: $redirectUrl,
            providerReference: $providerReference,
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
