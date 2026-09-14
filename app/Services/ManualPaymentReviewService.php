<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentReviewException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManualPaymentReviewService
{
    public function __construct(
        private readonly OrderStateMachine $stateMachine,
        private readonly PaymentConstraintService $constraints,
    ) {}

    public function approve(Payment $payment): void
    {
        $paymentId = $payment->id;
        $orderId = $payment->order_id;

        DB::transaction(function () use ($payment) {
            $payment = $this->lockPayment($payment->id);

            $this->assertReviewable($payment);

            $order = $this->lockOrder($payment->order_id);

            $this->constraints->assertPayable($order);
            $this->constraints->assertSingleSuccessfulPayment($order);

            if ((int) $payment->amount !== (int) $order->total_price) {
                throw new PaymentReviewException('Payment amount does not match the order total.');
            }

            $payment->status = PaymentStatus::SUCCESS;
            $payment->paid_amount = $payment->amount;
            $payment->paid_at = now();
            $payment->metadata = array_merge($payment->metadata ?? [], [
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toIso8601String(),
            ]);
            $payment->save();

            $order->payment_status = PaymentStatusEnum::PAID;
            $order->save();

            $this->transitionOrderAfterApproval($order);
        });

        Log::info('Manual payment approved', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'admin_id' => auth()->id(),
        ]);
    }

    public function reject(Payment $payment): void
    {
        $paymentId = $payment->id;
        $orderId = $payment->order_id;

        DB::transaction(function () use ($payment) {
            $payment = $this->lockPayment($payment->id);

            $this->assertReviewable($payment);

            $payment->status = PaymentStatus::FAILED;
            $payment->metadata = array_merge($payment->metadata ?? [], [
                'reason' => 'admin_rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toIso8601String(),
            ]);
            $payment->save();
        });

        Log::warning('Manual payment rejected', [
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'admin_id' => auth()->id(),
        ]);
    }

    private function assertReviewable(Payment $payment): void
    {
        if ($payment->method !== PaymentMethod::MANUAL_TRANSFER) {
            throw new PaymentReviewException('Only manual transfer payments can be reviewed.');
        }

        if ($payment->status !== PaymentStatus::PENDING_REVIEW) {
            throw new PaymentReviewException('Payment is not awaiting review.');
        }
    }

    private function transitionOrderAfterApproval(Order $order): void
    {
        if ($this->stateMachine->canTransition($order, OrderStatusEnum::CONFIRMED)) {
            $this->stateMachine->transition($order, OrderStatusEnum::CONFIRMED);
        }
    }

    private function lockPayment(int $paymentId): Payment
    {
        $query = Payment::query()->where('id', $paymentId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new PaymentReviewException('Payment not found.');
    }

    private function lockOrder(int $orderId): Order
    {
        $query = Order::query()->where('id', $orderId);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        return $query->first()
            ?? throw new PaymentReviewException('Order not found.');
    }
}
