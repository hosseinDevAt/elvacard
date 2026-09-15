<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;

class Dashboard extends Component
{
    use AuthorizesAdminActions;

    public int $totalUsers = 0;

    public int $totalOrders = 0;

    public int $pendingOrders = 0;

    public int $totalProducts = 0;

    public int $pendingReviewPayments = 0;

    public int $successfulPayments = 0;

    public int $totalRevenue = 0;

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalOrders = Order::count();
        $this->pendingOrders = Order::where('status', 'pending')->count();
        $this->totalProducts = Product::count();

        $this->pendingReviewPayments = Payment::where('status', PaymentStatus::PENDING_REVIEW->value)->count();
        $this->successfulPayments = Payment::where('status', PaymentStatus::SUCCESS->value)->count();
        $this->totalRevenue = (int) Payment::where('status', PaymentStatus::SUCCESS->value)->sum('paid_amount');
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('layouts.admin')->title('داشبورد');
    }
}
