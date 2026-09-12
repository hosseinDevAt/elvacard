<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase I smoke tests for the admin panel access flow.
 *
 * These exercise the real login component, the role-aware redirect, the
 * Admin middleware, and the guest/session behaviour shared by every user.
 */
class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_logs_in_through_real_login_component_and_reaches_dashboard(): void
    {
        $admin = $this->admin();

        Livewire::test(Login::class)
            ->set('phone', $admin->phone)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_customer_login_redirects_to_account_dashboard(): void
    {
        $customer = $this->customer();

        Livewire::test(Login::class)
            ->set('phone', $customer->phone)
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect('/account');

        $this->assertAuthenticatedAs($customer);
    }

    public function test_admin_can_access_admin_dashboard_over_http(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
    }

    public function test_customer_is_denied_admin_dashboard_with_403(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_when_requesting_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->admin();

        Livewire::test(Login::class)
            ->set('phone', '09123456789')
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertGuest();
    }

    public function test_any_authenticated_user_can_logout(): void
    {
        $response = $this->actingAs($this->admin())->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}