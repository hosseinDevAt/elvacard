<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
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
use Tests\TestCase;

class DesignWizardIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(string $suffix = ''): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته'.$suffix,
            'slug' => 'wizard-cat-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(string $name = 'مشکی'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function design(CateDesign $category, string $suffix = ''): Design
    {
        return Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح'.$suffix,
            'slug' => 'wizard-design-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function image(Design $design, Color $color, string $suffix = ''): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/wizard-'.$suffix.uniqid().'.png',
            'is_active' => true,
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

    private function price(Product $product, Color $color, int $value, bool $isActive = true): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $value,
            'is_active' => $isActive,
        ]);
    }

    private function product(string $type, ?string $workflow, bool $isActive = false): Product
    {
        return Product::create([
            'type' => $type,
            'customization_workflow' => $workflow,
            'name' => 'محصول '.$type.$workflow,
            'slug' => 'wizard-product-'.$type.$workflow.uniqid(),
            'is_active' => $isActive,
        ]);
    }

    private function readyBankProduct(): array
    {
        $category = $this->category('بانک');
        $color = $this->color('مشکی بانک');
        $design = $this->design($category, 'بانک');
        $image = $this->image($design, $color, 'بانک');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, true);
        $this->price($product, $color, 700000);

        return [$product, $color, $design, $category, $image];
    }

    public function test_wizard_deleting_the_last_active_image_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('deleteImage', $image->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
    }

    public function test_wizard_deleting_an_image_required_by_an_active_product_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $extraColor = $this->color('خاکستری');
        $extraImage = $this->image($design, $extraColor, 'اضافی');
        $this->compatibility($extraImage, $extraColor);

        $wizard = Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id]);

        $wizard->call('deleteImage', $image->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_images', ['id' => $image->id]);

        $wizard->call('deleteImage', $extraImage->id);

        $this->assertDatabaseMissing('design_images', ['id' => $extraImage->id]);
    }

    public function test_wizard_removing_the_last_allowed_compatibility_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('toggleCompatibility', $image->id, $color->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }

    public function test_wizard_compatibility_removal_is_permitted_when_another_allowed_image_exists(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $imageB = $this->image($design, $color, 'دوم');
        $this->compatibility($imageB, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('toggleCompatibility', $image->id, $color->id)
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => false,
        ]);
    }

    public function test_wizard_deactivation_is_rejected_when_active_product_depends_on_it(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('isActive', false)
            ->call('next')
            ->assertSessionMissing('success')
            ->assertSet('step', 1);

        $this->assertTrue($design->fresh()->is_active, 'The design must survive a rejected deactivation.');
    }

    public function test_wizard_deactivation_is_permitted_when_another_purchasable_design_exists(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $designB = $this->design($category, 'دوم');
        $imageB = $this->image($designB, $color, 'دوم');
        $this->compatibility($imageB, $color);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('isActive', false)
            ->call('next')
            ->assertSessionMissing('error')
            ->assertSet('step', 2);

        $this->assertFalse($design->fresh()->is_active);
    }

    public function test_wizard_saving_an_uploaded_image_persists_the_file(): void
    {
        Storage::fake('public');

        $category = $this->category();
        $color = $this->color();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('cateDesignId', $category->id)
            ->set('name', 'طرح دارای آپلود')
            ->call('next')
            ->assertSet('step', 2);

        $design = Design::query()->where('name', 'طرح دارای آپلود')->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('colorId', $color->id)
            ->set('imageUpload', UploadedFile::fake()->image('design.png'))
            ->call('saveImage')
            ->assertHasNoErrors()
            ->assertSet('imageUpload', null);

        $image = DesignImage::query()->where('design_id', $design->id)->firstOrFail();
        $this->assertStringStartsWith('designs/', $image->image_path);
        Storage::disk('public')->assertExists($image->image_path);
    }

    public function test_wizard_replacing_an_image_deletes_the_old_unreferenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/old.png', 'data');

        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $image->update(['image_path' => 'designs/old.png']);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('next')
            ->assertSet('step', 2)
            ->call('editImage', $image->id)
            ->set('imageUpload', UploadedFile::fake()->image('new.png'))
            ->call('saveImage')
            ->assertHasNoErrors();

        $fresh = $image->fresh();
        $this->assertNotSame('designs/old.png', $fresh->image_path);
        Storage::disk('public')->assertExists($fresh->image_path);
        Storage::disk('public')->assertMissing('designs/old.png');
    }

    public function test_wizard_replacing_an_image_preserves_a_shared_referenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/shared.png', 'data');

        $category = $this->category();
        $color = $this->color();
        $design = $this->design($category, 'مشترک');
        $imageA = $this->image($design, $color, 'اول');
        $imageB = $this->image($design, $color, 'دوم');
        $imageA->update(['image_path' => 'designs/shared.png']);
        $imageB->update(['image_path' => 'designs/shared.png']);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('next')
            ->call('editImage', $imageA->id)
            ->set('imageUpload', UploadedFile::fake()->image('replacement.png'))
            ->call('saveImage')
            ->assertHasNoErrors();

        $this->assertNotSame('designs/shared.png', $imageA->fresh()->image_path);
        $this->assertSame('designs/shared.png', $imageB->fresh()->image_path);
        Storage::disk('public')->assertExists('designs/shared.png');
        Storage::disk('public')->assertExists($imageA->fresh()->image_path);
    }

    public function test_wizard_rejects_non_image_uploads(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->set('colorId', $color->id)
            ->set('imageUpload', UploadedFile::fake()->create('document.txt', 100))
            ->call('saveImage')
            ->assertHasErrors(['imageUpload' => 'image']);

        $this->assertDatabaseCount('design_images', 1);
    }
}
