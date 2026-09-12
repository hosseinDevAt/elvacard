<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentRetryException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class ManualPaymentRetryService
{
    public function createRetryPayment(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = $this->lockOrder($order->id);

            $this->assertRetryable($lockedOrder);

            return Payment::create([
                'order_id' => $lockedOrder->id,
                'method' => PaymentMethod::MANUAL_TRANSFER->value,
                'status' => PaymentStatus::PENDING_REVIEW->value,
                'amount' => (int) $lockedOrder->total_price,
                'tracking_code' => $data['tracking_number'] ?? null,
                'receipt_path' => $data['receipt_path'],
                'metadata' => ['note' => $data['note'] ?? null],
            ]);
        });
    }

    public function isRetryable(Order $order): bool
    {
        try {
            $this->assertRetryable($order);

            return true;
        } catch (PaymentRetryException) {
            return false;
        }
    }

    private function assertRetryable(Order $order): void
    {
        if ($order->payment_status === PaymentStatusEnum::PAID) {
            throw new PaymentRetryException('سفارش قبلاً پرداخت شده است.');
        }

        $hasActive = Payment::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [PaymentStatus::PENDING_REVIEW->value, PaymentStatus::SUCCESS->value])
            ->exists();

        if ($hasActive) {
            throw new PaymentRetryException('پرداخت فعالی برای این سفارش وجود دارد.');
        }

        $hasFailed = Payment::query()
            ->where('order_id', $order->id)
            ->where('method', PaymentMethod::MANUAL_TRANSFER->value)
            ->where('status', PaymentStatus::FAILED->value)
            ->exists();

        if (! $hasFailed) {
            throw new PaymentRetryException('پرداخت رد شده‌ای برای پرداخت مجدد وجود ندارد.');
        }
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
