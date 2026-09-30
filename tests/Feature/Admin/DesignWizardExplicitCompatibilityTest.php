<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
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
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Compatibility is an explicit administrator decision. DesignImage.color_id is
 * only the image's reference/design color and never implies that the image may
 * be used on that card color; only DesignColorCompatibility rows created by
 * toggleCompatibility() grant an image/color pair. These tests lock the
 * explicit model so no implicit pairing can leak back in.
 */
class DesignWizardExplicitCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::disk('public')->put('designs/not-ready.png', 'fake');
        Storage::disk('public')->put('designs/semantics.png', 'fake');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته '.Str::random(4),
            'slug' => 'explicit-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(): Color
    {
        return Color::create([
            'name' => 'رنگ '.Str::random(4),
            'code_hex' => '#1a1a1a',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function allowedImageIds(int $cardColorId)
    {
        return DesignColorCompatibility::query()
            ->where('card_color_id', $cardColorId)
            ->where('is_allowed', true)
            ->pluck('design_image_id');
    }

    public function test_saving_an_image_creates_no_compatibility_without_an_explicit_admin_choice(): void
    {
        $category = $this->category();
        $color = $this->color();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح بدون سازگاری')
            ->call('next')
            ->set('colorId', $color->id)
            ->set('imagePath', 'designs/no-compat.png')
            ->call('saveImage')
            ->assertHasNoErrors();

        $designId = (int) Design::query()->where('name', 'طرح بدون سازگاری')->value('id');
        $image = DesignImage::query()->where('design_id', $designId)->firstOrFail();

        $this->assertSame((int) $color->id, (int) $image->color_id);
        $this->assertDatabaseCount('design_color_compatibilities', 0);
    }

    public function test_explicit_compatibility_toggle_creates_the_row(): void
    {
        $category = $this->category();
        $color = $this->color();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح با سازگاری صریح')
            ->call('next')
            ->set('colorId', $color->id)
            ->set('imagePath', 'designs/explicit-allow.png')
            ->call('saveImage');

        $designId = (int) Design::query()->where('name', 'طرح با سازگاری صریح')->value('id');
        $image = DesignImage::query()->where('design_id', $designId)->firstOrFail();

        $component->call('toggleCompatibility', $image->id, $color->id)->assertHasNoErrors();

        $this->assertSame(1, DesignColorCompatibility::query()->where('design_image_id', $image->id)->count());
        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }

    public function test_an_explicitly_denied_pair_stays_denied_and_is_never_auto_enabled(): void
    {
        $category = $this->category();
        $color = $this->color();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح ممنوع')
            ->call('next');

        $designId = (int) $component->get('designId');

        $image = DesignImage::create([
            'design_id' => $designId,
            'color_id' => $color->id,
            'image_path' => 'designs/denied.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => false,
        ]);

        $component->call('editImage', $image->id)
            ->call('saveImage')
            ->assertHasNoErrors();

        $this->assertSame(1, DesignColorCompatibility::query()->where('design_image_id', $image->id)->count());
        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => false,
        ]);
    }

    public function test_workspace_visibility_depends_on_explicit_compatibility_not_the_reference_color(): void
    {
        $category = $this->category();
        $color = $this->color();
        $catalog = app(DesignCatalogService::class);

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح غیرمجاز')
            ->set('isActive', true)
            ->call('next')
            ->set('colorId', $color->id)
            ->set('imagePath', 'designs/not-ready.png')
            ->call('saveImage');

        $designId = (int) Design::query()->where('name', 'طرح غیرمجاز')->value('id');
        $image = DesignImage::query()->where('design_id', $designId)->firstOrFail();

        // Active reference color, but no explicit grant: not ready anywhere.
        $this->assertFalse($catalog->isReadyForWorkspace($designId));

        foreach ([CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD] as $workflow) {
            $this->assertFalse($catalog->isDesignInCatalog($designId, $category->id, $this->allowedImageIds((int) $color->id), $workflow));
            $pageIds = array_column($catalog->paginate($category->id, $color->id, $this->allowedImageIds((int) $color->id), $workflow)->items(), 'id');
            $this->assertNotContains((int) $designId, $pageIds);
        }

        // The same explicit model refuses to activate an invisible design:
        // an active-but-unready design is never silently persisted as active.
        $component->call('save')->assertNoRedirect();
        $this->assertFalse((bool) Design::find($designId)->fresh()->is_active);

        // The administrator's explicit grant flips the same design to visible,
        // after which activation is permitted.
        $component->call('toggleCompatibility', $image->id, $color->id)
            ->call('save')
            ->assertRedirect(route('admin.designs'));

        $this->assertTrue((bool) Design::find($designId)->fresh()->is_active);
        $this->assertTrue($catalog->isReadyForWorkspace($designId));

        foreach ([CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD] as $workflow) {
            $this->assertTrue($catalog->isDesignInCatalog($designId, $category->id, $this->allowedImageIds((int) $color->id), $workflow));
            $pageIds = array_column($catalog->paginate($category->id, $color->id, $this->allowedImageIds((int) $color->id), $workflow)->items(), 'id');
            $this->assertContains((int) $designId, $pageIds);
        }
    }

    public function test_image_color_does_not_imply_compatibility_for_that_color(): void
    {
        $category = $this->category();
        $referenceColor = $this->color();
        $allowedColor = $this->color();
        $catalog = app(DesignCatalogService::class);

        $this->assertNotSame((int) $referenceColor->id, (int) $allowedColor->id);

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح چالش رنگ')
            ->call('next')
            ->set('colorId', $referenceColor->id)
            ->set('imagePath', 'designs/semantics.png')
            ->call('saveImage');

        $designId = (int) Design::query()->where('name', 'طرح چالش رنگ')->value('id');
        $image = DesignImage::query()->where('design_id', $designId)->firstOrFail();

        // Reference color stored; compatibility created for neither of them.
        $this->assertSame((int) $referenceColor->id, (int) $image->color_id);
        $this->assertFalse($this->allowedImageIds((int) $referenceColor->id)->contains($image->id));
        $this->assertFalse($this->allowedImageIds((int) $allowedColor->id)->contains($image->id));

        // Explicitly allow only the *other* card color, then finish the
        // wizard normally so the design is active and the catalog gate applies.
        $component->call('toggleCompatibility', $image->id, $allowedColor->id)
            ->call('save')
            ->assertRedirect(route('admin.designs'));

        $this->assertTrue((bool) Design::find($designId)->fresh()->is_active);
        $this->assertTrue($this->allowedImageIds((int) $allowedColor->id)->contains($image->id));
        $this->assertTrue($catalog->isDesignInCatalog($designId, $category->id, $this->allowedImageIds((int) $allowedColor->id), CustomizationWorkflowEnum::BANK_CARD));

        // The image must NOT automatically become available for its reference
        // color merely because image.color_id equals it.
        $this->assertFalse($this->allowedImageIds((int) $referenceColor->id)->contains($image->id));
        $this->assertFalse($catalog->isDesignInCatalog($designId, $category->id, $this->allowedImageIds((int) $referenceColor->id), CustomizationWorkflowEnum::BANK_CARD));
    }
}
