<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentRetryException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * The single locked authority for creating a manual transfer payment.
 *
 * Both the first submission and a retry submission funnel through this service
 * so a race between two concurrent submissions cannot produce more than one
 * active payment for the same order.
 */
final class ManualPaymentCreationService
{
    public function __construct(private readonly PaymentConstraintService $constraints) {}

    /**
     * @param  array{receipt_path: string, tracking_number?: ?string, note?: ?string}  $data
     */
    public function createPayment(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data) {
            $lockedOrder = $this->lockOrder($order->id);

            $this->constraints->assertPayable($lockedOrder);
            $this->constraints->assertSingleSuccessfulPayment($lockedOrder);

            if ($lockedOrder->payments()->active()->exists()) {
                throw new PaymentRetryException('پرداخت فعالی برای این سفارش وجود دارد.');
            }

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

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }
}
