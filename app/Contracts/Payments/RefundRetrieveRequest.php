<?php

namespace App\Contracts\Payments;

final readonly class RefundRetrieveRequest
{
    public function __construct(
        public int $orderId,
        public string $idempotencyKey,
        public string $paymentTransactionId,
        public ?string $paymentProviderTransactionId = null,
    ) {}
}
