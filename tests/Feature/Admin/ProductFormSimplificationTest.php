<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormSimplificationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(): ProductCategory
    {
        return ProductCategory::create([
            'name' => 'اکسسوری لوکس',
            'slug' => 'luxury-accessories-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_neither_product_type_nor_customization_workflow_appear_in_create_or_edit_form(): void
    {
        $category = $this->category();
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'انگشتر هوشمند استیل',
            'slug' => 'smart-ring-'.uniqid(),
            'product_category_id' => $category->id,
            'base_price' => 450000,
            'is_active' => true,
        ]);

        // 1. Create form inspection
        $createTest = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('type', ProductTypeEnum::STANDARD->value)
            ->assertSet('customizationWorkflow', null)
            ->assertDontSeeHtml('wire:model="type"')
            ->assertDontSeeHtml('wire:model="customizationWorkflow"')
            ->assertSee('نام محصول')
            ->assertSee('دسته‌بندی محصول');

        // 2. Edit form inspection
        $editTest = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('type', ProductTypeEnum::STANDARD->value)
            ->assertSet('customizationWorkflow', null)
            ->assertDontSeeHtml('wire:model="type"')
            ->assertDontSeeHtml('wire:model="customizationWorkflow"')
            ->assertSee('نام محصول')
            ->assertSee('دسته‌بندی محصول');
    }

    public function test_ordinary_product_can_be_created_without_type_or_workflow_inputs(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('name', 'جاکلیدی هوشمند NFC')
            ->set('productCategoryId', $category->id)
            ->set('description', 'جاکلیدی هوشمند با متریال استیل ضدزنگ')
            ->set('basePrice', 290000)
            ->set('isActive', true)
            ->set('specifications', [
                ['label' => 'متریال', 'value' => 'استیل ۳۱۶'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'جاکلیدی هوشمند NFC',
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'product_category_id' => $category->id,
            'base_price' => 290000,
            'is_active' => true,
        ]);
    }

    public function test_editing_ordinary_product_preserves_standard_attributes(): void
    {
        $category = $this->category();
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'ساعت فلزی الوا',
            'slug' => 'metal-watch-'.uniqid(),
            'product_category_id' => $category->id,
            'base_price' => 950000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('name', 'ساعت فلزی الوا پرو')
            ->set('basePrice', 1150000)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertSame('ساعت فلزی الوا پرو', $fresh->name);
        $this->assertSame(1150000, (int) $fresh->base_price);
        $this->assertSame(ProductTypeEnum::STANDARD, $fresh->type);
        $this->assertNull($fresh->customization_workflow);
    }
}
