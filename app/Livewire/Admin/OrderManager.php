<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderLifecycleConstraintException;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentReviewException;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\ManualPaymentReviewService;
use App\Services\ManualRefundService;
use App\Services\OrderStateMachine;
use App\Services\RefundConstraintService;
use App\Services\RefundCore;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class OrderManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public ?string $statusFilter = null;

    public ?int $selectedOrderId = null;

    public function viewOrder(int $orderId): void
    {
        $order = Order::find($orderId);

        if (! $order) {
            session()->flash('error', 'سفارش یافت نشد');

            return;
        }

        $this->selectedOrderId = $order->id;
    }

    public function closeOrderDetail(): void
    {
        $this->selectedOrderId = null;
    }

    public function updateStatus(int $orderId, string $status, OrderStateMachine $stateMachine): void
    {
        $order = Order::find($orderId);

        if (! $order) {
            session()->flash('error', 'سفارش یافت نشد');

            return;
        }

        if (! Gate::allows('updateStatus', $order)) {
            session()->flash('error', 'شما مجاز به تغییر وضعیت این سفارش نیستید');

            return;
        }

        if (! OrderStatusEnum::tryFrom($status)) {
            session()->flash('error', 'وضعیت نامعتبر است');

            return;
        }

        try {
            $stateMachine->transition($order, OrderStatusEnum::from($status));
        } catch (OrderLifecycleConstraintException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (InvalidOrderTransitionException $e) {
            $from = $e->from->faLabel();
            $to = $e->to->faLabel();

            session()->flash('error', "تغییر وضعیت سفارش از «{$from}» به «{$to}» مجاز نیست.");

            return;
        }

        session()->flash('success', 'وضعیت سفارش بروزرسانی شد');
    }

    public function approvePayment(int $paymentId, ManualPaymentReviewService $service): void
    {
        $this->reviewPayment($paymentId, $service, 'approve');
    }

    public function rejectPayment(int $paymentId, ManualPaymentReviewService $service): void
    {
        $this->reviewPayment($paymentId, $service, 'reject');
    }

    private function reviewPayment(int $paymentId, ManualPaymentReviewService $service, string $action): void
    {
        $order = Order::find($this->selectedOrderId);

        if (! $order || ! Gate::allows('updateStatus', $order)) {
            session()->flash('error', 'شما مجاز به بررسی پرداخت نیستید');

            return;
        }

        $payment = Payment::find($paymentId);

        if (! $payment || (int) $payment->order_id !== (int) $order->id) {
            session()->flash('error', 'پرداخت یافت نشد');

            return;
        }

        try {
            if ($action === 'approve') {
                $service->approve($payment);
                session()->flash('success', 'پرداخت تأیید شد');
            } else {
                $service->reject($payment);
                session()->flash('success', 'پرداخت رد شد');
            }
        } catch (PaymentReviewException|PaymentConstraintViolationException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }
    }

    public function render(OrderStateMachine $stateMachine, RefundConstraintService $constraints)
    {
        $query = Order::with(['user', 'items.product', 'payments']);

        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            if (OrderStatusEnum::tryFrom($this->statusFilter)) {
                $query->where('status', $this->statusFilter);
            }
        }

        $orders = $query->latest()->paginate(15);

        $transitions = $orders->getCollection()
            ->mapWithKeys(fn (Order $order) => [
                $order->id => collect($stateMachine->allowedTargets($order))
                    ->unique(fn (OrderStatusEnum $status) => $status->value === 'processing' ? 'production' : $status->value)
                    ->map(fn (OrderStatusEnum $status) => [
                        'value' => $status->value === 'processing' ? 'production' : $status->value,
                        'label' => $status->faLabel(),
                    ])
                    ->unique('value')
                    ->values()
                    ->all(),
            ])
            ->all();

        $selectedOrder = $this->selectedOrderId
            ? Order::with(['user', 'items.product', 'payments'])->find($this->selectedOrderId)
            : null;

        if ($this->selectedOrderId && ! $selectedOrder) {
            $this->selectedOrderId = null;
        }

        if ($selectedOrder !== null && ! isset($transitions[$selectedOrder->id])) {
            $transitions[$selectedOrder->id] = collect($stateMachine->allowedTargets($selectedOrder))
                ->unique(fn (OrderStatusEnum $status) => $status->value === 'processing' ? 'production' : $status->value)
                ->map(fn (OrderStatusEnum $status) => [
                    'value' => $status->value === 'processing' ? 'production' : $status->value,
                    'label' => $status->faLabel(),
                ])
                ->unique('value')
                ->values()
                ->all();
        }

        // Same rule as the payments surface: the displayed refundable balance
        // comes from the single authoritative calculation, not from a view-level
        // recomputation that could drift from what the server accepts.
        $refundableByPaymentId = [];

        if ($selectedOrder !== null) {
            foreach ($selectedOrder->payments as $orderPayment) {
                $refundableByPaymentId[(int) $orderPayment->id] = $constraints->refundableAmount($orderPayment);
            }
        }

        return view('livewire.admin.order-manager', [
            'orders' => $orders,
            'transitions' => $transitions,
            'selectedOrder' => $selectedOrder,
            'refundableByPaymentId' => $refundableByPaymentId,
        ])->layout('layouts.admin')->title('مدیریت سفارشات');
    }
}
