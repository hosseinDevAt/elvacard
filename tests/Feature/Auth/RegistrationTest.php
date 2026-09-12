<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\Traits\UsesRecordingSms;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    use UsesRecordingSms;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_user_can_register_with_valid_otp(): void
    {
        $recording = $this->useRecordingSms();

        $component = Livewire::test(Register::class)
            ->set('phone', '09123456789')
            ->call('requestOtp')
            ->assertSet('step', 2);

        $code = $recording->lastCode();

        $this->assertNotNull($code);
        $this->assertSame('09123456789', $recording->lastPhone());

        $component
            ->set('code', $code)
            ->call('verifyCode')
            ->assertSet('step', 3);

        $component
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'password')
            ->set('passwordConfirmation', 'password')
            ->call('completeRegistration');

        $this->assertAuthenticated();

        $user = User::query()->where('phone', '09123456789')->first();

        $this->assertNotNull($user);
        $this->assertSame('Test', $user->first_name);
        $this->assertSame('User', $user->last_name);
        $this->assertSame('Test User', $user->name);
        $this->assertSame('customer', $user->role);
    }

    public function test_no_user_is_created_before_otp_verification(): void
    {
        $this->useRecordingSms();

        Livewire::test(Register::class)
            ->set('phone', '09123456789')
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_phone_format(): void
    {
        Livewire::test(Register::class)
            ->set('phone', '123')
            ->call('requestOtp')
            ->assertHasErrors(['phone' => 'regex']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_requires_unique_phone(): void
    {
        User::factory()->create(['phone' => '09123456789']);

        Livewire::test(Register::class)
            ->set('phone', '09123456789')
            ->call('requestOtp')
            ->assertHasErrors(['phone' => 'unique']);

        $this->assertSame(1, User::query()->where('phone', '09123456789')->count());
    }

    public function test_registration_rejects_wrong_otp_code(): void
    {
        $recording = $this->useRecordingSms();

        $component = Livewire::test(Register::class)
            ->set('phone', '09123456789')
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertNotNull($recording->lastCode());

        $component
            ->set('code', '111111')
            ->call('verifyCode')
            ->assertHasErrors('code')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_account_is_not_created_without_an_otp_authorized_session(): void
    {
        $this->useRecordingSms();

        $component = Livewire::test(Register::class);

        $component
            ->set('phone', '09123456789')
            ->set('firstName', 'Test')
            ->set('lastName', 'User')
            ->set('password', 'password')
            ->set('passwordConfirmation', 'password')
            ->call('completeRegistration')
            ->assertHasErrors('phone');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_otp_codes_are_rate_limited(): void
    {
        $recording = $this->useRecordingSms();

        $component = Livewire::test(Register::class)
            ->set('phone', '09123456789')
            ->call('requestOtp')
            ->assertSet('step', 2);

        $component
            ->call('requestOtp')
            ->assertHasErrors('phone')
            ->assertSet('step', 2);

        $this->assertDatabaseCount('otp_codes', 1);
        $this->assertCount(1, $recording->sent);
    }
}