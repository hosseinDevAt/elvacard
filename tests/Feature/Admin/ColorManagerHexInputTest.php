<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ColorManager;
use App\Models\Color;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ColorManagerHexInputTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_save_valid_six_digit_hex_code(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'قرمز شعله‌ای')
            ->set('colorCode', '#FF5733')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'قرمز شعله‌ای',
            'code_hex' => '#FF5733',
        ]);
    }

    public function test_admin_can_save_lowercase_six_digit_hex_and_it_is_normalized_to_uppercase(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'سرمه‌ای تیره')
            ->set('colorCode', '#1a2b3c')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'سرمه‌ای تیره',
            'code_hex' => '#1A2B3C',
        ]);
    }

    public function test_admin_can_save_three_digit_shorthand_hex_and_it_is_normalized_to_canonical_six_digit(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'قرمز خالص')
            ->set('colorCode', '#F00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('colors', [
            'name' => 'قرمز خالص',
            'code_hex' => '#FF0000',
        ]);
    }

    public function test_three_digit_shorthand_normalizes_live_color_code_in_component(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('colorCode', '#fff')
            ->assertSet('colorCode', '#FFFFFF');
    }

    public function test_invalid_hex_codes_fail_validation_without_persisting(): void
    {
        // 1. Missing leading hash
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'رنگ نامعتبر')
            ->set('colorCode', 'FF5733')
            ->call('save')
            ->assertHasErrors(['colorCode']);

        // 2. Non-hex characters
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'رنگ نامعتبر')
            ->set('colorCode', '#ZZZZZZ')
            ->call('save')
            ->assertHasErrors(['colorCode']);

        // 3. Incomplete two-digit code
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'رنگ نامعتبر')
            ->set('colorCode', '#12')
            ->call('save')
            ->assertHasErrors(['colorCode']);

        // 4. Too long (eight characters)
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('create')
            ->set('name', 'رنگ نامعتبر')
            ->set('colorCode', '#1234567')
            ->call('save')
            ->assertHasErrors(['colorCode']);

        $this->assertDatabaseMissing('colors', [
            'name' => 'رنگ نامعتبر',
        ]);
    }

    public function test_admin_can_edit_existing_color_and_update_hex_code(): void
    {
        $color = Color::create([
            'name' => 'خاکستری تیره',
            'code_hex' => '#222222',
            'is_scratched' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('edit', $color->id)
            ->assertSet('colorCode', '#222222')
            ->set('colorCode', '#00AAFF')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('#00AAFF', $color->fresh()->code_hex);
    }
}
