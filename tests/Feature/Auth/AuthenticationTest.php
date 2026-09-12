<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticated();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_phone_format(): void
    {
        User::factory()->create(['phone' => '09123456789']);

        Livewire::test(Login::class)
            ->set('phone', '12345')
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors(['phone' => 'regex']);

        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'wrong-password')
            ->call('login');

        $this->assertGuest();
    }

    public function test_unknown_phone_is_not_auto_registered(): void
    {
        Livewire::test(Login::class)
            ->set('phone', '09123456789')
            ->set('password', 'password')
            ->call('login');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['phone' => '09123456789']);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}