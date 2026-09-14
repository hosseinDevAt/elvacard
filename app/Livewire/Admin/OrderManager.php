<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatusEnum;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentReviewException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ManualPaymentReviewService;
use App\Services\OrderStateMachine;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class OrderManager extends Component
{
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
        } catch (InvalidOrderTransitionException $e) {
            $from = $e->from->label();
            $to = $e->to->label();

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

    public function render(OrderStateMachine $stateMachine)
    {
        $query = Order::with(['user', 'items', 'payments']);

        if ($this->statusFilter !== null && $this->statusFilter !== '') {
            if (OrderStatusEnum::tryFrom($this->statusFilter)) {
                $query->where('status', $this->statusFilter);
            }
        }

        $orders = $query->latest()->paginate(15);

        $transitions = $orders->getCollection()
            ->mapWithKeys(fn (Order $order) => [
                $order->id => collect($stateMachine->allowedTargets($order))
                    ->map(fn (OrderStatusEnum $status) => [
                        'value' => $status->value,
                        'label' => $status->label(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $selectedOrder = $this->selectedOrderId
            ? Order::with(['user', 'items', 'payments'])->find($this->selectedOrderId)
            : null;

        if ($this->selectedOrderId && ! $selectedOrder) {
            $this->selectedOrderId = null;
        }

        return view('livewire.admin.order-manager', [
            'orders' => $orders,
            'transitions' => $transitions,
            'selectedOrder' => $selectedOrder,
        ])->layout('layouts.admin')->title('مدیریت سفارشات');
    }
}
