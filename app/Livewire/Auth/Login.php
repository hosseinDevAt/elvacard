<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public string $phone = '';

    protected $rules = [
        'phone' => ['required', 'string', 'digits:11'],
    ];

    protected $messages = [
        'phone.required' => 'شماره تلفن الزامی است',
        'phone.digits' => 'شماره تلفن باید ۱۱ رقم باشد',
    ];

    public function login(): void
    {
        $this->validate();

        $user = User::where('phone', $this->phone)->first();

        if (!$user) {
            $user = User::create([
                'phone' => $this->phone,
                'name' => 'کاربر ' . $this->phone,
                'password' => bcrypt('123456'),
            ]);
        }

        Auth::login($user);
        session()->regenerate();
        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
