<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * N-Onyx-47 — Rate limiting verification.
 *
 * Every protection exercised here already exists in production code. This file
 * only proves the implemented behaviour, using the deployed keys, windows and
 * messages as the source of truth:
 *
 *   login          login:{phone}:{ip}              5 attempts / 120s, cleared on success
 *
 * Guest tracking (throttle:5,1) is proven in SecurityInfrastructureHardeningTest.
 */
class RateLimitingVerificationTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_INVALID = 'شماره تلفن یا رمز عبور صحیح نیست.';

    private const LOGIN_THROTTLED = 'تعداد تلاش‌های ورود بیش از حد مجاز است.';

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
                ->set('phone', $user->phone ?? '09120000007')
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
}
