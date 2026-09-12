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

class ManualPaymentReviewService
{
    public function __construct(private readonly OrderStateMachine $stateMachine)
    {
    }

    public function approve(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = $this->lockPayment($payment->id);

            $this->assertReviewable($payment);

            if ((int) $payment->amount !== (int) $payment->order->total_price) {
                throw new PaymentReviewException('Payment amount does not match the order total.');
            }

            $payment->status = PaymentStatus::SUCCESS;
            $payment->paid_amount = $payment->amount;
            $payment->paid_at = now();
            $payment->save();

            $order = $payment->order;
            $order->payment_status = PaymentStatusEnum::PAID;
            $order->save();

            $this->transitionOrderAfterApproval($order);
        });
    }

    public function reject(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = $this->lockPayment($payment->id);

            $this->assertReviewable($payment);

            $payment->status = PaymentStatus::FAILED;
            $payment->save();
        });
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
}