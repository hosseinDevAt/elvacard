<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Design;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPopularDesignManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private CateDesign $category;

    private Design $design;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->category = CateDesign::create([
            'name' => 'طرح‌های ورزشی',
            'slug' => 'sports-category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح اسکی آلپاین',
            'slug' => 'ski-alpine',
            'description' => 'توضیحات طرح ورزشی',
            'meta_title' => 'عنوان سئو طرح اسکی',
            'meta_description' => 'توضیحات سئو طرح اسکی',
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 10,
        ]);
    }

    public function test_admin_can_mark_design_as_popular_via_toggle(): void
    {
        $this->assertFalse($this->design->is_popular);

        Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->call('togglePopular', $this->design->id)
            ->assertStatus(200);

        $this->design->refresh();
        $this->assertTrue($this->design->is_popular);
    }

    public function test_admin_can_remove_design_from_popular_via_toggle(): void
    {
        $this->design->update(['is_popular' => true]);
        $this->assertTrue($this->design->fresh()->is_popular);

        Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->call('togglePopular', $this->design->id)
            ->assertStatus(200);

        $this->design->refresh();
        $this->assertFalse($this->design->is_popular);
    }

    public function test_unauthorized_guest_cannot_toggle_popular_membership(): void
    {
        Livewire::test(DesignManager::class)
            ->assertStatus(403);
    }

    public function test_unauthorized_customer_cannot_toggle_popular_membership(): void
    {
        Livewire::actingAs($this->customer)
            ->test(DesignManager::class)
            ->assertStatus(403);
    }

    public function test_hydrate_rejects_unauthorized_action_call(): void
    {
        $component = Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->assertOk();

        \Livewire\Features\SupportTesting\Testable::actingAs($this->customer);

        $component->call('togglePopular', $this->design->id)->assertStatus(403);

        $this->assertFalse($this->design->fresh()->is_popular);
    }

    public function test_toggling_popular_preserves_category_and_unrelated_fields(): void
    {
        $originalCategoryId = $this->design->cate_design_id;
        $originalName = $this->design->name;
        $originalSlug = $this->design->slug;
        $originalDescription = $this->design->description;
        $originalMetaTitle = $this->design->meta_title;
        $originalMetaDescription = $this->design->meta_description;
        $originalIsActive = $this->design->is_active;
        $originalSortOrder = $this->design->sort_order;

        Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->call('togglePopular', $this->design->id)
            ->assertStatus(200);

        $this->design->refresh();

        $this->assertTrue($this->design->is_popular);
        $this->assertSame($originalCategoryId, $this->design->cate_design_id, 'Category ID must remain unchanged.');
        $this->assertSame($originalName, $this->design->name);
        $this->assertSame($originalSlug, $this->design->slug);
        $this->assertSame($originalDescription, $this->design->description);
        $this->assertSame($originalMetaTitle, $this->design->meta_title);
        $this->assertSame($originalMetaDescription, $this->design->meta_description);
        $this->assertSame($originalIsActive, $this->design->is_active);
        $this->assertSame($originalSortOrder, $this->design->sort_order);
    }

    public function test_admin_can_update_popular_status_via_design_wizard(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DesignWizard::class, ['designId' => $this->design->id])
            ->assertSet('isPopular', false)
            ->set('isPopular', true)
            ->call('next') // Step 1 validation & persist
            ->assertHasNoErrors();

        $this->design->refresh();
        $this->assertTrue($this->design->is_popular);
        $this->assertSame($this->category->id, $this->design->cate_design_id);
    }

    public function test_design_manager_table_renders_popular_status_column_and_badge(): void
    {
        $popularDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح محبوب نمونه',
            'slug' => 'sample-popular',
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->assertSeeHtml('★ پرطرفدار')
            ->assertSeeHtml('عادی')
            ->assertSee($popularDesign->name)
            ->assertSee($this->design->name);
    }
}
