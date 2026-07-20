<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

class OrderManager extends Component
{
    use WithPagination;

    public ?string $statusFilter = null;

    public function updateStatus(int $orderId, string $status): void
    {
        Order::find($orderId)->update(['status' => $status]);
        session()->flash('success', 'وضعیت سفارش بروزرسانی شد');
    }

    public function render()
    {
        $query = Order::with(['user', 'orderItems.cardType.color']);

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.admin.order-manager', [
            'orders' => $query->latest()->paginate(15),
        ])->layout('layouts.admin');
    }
}
