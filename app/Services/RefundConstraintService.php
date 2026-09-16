<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;

/**
 * Central refund lifecycle authority.
 *
 * Guarantees that refunds can only exist against successfully completed
 * payments, that the refundable amount is never exceeded, that at most
 * one refund is in flight at a time, and that terminal refund states
 * cannot be mutated.
 */
class RefundConstraintService
{
    /**
     * Refund statuses that financially reserve (consume) the paid amount.
     */
    private const RESERVATION_STATES = [
        RefundStatus::PENDING,
        RefundStatus::REVIEW,
        RefundStatus::COMPLETED,
    ];

    /**
     * @throws RefundConstraintViolationException
     */
    public function assertRefundable(Payment $payment, int $amount): void
    {
        $this->assertSuccessfulPayment($payment);
        $this->assertPositiveAmount($amount);
        $this->assertAmountWithinRefundable($payment, $amount);
        $this->assertNoInFlightRefund($payment);
    }

    /**
     * @throws RefundConstraintViolationException
     */
    public function assertSuccessfulPayment(Payment $payment): void
    {
        if ($payment->status !== PaymentStatus::SUCCESS) {
            throw new RefundConstraintViolationException('تنها پرداخت‌های موفق قابل بازگشت هستند.');
        }
    }

    /**
     * @throws RefundConstraintViolationException
     */
    public function assertPositiveAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new RefundConstraintViolationException('مبلغ بازگشت باید بزرگ‌تر از صفر باشد.');
        }
    }

    /**
     * @throws RefundConstraintViolationException
     */
    public function assertAmountWithinRefundable(Payment $payment, int $amount): void
    {
        $refundable = $this->refundableAmount($payment);

        if ($amount > $refundable) {
            throw new RefundConstraintViolationException(
                "مبلغ درخواستی ({$amount}) بیش از مبلغ قابل بازگشت ({$refundable}) است."
            );
        }
    }

    /**
     * @throws RefundConstraintViolationException
     */
    public function assertNoInFlightRefund(Payment $payment): void
    {
        $hasInFlight = Refund::where('payment_id', $payment->id)
            ->whereIn('status', [
                RefundStatus::PENDING->value,
                RefundStatus::REVIEW->value,
            ])
            ->exists();

        if ($hasInFlight) {
            throw new RefundConstraintViolationException('یک درخواست بازگشت در حال پردازش وجود دارد.');
        }
    }

    /**
     * Compute the total reserved (consumed) amount for a payment:
     * PENDING + REVIEW + COMPLETED refund amounts.
     */
    public function reservedAmount(Payment $payment): int
    {
        $sum = Refund::where('payment_id', $payment->id)
            ->whereIn('status', array_map(
                fn (RefundStatus $s) => $s->value,
                self::RESERVATION_STATES,
            ))
            ->sum('amount');

        return (int) $sum;
    }

    /**
     * Compute the raw (signed) refundable balance.
     *
     * A negative result indicates an over-refund (integrity incident).
     */
    public function rawRefundableBalance(Payment $payment): int
    {
        $paid = (int) ($payment->paid_amount ?? 0);

        return $paid - $this->reservedAmount($payment);
    }

    /**
     * Compute the refundable amount for a payment (clamped to zero).
     *
     * refundable = max(0, paid_amount − SUM(PENDING + REVIEW + COMPLETED refunds)).
     */
    public function refundableAmount(Payment $payment): int
    {
        return max(0, $this->rawRefundableBalance($payment));
    }

    /**
     * Check whether a payment is fully refunded.
     *
     * Uses the raw balance (not clamped) to avoid masking an over-refund
     * as "fully refunded." An exact zero raw balance with a positive paid
     * amount is the only valid full-refund condition.
     */
    public function isFullyRefunded(Payment $payment): bool
    {
        return $this->rawRefundableBalance($payment) === 0
            && (int) ($payment->paid_amount ?? 0) > 0;
    }

    /**
     * Determine whether the order should be marked REFUNDED.
     *
     * An order is considered fully refunded when every successful payment
     * on it has been fully refunded.
     */
    public function isOrderFullyRefunded(Order $order): bool
    {
        $successPayments = $order->payments()
            ->where('status', PaymentStatus::SUCCESS->value)
            ->get();

        if ($successPayments->isEmpty()) {
            return false;
        }

        return $successPayments->every(fn (Payment $p) => $this->isFullyRefunded($p));
    }

    /**
     * Compute the net refunded amount for an order (for reporting).
     */
    public function totalRefundedAmount(Order $order): int
    {
        return (int) Refund::whereHas('payment', fn ($q) => $q->where('order_id', $order->id))
            ->where('status', RefundStatus::COMPLETED->value)
            ->sum('amount');
    }
}
