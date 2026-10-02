<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordSessionInvalidationTest extends TestCase
{
    use RefreshDatabase;

    private function sessionHashFor(User $user): string
    {
        return Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword());
    }

    public function test_stale_admin_session_is_invalidated_and_redirected_to_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09127772222',
            'password' => 'password',
        ]);

        $staleHash = $this->sessionHashFor($admin);

        // Update admin password directly
        $admin->update(['password' => Hash::make('new-password')]);

        // Stale session attempts to access dashboard
        $this->actingAs($admin)
            ->withSession([
                'password_hash_web' => $staleHash,
                'password_owner_web' => (string) $admin->id,
            ])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
