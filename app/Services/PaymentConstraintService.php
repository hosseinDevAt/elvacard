<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentConstraintViolationException;
use App\Models\Order;

/**
 * Central payment lifecycle authority.
 *
 * Guarantees order state and payment state can never contradict each other:
 * a cancelled/completed order can never accept money and an order can never
 * end up with more than one successful payment.
 */
class PaymentConstraintService
{
    /**
     * Order statuses that may still legally receive a payment.
     *
     * @var list<string>
     */
    private const PAYABLE_STATUSES = [
        OrderStatusEnum::PENDING->value,
        OrderStatusEnum::CONFIRMED->value,
        OrderStatusEnum::PROCESSING->value,
    ];

    /**
     * @throws PaymentConstraintViolationException
     */
    public function assertPayable(Order $order): void
    {
        if (! $this->canReceivePayment($order)) {
            throw new PaymentConstraintViolationException('این سفارش قابل پرداخت نیست.');
        }
    }

    public function canReceivePayment(Order $order): bool
    {
        $status = $order->status;

        return $status instanceof OrderStatusEnum
            && in_array($status->value, self::PAYABLE_STATUSES, true);
    }

    /**
     * @throws PaymentConstraintViolationException
     */
    public function assertSingleSuccessfulPayment(Order $order): void
    {
        $hasSuccessfulPayment = $order->payments()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->exists();

        if ($hasSuccessfulPayment) {
            throw new PaymentConstraintViolationException('این سفارش قبلاً پرداخت شده است.');
        }
    }
}
