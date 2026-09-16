<?php

namespace App\Contracts\Payments;

/**
 * Provider-agnostic refund request.
 *
 * The amount and order context are provided by the Refund Core through
 * $request and must never be read from the browser/request by an
 * implementation.
 */
final readonly class PaymentRefundRequest
{
    public function __construct(
        public int $orderId,
        public int $amount,
        public string $paymentTransactionId,
        public ?string $paymentProviderTransactionId = null,
    ) {}
}
