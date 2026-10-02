<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpPurpose;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\Support\Traits\UsesRecordingSms;
use Tests\TestCase;

/**
 * N-Onyx-47 — Rate limiting verification.
 *
 * Every protection exercised here already exists in production code. This file
 * only proves the implemented behaviour, using the deployed keys, windows and
 * messages as the source of truth:
 *
 *   login          login:{phone}:{ip}              5 attempts / 120s, cleared on success
 *   otp request    otp.request:{phone}:{ip}        1 attempt  / sms.otp.resend_cooldown
 *   otp verify     otp.verify:{phone}:{ip}         sms.otp.verify_rate_limit / 60s
 *   otp attempts   OtpCode.attempts                sms.otp.max_attempts (service level)
 *
 * Guest tracking (throttle:5,1) is proven in SecurityInfrastructureHardeningTest.
 */
class RateLimitingVerificationTest extends TestCase
{
    use RefreshDatabase;
    use UsesRecordingSms;

    private const LOGIN_INVALID = 'شماره تلفن یا رمز عبور صحیح نیست.';

    private const LOGIN_THROTTLED = 'تعداد تلاش‌های ورود بیش از حد مجاز است.';

    private const OTP_REQUEST_THROTTLED = 'لطفاً قبل از درخواست کد جدید';

    private const OTP_VERIFY_THROTTLED = 'تعداد تلاش‌ها بیش از حد مجاز است.';

    private const OTP_INVALID = 'کد وارد شده صحیح نیست';

    private const OTP_GENERIC = 'اگر این شماره در سیستم ثبت شده باشد، کد تأیید برای آن ارسال خواهد شد.';

    private function customer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'admin'], $overrides));
    }

    private function loginKey(string $phone, ?string $ip = null): string
    {
        return 'login:'.$phone.':'.($ip ?? '127.0.0.1');
    }

    // =========================================================================
    // 1. LOGIN — 5 attempts / 120 seconds
    // =========================================================================

    public function test_login_attempts_below_the_limit_are_processed_normally(): void
    {
        $this->customer(['phone' => '09120000001']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('phone', '09120000001')
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertSee(self::LOGIN_INVALID)
                ->assertDontSee(self::LOGIN_THROTTLED);
        }

        $this->assertGuest();
        $this->assertSame(
            5,
            RateLimiter::attempts($this->loginKey('09120000001')),
            'All five attempts must have been counted against the throttle.',
        );
    }

    public function test_login_is_throttled_once_the_limit_is_reached(): void
    {
        $this->customer(['phone' => '09120000002']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('phone', '09120000002')
                ->set('password', 'wrong-password')
                ->call('login');
        }

        // Sixth attempt: throttled even though the credentials are now correct.
        Livewire::test(Login::class)
            ->set('phone', '09120000002')
            ->set('password', 'password')
            ->call('login')
            ->assertSee(self::LOGIN_THROTTLED)
            ->assertDontSee(self::LOGIN_INVALID);

        $this->assertGuest();
    }

    public function test_login_throttle_key_is_scoped_to_the_phone_number(): void
    {
        $this->customer(['phone' => '09120000003']);
        $this->customer(['phone' => '09120000004']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('phone', '09120000003')
                ->set('password', 'wrong-password')
                ->call('login');
        }

        $this->assertSame(5, RateLimiter::attempts($this->loginKey('09120000003')));

        // A different phone number must not inherit the first one's throttle.
        $this->assertSame(0, RateLimiter::attempts($this->loginKey('09120000004')));

        Livewire::test(Login::class)
            ->set('phone', '09120000004')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertSee(self::LOGIN_INVALID)
            ->assertDontSee(self::LOGIN_THROTTLED);
    }

    public function test_login_throttle_key_matches_the_implemented_format(): void
    {
        $this->customer(['phone' => '09120000005']);

        Livewire::test(Login::class)
            ->set('phone', '09120000005')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertSame(
            1,
            RateLimiter::attempts('login:09120000005:127.0.0.1'),
            'The throttle must be keyed on login:{phone}:{ip}.',
        );
    }

    public function test_a_successful_login_clears_the_throttle_counter(): void
    {
        $user = $this->admin(['phone' => '09120000006', 'password' => 'password']);

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            Livewire::test(Login::class)
                ->set('phone', $user->phone)
                ->set('password', 'wrong-password')
                ->call('login');
        }

        $this->assertSame(4, RateLimiter::attempts($this->loginKey($user->phone)));

        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(
            0,
            RateLimiter::attempts($this->loginKey($user->phone)),
            'The implemented design clears the counter once authentication succeeds.',
        );
    }

    public function test_login_throttle_window_is_120_seconds(): void
    {
        $this->admin(['phone' => '09120000007', 'password' => 'password']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            Livewire::test(Login::class)
                ->set('phone', '09120000007')
                ->set('password', 'wrong-password')
                ->call('login');
        }

        // Still throttled just before the window elapses.
        $this->travel(119)->seconds();

        Livewire::test(Login::class)
            ->set('phone', '09120000007')
            ->set('password', 'password')
            ->call('login')
            ->assertSee(self::LOGIN_THROTTLED);

        $this->assertGuest();

        // Released once the 120 second window has passed.
        $this->travel(2)->seconds();

        Livewire::test(Login::class)
            ->set('phone', '09120000007')
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticated();
    }

    // =========================================================================
    // 2. OTP REQUEST — otp.request:{phone}:{ip}, 1 attempt per cooldown
    // =========================================================================

    public function test_the_first_otp_request_is_delivered(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2)
            ->assertHasNoErrors();

        $this->assertNotNull($recording->lastCode());
        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_an_immediate_second_otp_request_is_throttled(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasNoErrors();

        $sentAfterFirst = count($recording->sent);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasErrors('phone')
            ->assertSee(self::OTP_REQUEST_THROTTLED);

        $this->assertCount(
            $sentAfterFirst,
            $recording->sent,
            'A throttled request must not send another message.',
        );
    }

    public function test_the_otp_request_cooldown_uses_the_configured_window(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();
        $cooldown = (int) config('sms.otp.resend_cooldown');

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        $this->assertSame(
            1,
            RateLimiter::attempts($this->otpRequestKey($user->phone)),
            'The cooldown must be keyed on otp.request:{phone}:{ip}.',
        );

        $this->travel($cooldown - 1)->seconds();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasErrors('phone')
            ->assertSee(self::OTP_REQUEST_THROTTLED);

        $this->travel(2)->seconds();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasNoErrors();

        $this->assertCount(2, $recording->sent, 'The second message goes out once the cooldown expires.');
    }

    public function test_a_provider_failure_persists_no_otp(): void
    {
        // The shipped "disabled" driver always fails, using existing production
        // infrastructure rather than a bespoke fake.
        config(['sms.default' => 'disabled']);

        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasErrors('phone')
            ->assertSee('امکان ارسال کد تأیید وجود ندارد');

        $this->assertDatabaseCount('otp_codes', 0);
    }

    // =========================================================================
    // 3. OTP VERIFICATION — otp.verify:{phone}:{ip} request rate limiter
    // =========================================================================

    public function test_a_valid_otp_verifies_within_the_allowed_attempts(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->set('code', $recording->lastCode())
            ->call('verifyCode')
            ->assertSet('step', 3)
            ->assertHasNoErrors();
    }

    public function test_a_wrong_otp_is_rejected_without_a_throttle_error(): void
    {
        $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        Livewire::test(ForgotPassword::class)
            ->set('step', 2)
            ->set('phone', $user->phone)
            ->set('code', '999999')
            ->call('verifyCode')
            ->assertHasErrors('code')
            ->assertSee(self::OTP_INVALID)
            ->assertDontSee(self::OTP_VERIFY_THROTTLED);
    }

    public function test_the_otp_verify_rate_limiter_blocks_the_correct_code(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        // Raise the service-level attempt budget so only the request rate
        // limiter can be responsible for blocking the correct code.
        config(['sms.otp.max_attempts' => 100]);
        config(['sms.otp.verify_rate_limit' => 3]);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        $correct = $recording->lastCode();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            Livewire::test(ForgotPassword::class)
                ->set('step', 2)
                ->set('phone', $user->phone)
                ->set('code', '999999')
                ->call('verifyCode')
                ->assertHasErrors('code')
                ->assertSee(self::OTP_INVALID)
                ->assertDontSee(self::OTP_VERIFY_THROTTLED);
        }

        $this->assertSame(
            3,
            RateLimiter::attempts($this->otpVerifyKey($user->phone)),
            'The limiter must be keyed on otp.verify:{phone}:{ip}.',
        );

        // The correct code is now refused by the rate limiter.
        Livewire::test(ForgotPassword::class)
            ->set('step', 2)
            ->set('phone', $user->phone)
            ->set('code', $correct)
            ->call('verifyCode')
            ->assertSet('step', 2)
            ->assertHasErrors('code')
            ->assertSee(self::OTP_VERIFY_THROTTLED);

        $this->assertDatabaseHas('otp_codes', ['consumed_at' => null]);
    }

    public function test_the_otp_verify_rate_limiter_window_expires(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        config(['sms.otp.max_attempts' => 100]);
        config(['sms.otp.verify_rate_limit' => 2]);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        $correct = $recording->lastCode();

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            Livewire::test(ForgotPassword::class)
                ->set('step', 2)
                ->set('phone', $user->phone)
                ->set('code', '999999')
                ->call('verifyCode');
        }

        Livewire::test(ForgotPassword::class)
            ->set('step', 2)
            ->set('phone', $user->phone)
            ->set('code', $correct)
            ->call('verifyCode')
            ->assertSet('step', 2)
            ->assertHasErrors('code')
            ->assertSee(self::OTP_VERIFY_THROTTLED);

        // The 60 second window lapses and the correct code is accepted again.
        $this->travel(61)->seconds();

        Livewire::test(ForgotPassword::class)
            ->set('step', 2)
            ->set('phone', $user->phone)
            ->set('code', $correct)
            ->call('verifyCode')
            ->assertSet('step', 3)
            ->assertHasNoErrors();
    }

    public function test_the_otp_attempt_budget_is_independent_of_the_request_rate_limiter(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        // No request rate limiter involved at all: OtpService is called directly.
        config(['sms.otp.max_attempts' => 3]);
        config(['sms.otp.verify_rate_limit' => 1000]);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        $correct = $recording->lastCode();
        $service = app(OtpService::class);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->assertFalse(
                $service->verify($user->phone, OtpPurpose::PASSWORD_RESET, '999999'),
            );
        }

        $this->assertSame(
            0,
            RateLimiter::attempts($this->otpVerifyKey($user->phone)),
            'The service-level budget must not touch the request rate limiter.',
        );

        $this->assertFalse(
            $service->verify($user->phone, OtpPurpose::PASSWORD_RESET, $correct),
            'The correct code must be refused once the attempt budget is spent.',
        );

        $this->assertNotNull(
            OtpCode::query()->where('phone', $user->phone)->value('consumed_at'),
            'An exhausted code must be invalidated rather than left usable.',
        );
    }

    public function test_an_expired_otp_is_rejected(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp');

        $this->travel((int) config('sms.otp.expires_in') + 1)->seconds();

        Livewire::test(ForgotPassword::class)
            ->set('step', 2)
            ->set('phone', $user->phone)
            ->set('code', $recording->lastCode())
            ->call('verifyCode')
            ->assertSet('step', 2)
            ->assertHasErrors('code')
            ->assertSee(self::OTP_INVALID);
    }

    // =========================================================================
    // 4. PASSWORD RESET
    // =========================================================================

    public function test_password_reset_repeats_inside_the_cooldown_are_throttled(): void
    {
        $recording = $this->useRecordingSms();
        $user = $this->customer();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasNoErrors();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasErrors('phone')
            ->assertSee(self::OTP_REQUEST_THROTTLED)
            ->assertSet('step', 1, false);

        $this->assertCount(1, $recording->sent);
    }

    public function test_password_reset_does_not_reveal_whether_the_account_exists(): void
    {
        $recording = $this->useRecordingSms();

        $existing = $this->customer(['phone' => '09120000010']);

        $known = Livewire::test(ForgotPassword::class)
            ->set('phone', $existing->phone)
            ->call('requestOtp')
            ->assertSet('step', 2)
            ->assertSee(self::OTP_GENERIC);

        $unknownHtml = Livewire::test(ForgotPassword::class)
            ->set('phone', '09129999999')
            ->call('requestOtp')
            ->assertSet('step', 2)
            ->assertSee(self::OTP_GENERIC)
            ->html();

        $this->assertStringContainsString(self::OTP_GENERIC, $known->html());
        $this->assertStringNotContainsString('otp_codes', $unknownHtml);

        $this->assertSame(
            1,
            RateLimiter::attempts($this->otpRequestKey('09129999999')),
            'An unknown number is throttled exactly like a known one.',
        );

        // Only the real account received a code.
        $this->assertCount(1, $recording->sent);
        $this->assertSame($existing->phone, $recording->lastPhone());
    }

    public function test_a_blocked_account_gets_no_usable_reset_path(): void
    {
        $recording = $this->useRecordingSms();
        $blocked = $this->customer(['is_active' => false]);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $blocked->phone)
            ->call('requestOtp')
            ->assertSet('step', 2)
            ->assertSee(self::OTP_GENERIC);

        $this->assertNull($recording->lastCode(), 'A blocked account must not receive an OTP.');
        $this->assertDatabaseCount('otp_codes', 0);
    }

    private function otpRequestKey(string $phone): string
    {
        return 'otp.request:'.$phone.':127.0.0.1';
    }

    private function otpVerifyKey(string $phone): string
    {
        return 'otp.verify:'.$phone.':127.0.0.1';
    }
}
