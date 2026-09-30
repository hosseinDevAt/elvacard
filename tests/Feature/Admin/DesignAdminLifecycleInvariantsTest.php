<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

/**
 * N-Onyx-51 admin-surface lifecycle invariants.
 *
 * These lock the two P2 gaps found by the N-Onyx-50 audit plus the F5 colour
 * check. They are written against the mutation boundary (the public Livewire
 * methods) rather than rendered markup, because the invariant must also hold for
 * a crafted /livewire/update call.
 *
 *   F1 - DesignManager::save() must apply the same workspace-readiness gate the
 *        wizard applies, and must never create an active design.
 *   F2 - Deactivating the last active image, or moving it under another design,
 *        must be refused exactly as deleting it is. The delete paths already did
 *        this through ProductPurchaseabilityService::designImageRemovalBlocker();
 *        the edit and reparent paths did not.
 *   F5 - A compatibility permission may only be granted for an active colour.
 *
 * Every refusal is asserted through the persisted row, which is the observable
 * effect that actually protects the customer. Nothing here asserts runtime
 * purchase authority: the cart, catalog and purchasability services are
 * unchanged by this work.
 */
class DesignAdminLifecycleInvariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(string $suffix = ''): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته'.$suffix,
            'slug' => 'life-cat-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(string $name = 'مشکی', bool $isActive = true): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#111111',
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);
    }

    private function design(CateDesign $category, string $suffix = '', bool $isActive = true): Design
    {
        return Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح'.$suffix,
            'slug' => 'life-design-'.$suffix.uniqid(),
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);
    }

    private function image(Design $design, Color $color, string $suffix = '', bool $isActive = true): DesignImage
    {
        $path = 'designs/life-'.$suffix.uniqid().'.png';
        Storage::disk('public')->put($path, 'fake');

        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);
    }

    private function compatibility(DesignImage $image, Color $color, bool $allowed = true): DesignColorCompatibility
    {
        return DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => $allowed,
        ]);
    }

    private function product(string $type, ?string $workflow, bool $isActive = false): Product
    {
        return Product::create([
            'type' => $type,
            'customization_workflow' => $workflow,
            'name' => 'محصول '.$type.$workflow,
            'slug' => 'life-product-'.$type.$workflow.uniqid(),
            'is_active' => $isActive,
        ]);
    }

    /**
     * A live Bank product whose only purchasable design is $design, visible
     * through $image. Emptying that design's visible set is a real outage, which
     * is what the F2 blocker exists to prevent.
     */
    private function liveDesignWithSingleVisibleImage(): array
    {
        $category = $this->category('بانک');
        $color = $this->color('مشکی بانک');
        $design = $this->design($category, 'بانک');
        $image = $this->image($design, $color, 'بانک');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, true);
        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        return [$product, $color, $design, $category, $image];
    }

    private function saveDesignViaManager(Design $design, string $name, bool $isActive): void
    {
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $design->cate_design_id)
            ->set('name', $name)
            ->set('isActive', $isActive)
            ->call('save');
    }

    // ---------------------------------------------------------------- F1 ----

    public function test_design_manager_cannot_activate_a_design_with_no_image(): void
    {
        $category = $this->category();
        $design = $this->design($category, 'بدون تصویر', false);

        $this->saveDesignViaManager($design, 'طرح بدون تصویر', true);

        $this->assertFalse((bool) $design->fresh()->is_active, 'An invisible design must not be activatable.');
    }

    public function test_design_manager_cannot_activate_a_design_whose_image_is_allowed_for_no_active_color(): void
    {
        $category = $this->category();
        $color = $this->color();
        $design = $this->design($category, 'بدون سازگاری', false);
        $this->image($design, $color, 'بدون سازگاری');

        // An active image exists, but no explicit allowed compatibility row does,
        // so the authoritative readiness rule still reports "not ready".
        $this->assertDatabaseCount('design_color_compatibilities', 0);

        $this->saveDesignViaManager($design, 'طرح بدون سازگاری', true);

        $this->assertFalse((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_cannot_activate_a_design_inside_an_inactive_category(): void
    {
        $category = $this->category('غیرفعال');
        $category->update(['is_active' => false]);
        $color = $this->color();
        $design = $this->design($category, 'دسته غیرفعال', false);
        $image = $this->image($design, $color, 'دسته غیرفعال');
        $this->compatibility($image, $color);

        $this->saveDesignViaManager($design, 'طرح دسته غیرفعال', true);

        $this->assertFalse((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_cannot_activate_a_design_whose_only_allowed_color_is_inactive(): void
    {
        $category = $this->category();
        $color = $this->color('رنگ غیرفعال');
        $design = $this->design($category, 'رنگ غیرفعال', false);
        $image = $this->image($design, $color, 'رنگ غیرفعال');
        $this->compatibility($image, $color);

        // The permission row exists, but on a colour that is no longer active, so
        // it can never satisfy readiness.
        $color->update(['is_active' => false]);

        $this->saveDesignViaManager($design, 'طرح رنگ غیرفعال', true);

        $this->assertFalse((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_can_still_edit_an_active_workspace_ready_design(): void
    {
        [, , $design] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $design->cate_design_id)
            ->set('name', 'طرح بانک ویرایش‌شده')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('طرح بانک ویرایش‌شده', $design->fresh()->name);
        $this->assertTrue((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_creates_a_new_design_inactive_even_when_activation_is_requested(): void
    {
        $category = $this->category();

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', null)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح تازه ساخته‌شده')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $created = Design::query()->where('name', 'طرح تازه ساخته‌شده')->firstOrFail();

        $this->assertFalse(
            (bool) $created->is_active,
            'A brand-new design owns no image, so it must never be born active.'
        );
    }

    public function test_design_manager_still_blocks_deactivating_a_design_a_live_product_depends_on(): void
    {
        [, , $design] = $this->liveDesignWithSingleVisibleImage();

        $this->saveDesignViaManager($design, $design->name, false);

        $this->assertTrue((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_activation_refusal_message_is_deterministic(): void
    {
        $message = (new ReflectionClass(DesignManager::class))->getConstant('NOT_READY_FOR_WORKSPACE');

        // One fixed message, identical to the string DesignWizard::save() flashes
        // for the same condition, so the refusal reads the same on both admin
        // entry points.
        $this->assertSame(
            'برای فعال‌سازی، طرح باید حداقل یک تصویر فعال داشته باشد که برای یک رنگ فعال مجاز شده باشد؛ در غیر این صورت در بخش شخصی‌سازی نمایش داده نمی‌شود.',
            $message
        );
    }

    public function test_design_manager_refusal_is_stable_across_repeated_attempts(): void
    {
        $category = $this->category();
        $design = $this->design($category, 'تکرار', false);

        $this->saveDesignViaManager($design, 'طرح تکرار', true);
        $this->assertFalse((bool) $design->fresh()->is_active);

        $this->saveDesignViaManager($design, 'طرح تکرار', true);
        $this->assertFalse((bool) $design->fresh()->is_active);

        $this->saveDesignViaManager($design, 'طرح تکرار', true);
        $this->assertFalse((bool) $design->fresh()->is_active);
    }

    public function test_design_manager_never_persists_a_name_change_when_activation_is_refused(): void
    {
        $category = $this->category();
        $design = $this->design($category, 'بدون تصویر', false);

        $this->saveDesignViaManager($design, 'نامی که نباید ذخیره شود', true);

        $this->assertNotSame('نامی که نباید ذخیره شود', $design->fresh()->name);
    }

    // ------------------------------------------------------- F2: wizard ----

    public function test_wizard_cannot_deactivate_the_last_active_image_of_a_live_design(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $image->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->set('imageIsActive', false)
            ->call('saveImage');

        $this->assertTrue((bool) $image->fresh()->is_active, 'The last visible image must stay active.');
    }

    public function test_wizard_can_deactivate_an_image_when_another_active_image_remains(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $sibling = $this->image($design, $color, 'دوم');
        $this->compatibility($sibling, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $image->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->set('imageIsActive', false)
            ->call('saveImage')
            ->assertHasNoErrors();

        $this->assertFalse((bool) $image->fresh()->is_active);
        $this->assertTrue((bool) $sibling->fresh()->is_active);
    }

    public function test_wizard_deactivation_refusal_is_stable_across_repeated_attempts(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();

        $component = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $image->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->set('imageIsActive', false);

        $component->call('saveImage');
        $this->assertTrue((bool) $image->fresh()->is_active);

        $component->call('saveImage');
        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    public function test_wizard_does_not_store_an_uploaded_file_when_the_deactivation_is_refused(): void
    {
        Storage::fake('public');

        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $image->id)
            ->set('colorId', $color->id)
            ->set('imageIsActive', false)
            ->set('imageUpload', UploadedFile::fake()->image('blocked.png'))
            ->call('saveImage');

        // The guard must run before the upload is persisted, otherwise a refused
        // save would strand a freshly written file on disk.
        Storage::disk('public')->assertMissing('designs/blocked.png');
        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    public function test_wizard_rejects_a_crafted_cross_design_image_mutation(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $otherDesign = $this->design($this->category('سایر'), 'سایر');
        $foreignImage = $this->image($otherDesign, $color, 'خارجی');

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $foreignImage->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $foreignImage->image_path)
            ->set('imageIsActive', false)
            ->call('saveImage');

        $this->assertTrue(
            (bool) $foreignImage->fresh()->is_active,
            'An image owned by another design must not be editable through this wizard.'
        );
        $this->assertSame((int) $otherDesign->id, (int) $foreignImage->fresh()->design_id);
    }

    public function test_wizard_rejects_a_crafted_cross_design_image_mutation_even_when_harmless(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $otherDesign = $this->design($this->category('دیگر'), 'دیگر');
        $foreignImage = $this->image($otherDesign, $color, 'خارجی دوم');

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $foreignImage->id)
            ->set('colorId', $color->id)
            ->set('imagePath', 'designs/hijacked.png')
            ->call('saveImage');

        $this->assertNotSame('designs/hijacked.png', $foreignImage->fresh()->image_path);
        $this->assertSame((int) $otherDesign->id, (int) $foreignImage->fresh()->design_id);
    }

    public function test_wizard_can_still_edit_metadata_of_the_last_active_image(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('editingImageId', $image->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->set('altText', 'توضیح تازه')
            ->set('imageIsActive', true)
            ->call('saveImage')
            ->assertHasNoErrors();

        $this->assertSame('توضیح تازه', $image->fresh()->alt_text);
        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    // -------------------------------------------- F2: standalone manager ----

    public function test_image_manager_cannot_deactivate_the_last_active_image_of_a_live_design(): void
    {
        [, , , , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('isActive', false)
            ->call('save');

        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    public function test_image_manager_cannot_deactivate_the_last_active_image_via_a_crafted_editing_id(): void
    {
        [, $color, , , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('editingId', $image->id)
            ->set('designId', $image->design_id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->set('isActive', false)
            ->call('save');

        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    public function test_image_manager_can_deactivate_when_another_active_image_remains(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $sibling = $this->image($design, $color, 'دوم');
        $this->compatibility($sibling, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse((bool) $image->fresh()->is_active);
        $this->assertTrue((bool) $sibling->fresh()->is_active);
    }

    public function test_image_manager_can_edit_an_inactive_image(): void
    {
        [, $color, , , $image] = $this->liveDesignWithSingleVisibleImage();
        $inactive = $this->image($image->design, $color, 'غیرفعال', false);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $inactive->id)
            ->set('altText', 'ویرایش تصویر غیرفعال')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ویرایش تصویر غیرفعال', $inactive->fresh()->alt_text);
        $this->assertFalse((bool) $inactive->fresh()->is_active);
    }

    public function test_image_manager_still_allows_a_harmless_active_metadata_edit(): void
    {
        [, , , , $image] = $this->liveDesignWithSingleVisibleImage();

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('sortOrder', 7)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(7, (int) $image->fresh()->sort_order);
        $this->assertTrue((bool) $image->fresh()->is_active);
    }

    // ------------------------------------------------------ F2: reparent ----

    public function test_image_manager_cannot_move_the_last_active_image_out_of_its_design(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $target = $this->design($this->category('هدف'), 'هدف');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('designId', $target->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->call('save');

        $this->assertSame(
            (int) $design->id,
            (int) $image->fresh()->design_id,
            'The move must not strip the source design of its last visible image.'
        );
    }

    public function test_image_manager_can_move_an_image_when_the_source_design_keeps_an_active_image(): void
    {
        [, $color, $design, , $image] = $this->liveDesignWithSingleVisibleImage();
        $sibling = $this->image($design, $color, 'دوم');
        $this->compatibility($sibling, $color);
        $target = $this->design($this->category('هدف'), 'هدف');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('designId', $target->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $image->image_path)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame((int) $target->id, (int) $image->fresh()->design_id);
        $this->assertTrue((bool) $sibling->fresh()->is_active);
    }

    public function test_image_manager_can_move_an_inactive_image_without_being_blocked(): void
    {
        [, $color, , , $image] = $this->liveDesignWithSingleVisibleImage();
        $inactive = $this->image($image->design, $color, 'غیرفعال', false);
        $target = $this->design($this->category('هدف'), 'هدف');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $inactive->id)
            ->set('designId', $target->id)
            ->set('colorId', $color->id)
            ->set('imagePath', $inactive->image_path)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame((int) $target->id, (int) $inactive->fresh()->design_id);
    }

    // -------------------------------------------------------------- F5 ----

    public function test_wizard_refuses_to_grant_compatibility_for_an_inactive_color(): void
    {
        $category = $this->category();
        $color = $this->color('غیرفعال', false);
        $design = $this->design($category, 'رنگ غیرفعال');
        $image = $this->image($design, $this->color('مرجع'), 'رنگ غیرفعال');

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('toggleCompatibility', $image->id, $color->id);

        $this->assertDatabaseCount('design_color_compatibilities', 0);
    }

    public function test_wizard_refuses_to_modify_an_existing_pair_whose_color_is_now_inactive(): void
    {
        $category = $this->category();
        $color = $this->color('رنگ');
        $design = $this->design($category, 'تغییر وضعیت رنگ');
        $image = $this->image($design, $color, 'تغییر وضعیت رنگ');
        $compatibility = $this->compatibility($image, $color);

        // The colour was active when the permission was granted, then deactivated.
        $color->update(['is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('toggleCompatibility', $image->id, $color->id);

        $this->assertTrue(
            (bool) $compatibility->fresh()->is_allowed,
            'The pre-existing row must be left exactly as it was.'
        );
    }

    public function test_wizard_still_grants_compatibility_for_an_active_color(): void
    {
        $category = $this->category();
        $color = $this->color('فعال');
        $design = $this->design($category, 'رنگ فعال');
        $image = $this->image($design, $color, 'رنگ فعال');

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('toggleCompatibility', $image->id, $color->id);

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }
}
