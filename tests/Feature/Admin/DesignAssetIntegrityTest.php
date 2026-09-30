<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CateDesignManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\User;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DesignAssetIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function activeCategory(string $suffix = ''): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته فعال '.$suffix,
            'slug' => 'cat-active-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function inactiveCategory(string $suffix = ''): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته غیرفعال '.$suffix,
            'slug' => 'cat-inactive-'.$suffix.uniqid(),
            'is_active' => false,
            'sort_order' => 2,
        ]);
    }

    private function activeColor(string $name = 'طلایی'): Color
    {
        return Color::create([
            'name' => $name.' '.uniqid(),
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function readyDesign(CateDesign $category, Color $color, string $path = 'designs/test-asset.png'): array
    {
        Storage::disk('public')->put($path, 'sample-binary');

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تست '.uniqid(),
            'slug' => 'design-test-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $compatibility = DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return [$design, $image, $compatibility];
    }

    public function test_workspace_readiness_requires_all_six_invariants(): void
    {
        Storage::fake('public');
        $catalog = app(DesignCatalogService::class);

        $category = $this->activeCategory();
        $color = $this->activeColor();
        $path = 'designs/complete-chain.png';

        [$design, $image, $compat] = $this->readyDesign($category, $color, $path);

        // 1. Initial complete state -> ready
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 2. Physical file missing -> not ready
        Storage::disk('public')->delete($path);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        Storage::disk('public')->put($path, 'restored');
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 3. Inactive design -> not ready
        $design->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        $design->update(['is_active' => true]);
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 4. Inactive category -> not ready
        $category->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        $category->update(['is_active' => true]);
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 5. Inactive image -> not ready
        $image->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        $image->update(['is_active' => true]);
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 6. Disallowed compatibility -> not ready
        $compat->update(['is_allowed' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        $compat->update(['is_allowed' => true]);
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));

        // 7. Inactive card color -> not ready
        $color->update(['is_active' => false]);
        $this->assertFalse($catalog->isReadyForWorkspace($design->id));
        $color->update(['is_active' => true]);
        $this->assertTrue($catalog->isReadyForWorkspace($design->id));
    }

    public function test_design_manager_blocks_moving_active_design_to_inactive_category(): void
    {
        Storage::fake('public');
        $activeCategory = $this->activeCategory('اصلی');
        $inactiveCategory = $this->inactiveCategory('مقصد');
        $color = $this->activeColor();

        [$design] = $this->readyDesign($activeCategory, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $inactiveCategory->id)
            ->set('name', $design->name)
            ->set('isActive', true)
            ->call('save');

        $this->assertSame((int) $activeCategory->id, (int) $design->fresh()->cate_design_id);
    }

    public function test_design_manager_blocks_activating_design_inside_inactive_category(): void
    {
        Storage::fake('public');
        $inactiveCategory = $this->inactiveCategory();
        $color = $this->activeColor();

        $path = 'designs/inactive-cat.png';
        Storage::disk('public')->put($path, 'bytes');

        $design = Design::create([
            'cate_design_id' => $inactiveCategory->id,
            'name' => 'طرح در دسته غیرفعال',
            'slug' => 'design-in-inactive-cat-'.uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $inactiveCategory->id)
            ->set('name', $design->name)
            ->set('isActive', true)
            ->call('save');

        $this->assertFalse((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_evaluates_readiness_against_resulting_reparented_state(): void
    {
        Storage::fake('public');
        $inactiveCategory = $this->inactiveCategory('قدیم');
        $activeCategory = $this->activeCategory('جدید');
        $color = $this->activeColor();

        $path = 'designs/reparent-test.png';
        Storage::disk('public')->put($path, 'bytes');

        // Design is currently inactive inside an inactive category, but has ready image and compatibility
        $design = Design::create([
            'cate_design_id' => $inactiveCategory->id,
            'name' => 'طرح انتقالی',
            'slug' => 'design-reparent-'.uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        // When admin moves it to active category AND activates it, readiness must evaluate against resulting state
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $activeCategory->id)
            ->set('name', 'طرح انتقالی فعال')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $refreshed = $design->fresh();
        $this->assertSame((int) $activeCategory->id, (int) $refreshed->cate_design_id);
        $this->assertTrue((bool) $refreshed->is_active);
    }

    public function test_design_wizard_blocks_moving_active_design_to_inactive_category(): void
    {
        Storage::fake('public');
        $activeCategory = $this->activeCategory();
        $inactiveCategory = $this->inactiveCategory();
        $color = $this->activeColor();

        [$design] = $this->readyDesign($activeCategory, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('cateDesignId', $inactiveCategory->id)
            ->set('isActive', true)
            ->call('save')
            ->assertNoRedirect();

        $this->assertSame((int) $activeCategory->id, (int) $design->fresh()->cate_design_id);
    }

    public function test_cate_design_manager_blocks_deactivating_category_with_active_designs(): void
    {
        Storage::fake('public');
        $category = $this->activeCategory('دارای طرح فعال');
        $color = $this->activeColor();

        [$design] = $this->readyDesign($category, $color);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->set('editingId', $category->id)
            ->set('name', $category->name)
            ->set('isActive', false)
            ->call('save');

        $this->assertTrue((bool) $category->fresh()->is_active);
    }

    public function test_seeded_design_images_physically_exist_on_disk(): void
    {
        $this->seed();

        $disk = Storage::disk('public');
        $seededImages = DesignImage::query()->get();

        $this->assertGreaterThanOrEqual(28, $seededImages->count());

        foreach ($seededImages as $image) {
            $this->assertNotEmpty($image->image_path, "Design image ID {$image->id} has empty path.");
            $this->assertTrue(
                $disk->exists($image->image_path),
                "Design image ID {$image->id} points to missing file: {$image->image_path}"
            );
        }
    }
}
