<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\User;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The customization workspace only renders an active design that has an active
 * image allowed for an active card color. A design could previously be saved
 * as active while failing every one of those gates, so it silently never
 * appeared for customers. These tests lock the guard that rejects that state
 * and the list badge that makes it visible to the administrator.
 */
class DesignWorkspaceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(bool $active = true): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته '.Str::random(4),
            'slug' => 'vis-cat-'.uniqid(),
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function color(bool $active = true): Color
    {
        return Color::create([
            'name' => 'رنگ '.Str::random(4),
            'code_hex' => '#1a1a1a',
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function design(CateDesign $category, bool $active = true): Design
    {
        return Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح '.Str::random(4),
            'slug' => 'vis-design-'.uniqid(),
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    public function test_wizard_refuses_to_activate_a_design_without_a_ready_image(): void
    {
        $category = $this->category();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح بدون تصویر')
            ->set('isActive', true)
            ->call('next');

        $designId = (int) $component->get('designId');
        $this->assertGreaterThan(0, $designId);

        // The create step persists the skeleton inactive; activation only
        // happens on the final save once readiness is validated.
        $this->assertFalse((bool) Design::find($designId)->is_active);

        $component->call('save')->assertNoRedirect();

        $this->assertFalse((bool) Design::find($designId)->fresh()->is_active);
    }

    public function test_wizard_saves_an_active_design_when_an_image_is_allowed_for_an_active_color(): void
    {
        $category = $this->category();
        $color = $this->color();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح آماده نمایش')
            ->set('isActive', true)
            ->call('next')
            ->set('colorId', $color->id)
            ->set('imagePath', 'designs/workspace-ready.png')
            ->call('saveImage');

        $designId = (int) $component->get('designId');
        $image = DesignImage::query()->where('design_id', $designId)->firstOrFail();

        $component->call('next')
            ->call('next')
            ->call('toggleCompatibility', $image->id, $color->id)
            ->call('next')
            ->call('save')
            ->assertRedirect(route('admin.designs'))
            ->assertSessionHas('success');

        $this->assertTrue((bool) Design::find($designId)->fresh()->is_active);
        $this->assertTrue(app(DesignCatalogService::class)->isReadyForWorkspace($designId));
    }

    public function test_inactive_design_can_be_saved_without_any_image(): void
    {
        $category = $this->category();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح غیرفعال')
            ->call('next');

        $designId = (int) $component->get('designId');

        $component->set('isActive', false)
            ->call('save')
            ->assertRedirect(route('admin.designs'))
            ->assertSessionHas('success');

        $this->assertFalse((bool) Design::find($designId)->fresh()->is_active);
    }

    public function test_readiness_requires_an_active_image_allowed_for_an_active_color(): void
    {
        $catalog = app(DesignCatalogService::class);

        $category = $this->category();
        $color = $this->color();
        $design = $this->design($category);

        // No image at all.
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/readiness.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Image exists but is not allowed for any color.
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        // Fully wired and active.
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // Deactivating the only allowed color hides it again.
        $color->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));

        // Reactivating the color restores it; an inactive image still blocks it.
        $color->update(['is_active' => true]);
        $image->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));

        $image->update(['is_active' => true]);
        $category->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
    }

    public function test_design_manager_list_flags_hidden_and_visible_active_designs(): void
    {
        $hidden = $this->design($this->category(), true);

        $readyCategory = $this->category();
        $color = $this->color();
        $visible = $this->design($readyCategory, true);
        $image = DesignImage::create([
            'design_id' => $visible->id,
            'color_id' => $color->id,
            'image_path' => 'designs/visible.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.designs'))
            ->assertOk()
            ->assertSee('پنهان از میزکار')
            ->assertSee('قابل نمایش در میزکار')
            ->assertSee($hidden->name)
            ->assertSee($visible->name);
    }
}
