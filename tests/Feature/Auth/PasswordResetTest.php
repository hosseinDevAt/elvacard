<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Support\Traits\UsesRecordingSms;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;
    use UsesRecordingSms;

    public function test_reset_password_request_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_otp_is_sent_to_a_registered_phone(): void
    {
        $recording = $this->useRecordingSms();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertSame($user->phone, $recording->lastPhone());
        $this->assertDatabaseHas('otp_codes', ['phone' => $user->phone, 'purpose' => 'password_reset']);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $recording = $this->useRecordingSms();

        $user = User::factory()->create();
        $oldHash = $user->password;

        $component = Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2);

        $component
            ->set('code', $recording->lastCode())
            ->call('verifyCode')
            ->assertSet('step', 3);

        $component
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('completeReset');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($oldHash, $user->refresh()->password);
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_unknown_phone_does_not_leak_account_existence(): void
    {
        $recording = $this->useRecordingSms();

        Livewire::test(ForgotPassword::class)
            ->set('phone', '09129999999')
            ->call('requestOtp')
            ->assertSet('step', 2)
            ->assertStatus(200);

        $this->assertNull($recording->lastCode());
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_reset_is_blocked_without_an_otp_authorized_session(): void
    {
        $this->useRecordingSms();

        $user = User::factory()->create();

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('completeReset')
            ->assertHasErrors('phone');

        $this->assertGuest();
        $this->assertFalse(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_reset_otp_verification_is_rate_limited(): void
    {
        $recording = $this->useRecordingSms();

        $user = User::factory()->create();

        $component = Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertNotNull($recording->lastCode());

        config(['sms.otp.verify_rate_limit' => 2]);

        $component->set('code', '000000')->call('verifyCode')->assertHasErrors('code');

        $component->set('code', '000000')->call('verifyCode')->assertSet('step', 2);

        $attemptsBefore = (int) \App\Models\OtpCode::query()->where('phone', $user->phone)->value('attempts');

        $component->set('code', '000000')->call('verifyCode');

        $attemptsAfter = (int) \App\Models\OtpCode::query()->where('phone', $user->phone)->value('attempts');

        $this->assertSame($attemptsBefore, $attemptsAfter, 'Rate-limited verify must not reach the OTP verification layer.');
    }
}