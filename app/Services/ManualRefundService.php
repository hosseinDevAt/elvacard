<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Exceptions\RefundException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manual (bank transfer) refund service.
 *
 * Mirrors ManualPaymentReviewService: the admin confirms the refund directly
 * without a provider call, since the bank transfer happens outside the system.
 * The refund is created as COMPLETED in a single transaction.
 */
class ManualRefundService
{
    public function __construct(
        private readonly RefundConstraintService $constraints,
    ) {}

    /**
     * @throws RefundConstraintViolationException
     */
    public function refund(Payment $payment, int $amount, ?string $reason = null): Refund
    {
        $paymentId = $payment->id;
        $orderId = $payment->order_id;

        if ($payment->method !== PaymentMethod::MANUAL_TRANSFER) {
            throw new RefundException('تنها پرداخت‌های کارت به کارت به صورت دستی بازگشت داده می‌شوند.');
        }

        $this->constraints->assertRefundable($payment, $amount);

        $refund = DB::transaction(function () use ($payment, $amount, $reason) {
            $locked = $this->lockPayment($payment->id);

            $this->constraints->assertSuccessfulPayment($locked);
            $this->constraints->assertPositiveAmount($amount);
            $this->constraints->assertAmountWithinRefundable($locked, $amount);
            $this->constraints->assertNoInFlightRefund($locked);

            $refund = Refund::create([
                'payment_id' => $locked->id,
                'amount' => $amount,
                'status' => RefundStatus::COMPLETED,
                'reason' => $reason,
                'refunded_at' => now(),
                'metadata' => [
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now()->toIso8601String(),
                ],
            ]);

            // The order is only REFUNDED when every successful payment on it is
            // fully refunded, so a historical order carrying more than one
            // successful payment is never reported as refunded while money is
            // still held.
            $order = $this->lockOrder($locked->order_id);

            if ($this->constraints->isOrderFullyRefunded($order)) {
                $order->payment_status = PaymentStatusEnum::REFUNDED;
                $order->save();
            }

            return $refund;
        });

        Log::info('Manual refund completed', [
            'refund_id' => $refund->id,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'amount' => $amount,
            'admin_id' => auth()->id(),
        ]);

        return $refund->fresh();
    }

    private function lockPayment(int $paymentId): Payment
    {
        $query = Payment::query()->where('id', $paymentId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new RefundException('پرداخت یافت نشد.');
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new RefundException('سفارش یافت نشد.');
    }
}
