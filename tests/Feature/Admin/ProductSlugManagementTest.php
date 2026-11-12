<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSlugManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCategory(string $name = 'دسته‌بندی تست'): ProductCategory
    {
        return ProductCategory::create([
            'name' => $name,
            'slug' => 'test-cat-' . uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * 1. Admin can create a product with a manually entered slug.
     */
    public function test_admin_can_create_a_product_with_manually_entered_slug(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'کارت استیل مات طلایی')
            ->set('slug', 'matte-gold-steel-card')
            ->set('basePrice', 250000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('slug', 'matte-gold-steel-card')->first();
        $this->assertNotNull($product);
        $this->assertSame('کارت استیل مات طلایی', $product->name);
        $this->assertSame('matte-gold-steel-card', $product->slug);
    }

    /**
     * 2. Manually entered slug is not replaced by transliteration.
     */
    public function test_manually_entered_slug_is_not_replaced_by_transliteration(): void
    {
        $persianTitle = 'محصول خاص ویژه نقره‌ای';
        $transliteratedFinglish = Str::slug($persianTitle);
        $chosenEnglishSlug = 'silver-exclusive-edition';

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', $persianTitle)
            ->set('slug', $chosenEnglishSlug)
            ->set('basePrice', 150000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('name', $persianTitle)->firstOrFail();
        $this->assertSame($chosenEnglishSlug, $product->slug);
        $this->assertNotSame($transliteratedFinglish, $product->slug);
    }

    /**
     * 3. Empty slug on creation uses the existing automatic fallback.
     */
    public function test_empty_slug_on_creation_uses_automatic_fallback(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'دستبند چرمی مشکی')
            ->set('slug', '')
            ->set('basePrice', 180000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('name', 'دستبند چرمی مشکی')->firstOrFail();
        $this->assertNotEmpty($product->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $product->slug);
        $this->assertSame(Str::slug('دستبند چرمی مشکی'), $product->slug);
    }

    /**
     * 4. Editing product title does not change slug.
     */
    public function test_editing_product_title_does_not_change_slug(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'عنوان اصلی قدیمی',
            'slug' => 'original-fixed-slug',
            'base_price' => 300000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('slug', 'original-fixed-slug')
            ->set('name', 'عنوان کاملاً جدید و بازنویسی شده')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame('original-fixed-slug', $product->slug);
        $this->assertSame('عنوان کاملاً جدید و بازنویسی شده', $product->name);
        $this->assertSame(0, ProductSlugHistory::where('product_id', $product->id)->count());
    }

    /**
     * 5. Editing unrelated fields does not change slug.
     */
    public function test_editing_unrelated_fields_does_not_change_slug(): void
    {
        $category = $this->createCategory();
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'کارت اعتباری لوکس',
            'slug' => 'luxury-credit-card',
            'base_price' => 100000,
            'description' => 'توضیحات اولیه',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('basePrice', 450000)
            ->set('description', 'توضیحات به‌روزشده')
            ->set('productCategoryId', $category->id)
            ->set('metaTitle', 'سئو متاتایتل جدید')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame('luxury-credit-card', $product->slug);
        $this->assertSame(450000, (int) $product->base_price);
        $this->assertSame('توضیحات به‌روزشده', $product->description);
        $this->assertSame($category->id, $product->product_category_id);
    }

    /**
     * 6. Admin can intentionally change an existing product slug.
     */
    public function test_admin_can_intentionally_change_an_existing_product_slug(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'گردنبند فلزی',
            'slug' => 'old-necklace-slug',
            'base_price' => 200000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('slug', 'new-stylish-necklace')
            ->call('save')
            ->assertHasNoErrors();

        $product->refresh();
        $this->assertSame('new-stylish-necklace', $product->slug);
    }

    /**
     * 7. Previous slug history and redirect behavior remain functional.
     */
    public function test_previous_slug_history_and_redirect_behavior_remain_functional(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'کارت طرح شیر',
            'slug' => 'lion-card-v1',
            'base_price' => 150000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('slug', 'lion-card-v2')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_slug_histories', [
            'slug' => 'lion-card-v1',
            'product_id' => $product->id,
        ]);

        $this->get(route('catalog.products.show', 'lion-card-v1'))
            ->assertStatus(301)
            ->assertRedirect(route('catalog.products.show', 'lion-card-v2'));

        $this->get(route('catalog.products.show', 'lion-card-v2'))
            ->assertOk();
    }

    /**
     * 8. Duplicate slug is rejected.
     */
    public function test_duplicate_slug_is_rejected(): void
    {
        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول اول',
            'slug' => 'existing-unique-slug',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'محصول دوم')
            ->set('slug', 'existing-unique-slug')
            ->set('basePrice', 120000)
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, Product::query()->where('slug', 'existing-unique-slug')->count());
    }

    /**
     * 9. Invalid characters are rejected.
     */
    public function test_invalid_characters_are_rejected(): void
    {
        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('name', 'تست نامک')
            ->set('slug', 'نامک-فارسی')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Spaces and special characters
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('name', 'تست نامک')
            ->set('slug', 'invalid slug with spaces!')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    /**
     * 10. Existing slug reservation behavior remains functional.
     */
    public function test_existing_slug_reservation_behavior_remains_functional(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول قابل حذف',
            'slug' => 'permanently-reserved-slug',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        // Delete product via manager
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $product->id);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_slug_histories', [
            'slug' => 'permanently-reserved-slug',
            'product_id' => null,
        ]);

        // Attempting to create a new product with the reserved slug must be rejected
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'محصول متجاوز به نامک رزرو شده')
            ->set('slug', 'permanently-reserved-slug')
            ->set('basePrice', 150000)
            ->call('save')
            ->assertHasErrors(['slug']);

        // Attempting to visit the deleted slug returns 404 (not redirected)
        $this->get(route('catalog.products.show', 'permanently-reserved-slug'))
            ->assertNotFound();
    }
}
