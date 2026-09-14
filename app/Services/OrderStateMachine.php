<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderLifecycleConstraintException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

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

    /**
     * A paid order may only be cancelled through an explicit refund lifecycle
     * which does not exist yet, so cancellation stays blocked. An unpaid order
     * may not be completed.
     *
     * PROCESSING deliberately does not require payment: PaymentConstraintService
     * still accepts money for confirmed/processing orders, so an unpaid order can
     * legitimately reach processing while awaiting payment. Only completion is
     * money-gated, which keeps the COMPLETED + UNPAID state impossible.
     */
    public function canTransition(Order $order, OrderStatusEnum $to): bool
    {
        $from = $order->status;

        if (! $from instanceof OrderStatusEnum) {
            return false;
        }

        if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
            return false;
        }

        return $this->isPaymentAllowedForTarget($order, $to);
    }

    /**
     * @throws InvalidOrderTransitionException When the given transition is
     *                                         structurally impossible.
     * @throws OrderLifecycleConstraintException When the transition violates
     *                                           a payment-aware lifecycle rule.
     */
    public function transition(Order $order, OrderStatusEnum $to): void
    {
        $locked = $this->lockOrder($order->id);

        $this->assertStructuralTransition($locked, $to);
        $this->assertPaymentTransition($locked, $to);

        $locked->status = $to;
        $locked->save();
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

        return collect(self::TRANSITIONS[$from->value] ?? [])
            ->map(fn (string $value) => OrderStatusEnum::from($value))
            ->filter(fn (OrderStatusEnum $to) => $this->isPaymentAllowedForTarget($order, $to))
            ->values()
            ->all();
    }

    private function isPaymentAllowedForTarget(Order $order, OrderStatusEnum $to): bool
    {
        if ($to === OrderStatusEnum::CANCELLED && $order->payment_status === PaymentStatusEnum::PAID) {
            return false;
        }

        if ($to === OrderStatusEnum::COMPLETED && $order->payment_status !== PaymentStatusEnum::PAID) {
            return false;
        }

        return true;
    }

    private function assertStructuralTransition(Order $order, OrderStatusEnum $to): void
    {
        $from = $order->status;

        if (! $from instanceof OrderStatusEnum || ! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
            throw new InvalidOrderTransitionException(
                $order->id,
                $from ?? OrderStatusEnum::PENDING,
                $to,
            );
        }
    }

    private function assertPaymentTransition(Order $order, OrderStatusEnum $to): void
    {
        if ($to === OrderStatusEnum::CANCELLED && $order->payment_status === PaymentStatusEnum::PAID) {
            throw new OrderLifecycleConstraintException(
                $order->id,
                $order->status,
                $to,
                $order->payment_status,
                'این سفارش پرداخت شده است؛ لغو آن نیازمند فرایند بازگشت وجه است.',
            );
        }

        if ($to === OrderStatusEnum::COMPLETED && $order->payment_status !== PaymentStatusEnum::PAID) {
            throw new OrderLifecycleConstraintException(
                $order->id,
                $order->status,
                $to,
                $order->payment_status,
                'سفارش پرداخت‌نشده قابل تکمیل نیست.',
            );
        }
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new \RuntimeException('Order not found.');
    }
}
