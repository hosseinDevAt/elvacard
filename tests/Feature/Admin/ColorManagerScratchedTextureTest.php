<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ColorManager;
use App\Models\Color;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ColorManagerScratchedTextureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_color_with_scratched_texture_enabled(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'طلایی خش‌دار متالیک')
            ->set('colorCode', '#D4AF37')
            ->set('isScratched', true)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'طلایی خش‌دار متالیک',
            'code_hex' => '#D4AF37',
            'is_scratched' => true,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_create_color_with_scratched_texture_disabled_by_default(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'نقره‌ای ساده')
            ->set('colorCode', '#C0C0C0')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'نقره‌ای ساده',
            'code_hex' => '#C0C0C0',
            'is_scratched' => false,
        ]);
    }

    public function test_admin_can_edit_existing_color_to_toggle_scratched_texture(): void
    {
        $color = Color::create([
            'name' => 'مشکی مات',
            'code_hex' => '#111111',
            'is_scratched' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Toggle to scratched
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('edit', $color->id)
            ->assertSet('isScratched', false)
            ->set('isScratched', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($color->fresh()->is_scratched);

        // Toggle back to smooth
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('edit', $color->id)
            ->assertSet('isScratched', true)
            ->set('isScratched', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($color->fresh()->is_scratched);
    }

    public function test_admin_color_manager_displays_texture_badge_in_table(): void
    {
        Color::create([
            'name' => 'طلایی براش‌خورده',
            'code_hex' => '#FFD700',
            'is_scratched' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Color::create([
            'name' => 'سفید معمولی',
            'code_hex' => '#FFFFFF',
            'is_scratched' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->assertSee('طلایی براش‌خورده')
            ->assertSee('خش‌دار')
            ->assertSee('سفید معمولی')
            ->assertSee('معمولی');
    }

    public function test_admin_color_manager_resets_is_scratched_on_create(): void
    {
        $color = Color::create([
            'name' => 'طلایی خش‌دار',
            'code_hex' => '#FFD700',
            'is_scratched' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('edit', $color->id)
            ->assertSet('isScratched', true)
            ->call('create')
            ->assertSet('isScratched', false);
    }
}
