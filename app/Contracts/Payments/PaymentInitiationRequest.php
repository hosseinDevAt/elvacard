<?php

namespace App\Contracts\Payments;

/**
 * Trusted input handed by the Payment Core to a gateway.
 *
 * Every value originates from server-side order/payment data — never from the
 * browser. Specifically, the amount is the only authoritative pricing source.
 */
final readonly class PaymentInitiationRequest
{
    public function __construct(
        public int $orderId,
        public int $amount,
        public string $orderReference,
        public string $callbackUrl,
    ) {}
}
