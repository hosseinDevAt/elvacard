<?php

namespace Tests\Feature;

use App\Livewire\Admin\AppearanceManager;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IconManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);

        MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'url',
            'route_key' => 'home',
            'title' => 'خانه',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_default_variant_and_enabled_state_fall_back_when_unset(): void
    {
        $this->assertSame('outline', site_icon_variant('phone'));
        $this->assertTrue(site_icon_enabled('phone'));
        $this->assertSame('outline', site_icon_variant('search'));
        $this->assertTrue(site_icon_enabled('search'));
    }

    public function test_admin_can_change_variant(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->set('iconSettings.phone.variant', 'solid')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'icon.phone',
            'value' => 'solid',
        ]);

        $this->assertSame('solid', site_icon_variant('phone'));
    }

    public function test_invalid_variant_is_rejected(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->set('iconSettings.phone.variant', 'evil-variant')
            ->call('save')
            ->assertHasErrors('iconSettings.phone.variant');

        $this->assertDatabaseMissing('site_settings', ['key' => 'icon.phone']);
    }

    public function test_admin_can_disable_and_reenable_icon(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->set('iconSettings.phone.enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_settings', [
            'key' => 'icon.phone_enabled',
            'value' => '0',
        ]);
        $this->assertFalse(site_icon_enabled('phone'));

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->set('iconSettings.phone.enabled', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(site_icon_enabled('phone'));
    }

    public function test_system_slots_cannot_be_disabled(): void
    {
        SiteSetting::create([
            'key' => 'icon.search_enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'icons',
            'is_public' => true,
        ]);

        $this->assertTrue(site_icon_enabled('search'));
        $this->assertTrue(site_icon_enabled('cart'));
    }

    public function test_tampered_stored_variant_falls_back_to_default(): void
    {
        SiteSetting::create([
            'key' => 'icon.phone',
            'value' => 'script-danger',
            'type' => 'string',
            'group' => 'icons',
            'is_public' => true,
        ]);

        $this->assertSame('outline', site_icon_variant('phone'));
    }

    public function test_reset_icon_removes_overrides(): void
    {
        SiteSetting::create([
            'key' => 'icon.phone',
            'value' => 'solid',
            'type' => 'string',
            'group' => 'icons',
            'is_public' => true,
        ]);
        SiteSetting::create([
            'key' => 'icon.phone_enabled',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'icons',
            'is_public' => true,
        ]);

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->call('resetIcon', 'phone');

        $this->assertDatabaseMissing('site_settings', ['key' => 'icon.phone']);
        $this->assertDatabaseMissing('site_settings', ['key' => 'icon.phone_enabled']);
        $this->assertSame('outline', site_icon_variant('phone'));
        $this->assertTrue(site_icon_enabled('phone'));
    }

    public function test_reset_icon_ignores_unknown_slot(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(AppearanceManager::class)
            ->call('resetIcon', 'does-not-exist');

        $this->assertDatabaseMissing('site_settings', ['key' => 'icon.does-not-exist']);
    }

    public function test_admin_can_access_appearance_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.appearance'))
            ->assertOk();
    }
}