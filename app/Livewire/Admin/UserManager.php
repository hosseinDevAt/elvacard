<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        $query = User::withCount('orders');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%");
            });
        }

        return view('livewire.admin.user-manager', [
            'users' => $query->latest()->paginate(15),
        ])->layout('layouts.admin');
    }
}
