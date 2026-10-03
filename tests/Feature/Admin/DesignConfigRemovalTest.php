<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DesignConfigRemovalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_products_table_does_not_contain_design_config_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('products', 'design_config'),
            'Failed asserting that design_config column was removed from products table.'
        );
    }

    public function test_product_model_does_not_include_design_config_in_fillable_or_casts(): void
    {
        $product = new Product();

        $this->assertNotContains('design_config', $product->getFillable());
        $this->assertArrayNotHasKey('design_config', $product->getCasts());
    }

    public function test_design_config_is_absent_from_admin_product_form(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'گردنبند استیل مات',
            'slug' => 'necklace-steel-'.uniqid(),
            'base_price' => 380000,
            'is_active' => true,
        ]);

        // Create form
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertDontSee('پیکربندی طراحی')
            ->assertDontSeeHtml('wire:model="designConfig"');

        // Edit form
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertDontSee('پیکربندی طراحی')
            ->assertDontSeeHtml('wire:model="designConfig"');
    }

    public function test_admin_can_create_and_edit_product_without_design_config(): void
    {
        // 1. Create product
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('name', 'پلاک استیل سفارشی')
            ->set('basePrice', 220000)
            ->set('description', 'توضیحات تست پلاک استیل')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'پلاک استیل سفارشی')->first();
        $this->assertNotNull($product);
        $this->assertSame(220000, (int) $product->base_price);

        // 2. Edit product
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('name', 'پلاک استیل سفارشی طلایی')
            ->set('basePrice', 260000)
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame('پلاک استیل سفارشی طلایی', $product->name);
        $this->assertSame(260000, (int) $product->base_price);
    }
}
