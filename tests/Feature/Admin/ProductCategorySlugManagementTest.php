<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductCategoryManager;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCategorySlugManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Admin can create a category with a manually entered slug.
     */
    public function test_admin_can_create_a_category_with_manually_entered_slug(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->set('name', 'قاب و محافظ')
            ->set('slug', 'cases-and-covers')
            ->set('sortOrder', 2)
            ->call('save')
            ->assertHasNoErrors();

        $category = ProductCategory::query()->where('slug', 'cases-and-covers')->first();
        $this->assertNotNull($category);
        $this->assertSame('قاب و محافظ', $category->name);
        $this->assertSame('cases-and-covers', $category->slug);
    }

    /**
     * 2. Empty slug on creation uses automatic fallback.
     */
    public function test_empty_slug_on_creation_uses_automatic_fallback(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->set('name', 'لوازم دیجیتال')
            ->set('slug', '')
            ->call('save')
            ->assertHasNoErrors();

        $category = ProductCategory::query()->where('name', 'لوازم دیجیتال')->firstOrFail();
        $this->assertNotEmpty($category->slug);
        $this->assertSame(Str::slug('لوازم دیجیتال'), $category->slug);
    }

    /**
     * 3. Editing unrelated fields preserves the existing slug.
     */
    public function test_editing_unrelated_fields_preserves_the_existing_slug(): void
    {
        $category = ProductCategory::create([
            'name' => 'اکسسوری مردانه',
            'slug' => 'mens-accessories',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('edit', $category->id)
            ->assertSet('slug', 'mens-accessories')
            ->set('sortOrder', 10)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('mens-accessories', $category->slug);
        $this->assertSame(10, $category->sort_order);
        $this->assertFalse($category->is_active);
    }

    /**
     * 4. Editing the name does not change the existing slug.
     */
    public function test_editing_the_name_does_not_change_the_existing_slug(): void
    {
        $category = ProductCategory::create([
            'name' => 'نام دسته‌بندی قدیمی',
            'slug' => 'fixed-category-slug',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('edit', $category->id)
            ->assertSet('slug', 'fixed-category-slug')
            ->set('name', 'نام دسته‌بندی جدید و به‌روزشده')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('fixed-category-slug', $category->slug);
        $this->assertSame('نام دسته‌بندی جدید و به‌روزشده', $category->name);
    }

    /**
     * 5. Changing an existing category slug is rejected for now.
     */
    public function test_changing_an_existing_category_slug_is_rejected_with_validation_error(): void
    {
        $category = ProductCategory::create([
            'name' => 'دسته‌بندی اصلی',
            'slug' => 'immutable-slug-original',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('edit', $category->id)
            ->set('slug', 'attempted-new-slug')
            ->call('save')
            ->assertHasErrors(['slug']);

        $category->refresh();
        $this->assertSame('immutable-slug-original', $category->slug);
    }

    /**
     * 6. Duplicate slug is rejected.
     */
    public function test_duplicate_slug_is_rejected(): void
    {
        ProductCategory::create([
            'name' => 'دسته‌بندی اول',
            'slug' => 'first-category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->set('name', 'دسته‌بندی دوم')
            ->set('slug', 'first-category')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, ProductCategory::query()->where('slug', 'first-category')->count());
    }

    /**
     * 7. Invalid characters are rejected.
     */
    public function test_invalid_characters_are_rejected(): void
    {
        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->set('name', 'تست دسته‌بندی')
            ->set('slug', 'نامک-دسته')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Spaces and special characters
        Livewire::actingAs($this->admin())
            ->test(ProductCategoryManager::class)
            ->call('create')
            ->set('name', 'تست دسته‌بندی')
            ->set('slug', 'invalid category slug!')
            ->call('save')
            ->assertHasErrors(['slug']);
    }
}
