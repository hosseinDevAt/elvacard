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
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Register extends Component
{
    public int $step = 1;

    public string $phone = '';

    public string $code = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    protected $messages = [
        'phone.required' => 'شماره موبایل الزامی است.',
        'phone.regex' => 'شماره موبایل معتبر نیست. نمونه صحیح: 09123456789',
        'phone.unique' => 'این شماره موبایل قبلاً ثبت‌نام شده است.',
        'code.required' => 'کد تأیید را وارد کنید.',
        'code.digits' => 'کد تأیید باید عدد باشد.',
        'firstName.required' => 'نام را وارد کنید.',
        'lastName.required' => 'نام خانوادگی را وارد کنید.',
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
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:users,phone'],
        ]);

        try {
            app(OtpService::class)->issue($this->phone, OtpPurpose::REGISTRATION);
        } catch (SmsSendingFailedException) {
            $this->addError('phone', 'امکان ارسال کد تأیید وجود ندارد. لطفاً بعداً دوباره تلاش کنید.');

            return;
        }

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

        $valid = app(OtpService::class)->verify($this->phone, OtpPurpose::REGISTRATION, $this->code);

        if (! $valid) {
            $this->addError('code', 'کد وارد شده صحیح نیست، منقضی شده یا بیش از حد تلاش شده است.');

            return;
        }

        Session::put('register.verified_phone', $this->phone);
        Session::put('register.verified_at', now());

        $this->step = 3;
    }

    public function completeRegistration(): void
    {
        $verifiedPhone = Session::get('register.verified_phone');
        $verifiedAt = Session::get('register.verified_at');

        if (! is_string($verifiedPhone) || ! $verifiedAt || $verifiedPhone !== $this->phone) {
            Session::forget(['register.verified_phone', 'register.verified_at']);
            $this->reset(['code', 'firstName', 'lastName', 'password', 'passwordConfirmation']);
            $this->step = 1;
            $this->addError('phone', 'جلسه تأیید شماره منقضی شده است. لطفاً دوباره شروع کنید.');

            return;
        }

        $expiry = (int) config('sms.otp.expires_in', 120);

        if (Carbon::parse($verifiedAt)->addSeconds($expiry)->isPast()) {
            Session::forget(['register.verified_phone', 'register.verified_at']);
            $this->reset(['code', 'firstName', 'lastName', 'password', 'passwordConfirmation']);
            $this->step = 1;
            $this->addError('phone', 'جلسه تأیید شماره منقضی شده است. لطفاً دوباره شروع کنید.');

            return;
        }

        $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($this->password !== $this->passwordConfirmation) {
            $this->addError('password', 'تکرار رمز عبور مطابقت ندارد.');

            return;
        }

        if (User::query()->where('phone', $verifiedPhone)->exists()) {
            $this->addError('phone', 'این شماره موبایل قبلاً ثبت‌نام شده است. وارد حساب شوید.');

            return;
        }

        $user = User::query()->create([
            'phone' => $verifiedPhone,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'name' => trim($this->firstName.' '.$this->lastName),
            'password' => $this->password,
            'role' => 'customer',
        ]);

        Session::forget(['register.verified_phone', 'register.verified_at']);

        Auth::login($user);

        $this->redirect(route('account.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('layouts.auth');
    }
}