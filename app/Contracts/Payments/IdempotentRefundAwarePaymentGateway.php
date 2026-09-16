<?php

namespace App\Contracts\Payments;

interface IdempotentRefundAwarePaymentGateway extends PaymentGateway
{
    /**
     * Whether invoking refund() again with the exact same idempotencyKey is
     * guaranteed safe by the provider (deduplicated, no second real-world movement).
     */
    public function supportsIdempotentRefundRetry(): bool;
}
