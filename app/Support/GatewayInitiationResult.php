<?php

namespace App\Support;

use App\Models\Payment;

/**
 * Safe application-level outcome of a gateway initiation attempt.
 *
 * Failures (empty registry, unknown gateway, provider failure, active payment
 * guard) are normalized into `unavailable()` so the checkout boundary never
 * surfaces provider internals or throws a 500 because a provider is missing.
 */
final readonly class GatewayInitiationResult
{
    public function __construct(
        public bool $success,
        public ?Payment $payment = null,
        public ?string $redirectUrl = null,
        public ?string $message = null,
    ) {}

    public static function success(Payment $payment, string $redirectUrl): self
    {
        return new self(
            success: true,
            payment: $payment,
            redirectUrl: $redirectUrl,
        );
    }

    public static function unavailable(string $message): self
    {
        return new self(
            success: false,
            message: $message,
        );
    }
}
