<?php

namespace App\Exceptions;

use App\Enums\OrderStatusEnum;
use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public function __construct(
        public readonly int $orderId,
        public readonly OrderStatusEnum $from,
        public readonly OrderStatusEnum $to,
    ) {
        parent::__construct(
            "Invalid order status transition from [{$from->value}] to [{$to->value}] for order [{$orderId}]."
        );
    }
}
