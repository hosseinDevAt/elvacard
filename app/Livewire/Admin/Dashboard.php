<?php

namespace App\Livewire\Admin;

use App\Models\CardType;
use App\Models\Order;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public int $totalUsers = 0;
    public int $totalOrders = 0;
    public int $pendingOrders = 0;
    public int $totalCardTypes = 0;

    public function mount(): void
    {
        $this->totalUsers = User::count();
        $this->totalOrders = Order::count();
        $this->pendingOrders = Order::where('status', 'pending')->count();
        $this->totalCardTypes = CardType::count();
    }

    public function render()
    {
        return view('livewire.admin.dashboard')->layout('layouts.admin');
    }
}
