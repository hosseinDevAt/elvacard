<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;

class OrderStateMachine
{
    /**
     * Allowed lifecycle transitions.
     *
     * @var array<string, list<string>>
     */
    private const TRANSITIONS = [
        OrderStatusEnum::PENDING->value => [
            OrderStatusEnum::CONFIRMED->value,
            OrderStatusEnum::CANCELLED->value,
        ],
        OrderStatusEnum::CONFIRMED->value => [
            OrderStatusEnum::PROCESSING->value,
            OrderStatusEnum::CANCELLED->value,
        ],
        OrderStatusEnum::PROCESSING->value => [
            OrderStatusEnum::COMPLETED->value,
            OrderStatusEnum::CANCELLED->value,
        ],
        OrderStatusEnum::COMPLETED->value => [],
        OrderStatusEnum::CANCELLED->value => [],
    ];

    public function canTransition(Order $order, OrderStatusEnum $to): bool
    {
        $from = $order->status;

        if (! $from instanceof OrderStatusEnum) {
            return false;
        }

        return in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true);
    }

    /**
     * @return list<OrderStatusEnum> valid destination statuses from the order's current status
     */
    public function allowedTargets(Order $order): array
    {
        $from = $order->status;

        if (! $from instanceof OrderStatusEnum) {
            return [];
        }

        return array_map(
            fn (string $value) => OrderStatusEnum::from($value),
            self::TRANSITIONS[$from->value] ?? [],
        );
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    public function transition(Order $order, OrderStatusEnum $to): void
    {
        if (! $this->canTransition($order, $to)) {
            throw new InvalidOrderTransitionException(
                $order->id,
                $order->status ?? OrderStatusEnum::PENDING,
                $to,
            );
        }

        $order->status = $to;
        $order->save();
    }
}
