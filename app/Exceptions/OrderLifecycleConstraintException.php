<?php

namespace App\Exceptions;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use RuntimeException;

/**
 * Thrown when an order status transition would violate a payment-aware
 * lifecycle rule (e.g. cancelling a paid order, completing an unpaid order).
 *
 * NOTE: paid-order cancellation requires a refund process that does not
 * exist yet. This transition stays blocked until an explicit refund lifecycle
 * (using PaymentStatusEnum::REFUNDED) is implemented.
 */
class OrderLifecycleConstraintException extends RuntimeException
{
    public function __construct(
        public readonly int $orderId,
        public readonly OrderStatusEnum $from,
        public readonly OrderStatusEnum $to,
        public readonly PaymentStatusEnum $paymentStatus,
        string $message,
    ) {
        parent::__construct($message);
    }
}
