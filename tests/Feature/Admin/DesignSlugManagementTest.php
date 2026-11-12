<?php

namespace Tests\Feature\Admin;

use App\Enums\DesignWorkspaceVisibilityEnum;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DesignSlugManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCategory(string $name = 'طبیعت و حیوانات'): CateDesign
    {
        return CateDesign::create([
            'name' => $name,
            'slug' => 'test-cate-' . uniqid(),
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Admin can create a design with a manually entered slug via DesignWizard.
     */
    public function test_admin_can_create_design_with_manually_entered_slug_via_wizard(): void
    {
        $category = $this->createCategory();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'شیر طلایی')
            ->set('slug', 'golden-lion')
            ->set('workspaceVisibility', 'both')
            ->set('sortOrder', 1)
            ->call('next')
            ->assertHasNoErrors();

        $design = Design::query()->where('slug', 'golden-lion')->first();
        $this->assertNotNull($design);
        $this->assertSame('شیر طلایی', $design->name);
        $this->assertSame('golden-lion', $design->slug);
    }

    /**
     * 2. Admin can create a design with a manually entered slug via DesignManager.
     */
    public function test_admin_can_create_design_with_manually_entered_slug_via_manager(): void
    {
        $category = $this->createCategory();

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'جمجمه سایبری')
            ->set('slug', 'cyber-skull')
            ->set('workspaceVisibility', 'both')
            ->set('sortOrder', 2)
            ->call('save')
            ->assertHasNoErrors();

        $design = Design::query()->where('slug', 'cyber-skull')->first();
        $this->assertNotNull($design);
        $this->assertSame('جمجمه سایبری', $design->name);
        $this->assertSame('cyber-skull', $design->slug);
    }

    /**
     * 3. Empty slug on creation in DesignWizard uses automatic fallback.
     */
    public function test_empty_slug_uses_automatic_fallback_in_wizard(): void
    {
        $category = $this->createCategory();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح عقاب آسمان')
            ->set('slug', '')
            ->set('workspaceVisibility', 'bank')
            ->call('next')
            ->assertHasNoErrors();

        $design = Design::query()->where('name', 'طرح عقاب آسمان')->first();
        $this->assertNotNull($design);
        $this->assertNotEmpty($design->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $design->slug);
    }

    /**
     * 4. Empty slug on creation in DesignManager uses automatic fallback.
     */
    public function test_empty_slug_uses_automatic_fallback_in_manager(): void
    {
        $category = $this->createCategory();

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح کهکشان آبی')
            ->set('slug', '')
            ->set('workspaceVisibility', 'fuel')
            ->call('save')
            ->assertHasNoErrors();

        $design = Design::query()->where('name', 'طرح کهکشان آبی')->first();
        $this->assertNotNull($design);
        $this->assertNotEmpty($design->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $design->slug);
    }

    /**
     * 5. Manually entered slug is not overwritten by Persian title transliteration.
     */
    public function test_manually_entered_slug_is_not_overwritten_by_persian_title(): void
    {
        $category = $this->createCategory();
        $persianTitle = 'طرح اختصاصی الماس سیاه';
        $transliterated = Str::slug($persianTitle);
        $chosenSlug = 'black-diamond-special';

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', $persianTitle)
            ->set('slug', $chosenSlug)
            ->call('next')
            ->assertHasNoErrors();

        $design = Design::query()->where('name', $persianTitle)->firstOrFail();
        $this->assertSame($chosenSlug, $design->slug);
        $this->assertNotSame($transliterated, $design->slug);
    }

    /**
     * 6. Editing title preserves existing slug in DesignManager.
     */
    public function test_editing_title_preserves_existing_slug_in_manager(): void
    {
        $category = $this->createCategory();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'عنوان قدیمی طرح',
            'slug' => 'preserved-manager-slug',
            'workspace_visibility' => 'both',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('edit', $design->id)
            ->set('name', 'عنوان جدید و به‌روزشده')
            ->call('save')
            ->assertHasNoErrors();

        $design->refresh();
        $this->assertSame('عنوان جدید و به‌روزشده', $design->name);
        $this->assertSame('preserved-manager-slug', $design->slug);
    }

    /**
     * 7. Editing title preserves existing slug when editing through DesignWizard.
     */
    public function test_editing_title_preserves_existing_slug_in_wizard(): void
    {
        $category = $this->createCategory();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح اولیه',
            'slug' => 'original-wizard-slug',
            'workspace_visibility' => 'both',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->assertSet('slug', 'original-wizard-slug')
            ->set('name', 'طرح ویرایش شده در ویزارد')
            ->call('next')
            ->assertHasNoErrors();

        $design->refresh();
        $this->assertSame('طرح ویرایش شده در ویزارد', $design->name);
        $this->assertSame('original-wizard-slug', $design->slug);
    }

    /**
     * 8. Editing unrelated fields preserves existing slug.
     */
    public function test_editing_unrelated_fields_preserves_existing_slug(): void
    {
        $category = $this->createCategory();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح مینیمال پایدار',
            'slug' => 'stable-minimal-design',
            'workspace_visibility' => 'both',
            'sort_order' => 5,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('edit', $design->id)
            ->set('sortOrder', 99)
            ->set('workspaceVisibility', 'fuel')
            ->call('save')
            ->assertHasNoErrors();

        $design->refresh();
        $this->assertSame('stable-minimal-design', $design->slug);
        $this->assertSame(99, $design->sort_order);
        $this->assertEquals(DesignWorkspaceVisibilityEnum::FUEL, $design->workspace_visibility);
    }

    /**
     * 9. Duplicate design slug is rejected.
     */
    public function test_duplicate_slug_is_rejected(): void
    {
        $category = $this->createCategory();
        Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح اول',
            'slug' => 'dragon-emblem',
            'workspace_visibility' => 'both',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح دوم')
            ->set('slug', 'dragon-emblem')
            ->call('next')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, Design::where('slug', 'dragon-emblem')->count());
    }

    /**
     * 10. Invalid slug characters are rejected.
     */
    public function test_invalid_slug_characters_are_rejected(): void
    {
        $category = $this->createCategory();

        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'تست نامک')
            ->set('slug', 'اسلاگ-طرح-فارسی')
            ->call('next')
            ->assertHasErrors(['slug']);

        // Spaces
        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'تست نامک')
            ->set('slug', 'invalid slug with spaces')
            ->call('next')
            ->assertHasErrors(['slug']);

        // Symbols
        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'تست نامک')
            ->set('slug', 'invalid@design#symbol')
            ->call('next')
            ->assertHasErrors(['slug']);
    }

    /**
     * 11. Admin can manually update an existing design slug.
     */
    public function test_admin_can_manually_update_existing_design_slug_in_wizard(): void
    {
        $category = $this->createCategory();
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تغییر اسلاگ',
            'slug' => 'initial-design-slug',
            'workspace_visibility' => 'both',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('slug', 'updated-custom-slug')
            ->call('next')
            ->assertHasNoErrors();

        $design->refresh();
        $this->assertSame('updated-custom-slug', $design->slug);
    }
}
