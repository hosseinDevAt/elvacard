<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\User;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only customer management. Customer identity data, order history and
 * purchase statistics are displayed for support; role editing is intentionally
 * not offered.
 */
class UserManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public string $search = '';

    public ?int $selectedUserId = null;

    public function viewUser(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            session()->flash('error', 'کاربر یافت نشد');

            return;
        }

        $this->selectedUserId = $user->id;
    }

    public function closeUserDetail(): void
    {
        $this->selectedUserId = null;
    }

    public function render()
    {
        $query = User::withCount('orders');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        $selectedUser = $this->selectedUserId
            ? User::find($this->selectedUserId)
            : null;

        if ($this->selectedUserId && ! $selectedUser) {
            $this->selectedUserId = null;
        }

        $customerStats = null;
        $recentOrders = collect();

        if ($selectedUser) {
            $userId = $selectedUser->id;

            $paidOrders = Order::where('user_id', $userId)
                ->where('payment_status', PaymentStatusEnum::PAID->value)
                ->count();

            $customerStats = [
                'totalOrders' => Order::where('user_id', $userId)->count(),
                'paidOrders' => $paidOrders,
                'totalSpent' => (int) Order::where('user_id', $userId)
                    ->where('payment_status', PaymentStatusEnum::PAID->value)
                    ->sum('total_price'),
                'lastOrderAt' => Order::where('user_id', $userId)->latest('created_at')->value('created_at'),
            ];

            $recentOrders = Order::where('user_id', $userId)
                ->latest()
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.user-manager', [
            'users' => $query->latest()->paginate(15),
            'selectedUser' => $selectedUser,
            'customerStats' => $customerStats,
            'recentOrders' => $recentOrders,
        ])->layout('layouts.admin')->title('مدیریت کاربران');
    }
}
