<?php

namespace App\Contracts\Payments;

use App\Enums\RefundLookupStatus;

final readonly class RefundRetrieveResult
{
    /**
     * @param  array<string, mixed>  $metadata  Provider-specific diagnostic data.
     */
    public function __construct(
        public RefundLookupStatus $status,
        public ?string $providerRefundId = null,
        public ?string $message = null,
        public array $metadata = [],
    ) {}

    public static function success(
        ?string $providerRefundId = null,
        array $metadata = [],
    ): self {
        return new self(
            status: RefundLookupStatus::SUCCESS,
            providerRefundId: $providerRefundId,
            metadata: $metadata,
        );
    }

    public static function confirmedFailure(
        string $message,
        array $metadata = [],
    ): self {
        return new self(
            status: RefundLookupStatus::CONFIRMED_FAILURE,
            message: $message,
            metadata: $metadata,
        );
    }

    public static function unknown(
        ?string $message = null,
        array $metadata = [],
    ): self {
        return new self(
            status: RefundLookupStatus::UNKNOWN,
            message: $message,
            metadata: $metadata,
        );
    }
}
