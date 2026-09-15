<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\Support\Traits\UsesRecordingSms;
use Tests\TestCase;

class PasswordSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;
    use UsesRecordingSms;

    private function sessionHashFor(User $user): string
    {
        return Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword());
    }

    public function test_password_change_keeps_current_session_and_logs_out_other_sessions(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        // The current device logs in through the real flow.
        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($user);
        $this->get(route('account.dashboard'))->assertOk();

        // A second device still holds the pre-change credential hash.
        $staleHash = $this->sessionHashFor($user);

        // The current device changes its password.
        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(302);

        $this->assertAuthenticatedAs($user);
        $this->get(route('account.dashboard'))->assertOk();

        // The other device is now stale and gets logged out on next request.
        $this->withSession(['password_hash_web' => $staleHash])
            ->get(route('account.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_password_reset_logs_out_previous_sessions_and_keeps_the_reset_session(): void
    {
        $recording = $this->useRecordingSms();

        $user = User::factory()->create([
            'phone' => '09127771111',
            'password' => 'old-password',
        ]);

        // A previous session (e.g. an old browser) holds the pre-reset hash.
        $preResetHash = $this->sessionHashFor($user);

        // The resetting device completes a full password reset.
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
        $this->get(route('account.dashboard'))->assertOk();

        // The pre-reset session is now stale and is logged out on next request.
        $this->withSession(['password_hash_web' => $preResetHash])
            ->get(route('account.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_password_change_keeps_admin_panel_access(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09127772222',
            'password' => 'password',
        ]);

        Livewire::test(Login::class)
            ->set('phone', $admin->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($admin);

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertStatus(302);

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }
}
