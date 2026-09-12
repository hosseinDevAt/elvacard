<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class Login extends Component
{
    public string $phone = '';
    public string $password = '';

    protected $rules = [
        'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        'password' => ['required', 'string', 'min:1'],
    ];

    protected $messages = [
        'phone.required' => 'شماره موبایل الزامی است',
        'phone.regex' => 'شماره موبایل معتبر نیست. نمونه صحیح: 09123456789',
        'password.required' => 'رمز عبور الزامی است',
    ];

    public function login(Request $request): void
    {
        $this->validate();

        $throttleKey = 'login:'.($this->phone).':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            session()->flash('error', "تعداد تلاش‌های ورود بیش از حد مجاز است. لطفاً {$seconds} ثانیه صبر کنید.");

            return;
        }

        if (! Auth::attempt(['phone' => $this->phone, 'password' => $this->password])) {
            RateLimiter::hit($throttleKey, 120);
            session()->flash('error', 'شماره تلفن یا رمز عبور صحیح نیست.');

            return;
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();
        session()->regenerate();

        $this->redirect($user->dashboardRoute(), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
