<?php

namespace Tests\Feature\Admin;

use App\Enums\DesignWorkspaceVisibilityEnum;
use App\Livewire\Admin\CateDesignManager;
use App\Models\CateDesign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CateDesignSlugManagementTest extends TestCase
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
     * 1. Admin can create category with a manually entered English slug.
     */
    public function test_admin_can_create_category_with_manually_entered_slug(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'دسته‌بندی مینیمال')
            ->set('slug', 'minimal-art')
            ->set('workspaceVisibility', 'both')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $category = CateDesign::query()->where('slug', 'minimal-art')->first();
        $this->assertNotNull($category);
        $this->assertSame('دسته‌بندی مینیمال', $category->name);
        $this->assertSame('minimal-art', $category->slug);
    }

    /**
     * 2. Empty slug on creation uses the existing automatic fallback.
     */
    public function test_empty_slug_on_creation_uses_automatic_fallback(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'طرح‌های ورزشی')
            ->set('slug', '')
            ->set('workspaceVisibility', 'bank')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $category = CateDesign::query()->where('name', 'طرح‌های ورزشی')->first();
        $this->assertNotNull($category);
        $this->assertNotEmpty($category->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $category->slug);
    }

    /**
     * 3. Manually entered slug is not overwritten by transliteration.
     */
    public function test_manual_slug_is_not_overwritten_by_persian_title(): void
    {
        $persianTitle = 'نمادهای مدرن آینده';
        $transliteratedFinglish = Str::slug($persianTitle);
        $chosenEnglishSlug = 'modern-future-symbols';

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', $persianTitle)
            ->set('slug', $chosenEnglishSlug)
            ->set('workspaceVisibility', 'both')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $category = CateDesign::query()->where('name', $persianTitle)->firstOrFail();
        $this->assertSame($chosenEnglishSlug, $category->slug);
        $this->assertNotSame($transliteratedFinglish, $category->slug);
    }

    /**
     * 4. Editing unrelated fields preserves existing category slug.
     */
    public function test_editing_unrelated_fields_preserves_existing_slug(): void
    {
        $category = CateDesign::create([
            'name' => 'نشان‌های اختصاصی',
            'slug' => 'exclusive-badges',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('edit', $category->id)
            ->assertSet('slug', 'exclusive-badges')
            ->set('workspaceVisibility', 'fuel')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('exclusive-badges', $category->slug);
        $this->assertEquals(DesignWorkspaceVisibilityEnum::FUEL, $category->workspace_visibility);
        $this->assertFalse($category->is_active);
    }

    /**
     * 5. Editing category name preserves existing slug.
     */
    public function test_editing_category_name_preserves_existing_slug(): void
    {
        $category = CateDesign::create([
            'name' => 'عنوان قدیمی دسته',
            'slug' => 'vintage-retro-pack',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('edit', $category->id)
            ->set('name', 'عنوان جدید و ویرایش شده')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('عنوان جدید و ویرایش شده', $category->name);
        $this->assertSame('vintage-retro-pack', $category->slug);
    }

    /**
     * 6. Changing existing category slug is rejected with validation error.
     */
    public function test_changing_existing_category_slug_is_rejected(): void
    {
        $category = CateDesign::create([
            'name' => 'دسته‌بندی ثابت',
            'slug' => 'fixed-category-slug',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('edit', $category->id)
            ->set('slug', 'attempted-new-slug')
            ->call('save')
            ->assertHasErrors(['slug']);

        $category->refresh();
        $this->assertSame('fixed-category-slug', $category->slug);
    }

    /**
     * 7. Duplicate slug is rejected on creation.
     */
    public function test_duplicate_slug_is_rejected(): void
    {
        CateDesign::create([
            'name' => 'دسته اول',
            'slug' => 'shared-slug',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'دسته دوم')
            ->set('slug', 'shared-slug')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, CateDesign::where('slug', 'shared-slug')->count());
    }

    /**
     * 8. Invalid slug formats are rejected.
     */
    public function test_invalid_slug_formats_are_rejected(): void
    {
        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'تست نامک')
            ->set('slug', 'نامک-فارسی')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Spaces and special characters
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'تست نامک')
            ->set('slug', 'invalid slug with spaces!')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Symbols
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'تست نامک')
            ->set('slug', 'invalid@slug#symbols')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    /**
     * 9. Workspace visibility behavior remains intact.
     */
    public function test_workspace_visibility_and_active_state_remain_intact_with_manual_slug(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('create')
            ->set('name', 'دسته سوخت خاص')
            ->set('slug', 'fuel-special-series')
            ->set('workspaceVisibility', 'fuel')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $cat = CateDesign::where('slug', 'fuel-special-series')->first();
        $this->assertNotNull($cat);
        $this->assertEquals(DesignWorkspaceVisibilityEnum::FUEL, $cat->workspace_visibility);
        $this->assertTrue($cat->is_active);
    }
}
