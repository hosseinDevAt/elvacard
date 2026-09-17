<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductCategoryManager;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategoryManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(string $name = 'لوازم جانبی', array $overrides = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'name' => $name,
            'slug' => 'product-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function commerceProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول فروشگاهی',
            'slug' => 'cat-product-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ], $overrides));
    }

    public function test_admin_can_create_a_category(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('name', 'کیف و کاور')
            ->set('sortOrder', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_categories', [
            'name' => 'کیف و کاور',
            'slug' => Str::slug('کیف و کاور'),
            'is_active' => true,
            'sort_order' => 3,
        ]);
    }

    public function test_admin_can_edit_a_category(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('edit', $category->id)
            ->assertSet('editingId', $category->id)
            ->assertSet('name', $category->name)
            ->set('name', 'دسته ویرایش‌شده')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('دسته ویرایش‌شده', $category->fresh()->name);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = $this->category();
        $this->commerceProduct(['product_category_id' => $category->id]);

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('delete', $category->id);

        $this->assertDatabaseHas('product_categories', ['id' => $category->id]);
    }

    public function test_unused_category_can_be_deleted(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('delete', $category->id);

        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_create_after_edit_starts_a_new_category(): void
    {
        $existing = $this->category('دسته موجود');

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('edit', $existing->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('name', '')
            ->set('name', 'دسته تازه')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_categories', ['name' => 'دسته موجود']);
        $this->assertDatabaseHas('product_categories', ['name' => 'دسته تازه']);
        $this->assertSame(2, ProductCategory::query()->count());
    }

    public function test_ordinary_product_keeps_the_selected_category(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'محصول دسته‌دار')
            ->set('productCategoryId', $category->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'محصول دسته‌دار',
            'product_category_id' => $category->id,
        ]);
    }

    public function test_card_product_never_keeps_a_category(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('name', 'کارت بانکی دسته‌دار')
            ->set('productCategoryId', $category->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'کارت بانکی دسته‌دار',
            'product_category_id' => null,
        ]);
    }

    public function test_customer_cannot_open_the_manager(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.product-categories'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.product-categories'))
            ->assertRedirect(route('login'));
    }
}
