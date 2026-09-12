<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\SmsSendingFailedException;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class ForgotPassword extends Component
{
    public int $step = 1;

    public string $phone = '';

    public string $code = '';

    public string $resetToken = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    protected $messages = [
        'phone.required' => 'شماره موبایل الزامی است.',
        'phone.regex' => 'شماره موبایل معتبر نیست. نمونه صحیح: 09123456789',
        'code.required' => 'کد تأیید را وارد کنید.',
        'code.digits' => 'کد تأیید باید عدد باشد.',
    ];

    private function otpRequestThrottled(): bool
    {
        $key = 'otp.request:'.normalize_phone($this->phone).':'.request()->ip();
        $cooldown = (int) config('sms.otp.resend_cooldown', 60);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('phone', "لطفاً قبل از درخواست کد جدید، {$seconds} ثانیه صبر کنید.");

            return true;
        }

        RateLimiter::hit($key, max(1, $cooldown));

        return false;
    }

    private function otpVerifyThrottled(): bool
    {
        $key = 'otp.verify:'.normalize_phone($this->phone).':'.request()->ip();
        $max = (int) config('sms.otp.verify_rate_limit', 10);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('code', "تعداد تلاش‌ها بیش از حد مجاز است. لطفاً {$seconds} ثانیه صبر کنید.");

            return true;
        }

        RateLimiter::hit($key, 60);

        return false;
    }

    public function requestOtp(): void
    {
        if ($this->otpRequestThrottled()) {
            return;
        }

        $this->validate([
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
        ]);

        $user = User::query()->where('phone', $this->phone)->first();

        if ($user) {
            try {
                app(OtpService::class)->issue($this->phone, OtpPurpose::PASSWORD_RESET);
            } catch (SmsSendingFailedException) {
                $this->addError('phone', 'امکان ارسال کد تأیید وجود ندارد. لطفاً بعداً دوباره تلاش کنید.');

                return;
            }
        }

        // Generic message to avoid leaking which phones have accounts.
        session()->flash('status', 'اگر این شماره در سیستم ثبت شده باشد، کد تأیید برای آن ارسال خواهد شد.');

        $this->step = 2;
    }

    public function verifyCode(): void
    {
        if ($this->otpVerifyThrottled()) {
            return;
        }

        $this->validate([
            'code' => ['required', 'numeric', 'digits:'.(int) config('sms.otp.length', 6)],
        ]);

        $valid = app(OtpService::class)->verify($this->phone, OtpPurpose::PASSWORD_RESET, $this->code);

        if (! $valid) {
            $this->addError('code', 'کد وارد شده صحیح نیست، منقضی شده یا بیش از حد تلاش شده است.');

            return;
        }

        $token = Str::random(64);

        Session::put('reset.authorization', [
            'token' => $token,
            'phone' => $this->phone,
            'expires_at' => now()->addSeconds((int) config('sms.otp.expires_in', 120)),
        ]);

        $this->resetToken = $token;

        $this->step = 3;
    }

    public function completeReset(): void
    {
        $authorization = Session::get('reset.authorization');

        $authorized =
            is_array($authorization)
            && isset($authorization['token'], $authorization['phone'], $authorization['expires_at'])
            && is_string($authorization['token'])
            && hash_equals($authorization['token'], (string) $this->resetToken)
            && ! Carbon::parse($authorization['expires_at'])->isPast();

        if (! $authorized) {
            Session::forget('reset.authorization');
            $this->reset(['password', 'passwordConfirmation', 'resetToken']);
            $this->step = 1;
            $this->addError('phone', 'جلسه بازنشانی رمز منقضی شده است. لطفاً دوباره شروع کنید.');

            return;
        }

        $this->validate([
            'password' => ['required', Password::defaults()],
        ]);

        if ($this->password !== $this->passwordConfirmation) {
            $this->addError('password', 'تکرار رمز عبور مطابقت ندارد.');

            return;
        }

        $user = User::query()->where('phone', $authorization['phone'])->first();

        if (! $user) {
            Session::forget('reset.authorization');
            $this->step = 1;
            $this->addError('phone', 'این شماره در سیستم ثبت نشده است.');

            return;
        }

        $user->password = $this->password;
        $user->save();

        Session::forget('reset.authorization');

        Auth::login($user);

        $this->redirect(route('account.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('layouts.auth');
    }
}