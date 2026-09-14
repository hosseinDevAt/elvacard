<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\CateDesignManager;
use App\Livewire\Admin\ColorManager;
use App\Livewire\Admin\DesignColorCompatibilityManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\Customization\ProductPurchaseabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogIntegrityGuardTest extends TestCase
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
            'slug' => 'guard-cat-'.$suffix.uniqid(),
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
            'slug' => 'guard-design-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function image(Design $design, Color $color, string $suffix = ''): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/guard-'.$suffix.uniqid().'.png',
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

    private function product(string $type, ?string $workflow, bool $isActive = false, ?int $basePrice = null): Product
    {
        return Product::create([
            'type' => $type,
            'customization_workflow' => $workflow,
            'name' => 'محصول '.$type.$workflow,
            'slug' => 'guard-product-'.$type.$workflow.uniqid(),
            'base_price' => $basePrice,
            'is_active' => $isActive,
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

    private function readyBankProduct(bool $isActive = true): array
    {
        $category = $this->category('بانک');
        $color = $this->color('مشکی بانک');
        $design = $this->design($category, 'بانک');
        $image = $this->image($design, $color, 'بانک');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, $isActive);
        $this->price($product, $color, 700000);

        return [$product, $color, $design, $category, $image];
    }

    private function readyFuelProduct(bool $isActive = true): array
    {
        $category = $this->category('سوخت');
        $color = $this->color('مشکی سوخت');
        $design = $this->design($category, 'سوخت');
        $image = $this->image($design, $color, 'سوخت');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::FUEL_CARD->value, $isActive);
        $this->price($product, $color, 480000);

        return [$product, $color, $design, $category, $image];
    }

    public function test_standard_product_cannot_be_activated_without_base_price(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'کالای بدون قیمت')
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('basePrice');

        $this->assertSame(0, Product::count(), 'A rejected activation must not persist the product.');
    }

    public function test_inactive_standard_product_without_base_price_is_allowed(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'پیش‌نویس کالا')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Product::count());
        $this->assertFalse(Product::firstOrFail()->is_active);
    }

    public function test_standard_product_activation_succeeds_with_base_price(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'کالای قیمت‌دار')
            ->set('basePrice', 150000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, Product::count());
        $this->assertTrue(Product::firstOrFail()->is_active);
    }

    public function test_bank_product_cannot_be_activated_without_active_pricing(): void
    {
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('name', $product->name)
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('customizationWorkflow');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_bank_product_cannot_be_activated_without_purchasable_design(): void
    {
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value);
        $this->price($product, $this->color('قرمز'), 650000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('name', $product->name)
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('customizationWorkflow');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_bank_product_activation_succeeds_when_fully_ready(): void
    {
        [$product] = $this->readyBankProduct(false);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('name', $product->name)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_new_active_bank_product_cannot_be_created_at_once(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('name', 'کارت جدید')
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('customizationWorkflow');

        $this->assertSame(0, Product::count());
    }

    public function test_deactivating_the_only_price_of_an_active_product_is_rejected(): void
    {
        [$product, $color] = $this->readyBankProduct();
        $row = $product->colorPrices()->firstOrFail();

        $this->assertNotNull(ProductPurchaseabilityService::priceRowChangeBlocker($product->id, $row->id, $color->id, false));

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('editingId', $row->id)
            ->set('productId', $product->id)
            ->set('colorId', $color->id)
            ->set('price', 700000)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertTrue($row->fresh()->is_active, 'The only active price must survive a rejected deactivation.');
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_deleting_the_only_price_of_an_active_product_is_rejected(): void
    {
        [$product] = $this->readyBankProduct();
        $row = $product->colorPrices()->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $row->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('product_color_prices', ['id' => $row->id]);
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_deleting_the_only_price_of_an_active_standard_product_without_base_price_is_rejected(): void
    {
        $product = $this->product(ProductTypeEnum::STANDARD->value, null, true);
        $color = $this->color('طلایی');
        $row = $this->price($product, $color, 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $row->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('product_color_prices', ['id' => $row->id]);
    }

    public function test_deleting_a_non_sole_active_price_of_an_active_product_is_permitted(): void
    {
        [$product, $colorA] = $this->readyBankProduct();
        $rowA = $product->colorPrices()->where('color_id', $colorA->id)->firstOrFail();
        $category = $this->category('دوم');
        $colorB = $this->color('نقره‌ای');
        $designB = $this->design($category, 'دوم');
        $imageB = $this->image($designB, $colorB, 'دوم');
        $this->compatibility($imageB, $colorB);
        $rowB = $this->price($product, $colorB, 750000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $rowB->id)
            ->assertSessionMissing('error');

        $this->assertDatabaseMissing('product_color_prices', ['id' => $rowB->id]);
        $this->assertDatabaseHas('product_color_prices', ['id' => $rowA->id]);
    }

    public function test_color_deactivation_is_rejected_when_active_product_depends_on_it(): void
    {
        [$product, $color] = $this->readyBankProduct();

        $this->assertNotNull(ProductPurchaseabilityService::colorDeactivationBlocker($color->id));

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->set('editingId', $color->id)
            ->set('name', $color->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertTrue($color->fresh()->is_active, 'The color must survive a rejected deactivation.');
    }

    public function test_fuel_color_deactivation_is_rejected_when_active_product_depends_on_it(): void
    {
        [$product, $color] = $this->readyFuelProduct();

        $this->assertNotNull(ProductPurchaseabilityService::colorDeactivationBlocker($color->id));

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->set('editingId', $color->id)
            ->set('name', $color->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertTrue($color->fresh()->is_active);
    }

    public function test_color_deactivation_is_permitted_when_another_purchasable_path_exists(): void
    {
        [$product, $colorA] = $this->readyBankProduct();

        $colorB = $this->color('آبی');
        $categoryB = $this->category('مسیر جایگزین');
        $designB = $this->design($categoryB, 'جایگزین');
        $imageB = $this->image($designB, $colorB, 'جایگزین');
        $this->compatibility($imageB, $colorB);
        $this->price($product, $colorB, 900000);

        $this->assertNull(ProductPurchaseabilityService::colorDeactivationBlocker($colorA->id));

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->set('editingId', $colorA->id)
            ->set('name', $colorA->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('error');

        $this->assertFalse($colorA->fresh()->is_active);
    }

    public function test_design_deactivation_is_rejected_when_active_product_depends_on_it(): void
    {
        [$product, $color, $design] = $this->readyBankProduct();

        $this->assertNotNull(ProductPurchaseabilityService::designDeactivationBlocker($design->id));

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $design->cate_design_id)
            ->set('name', $design->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertTrue($design->fresh()->is_active, 'The design must survive a rejected deactivation.');
    }

    public function test_design_deactivation_is_permitted_when_another_purchasable_design_exists(): void
    {
        [$product, $color, $design, $category] = $this->readyBankProduct();
        $designB = $this->design($category, 'دوم');
        $imageB = $this->image($designB, $color, 'دوم');
        $this->compatibility($imageB, $color);

        $this->assertNull(ProductPurchaseabilityService::designDeactivationBlocker($design->id));

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->set('editingId', $design->id)
            ->set('cateDesignId', $design->cate_design_id)
            ->set('name', $design->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('error');

        $this->assertFalse($design->fresh()->is_active);
    }

    public function test_category_deactivation_is_rejected_when_active_product_depends_on_its_design(): void
    {
        [$product, $color, $design, $category] = $this->readyBankProduct();

        $this->assertNotNull(ProductPurchaseabilityService::categoryDeactivationBlocker($category->id));

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->set('editingId', $category->id)
            ->set('name', $category->name)
            ->set('isActive', false)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertTrue($category->fresh()->is_active, 'The category must survive a rejected deactivation.');
    }

    public function test_deleting_the_last_active_design_image_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        $this->assertNotNull(ProductPurchaseabilityService::designImageRemovalBlocker($image->id));

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $image->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
    }

    public function test_deleting_an_image_required_by_an_active_product_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $extraColor = $this->color('خاکستری');
        $extraImage = $this->image($design, $extraColor, 'اضافی');
        $this->compatibility($extraImage, $extraColor);

        $this->assertNotNull(ProductPurchaseabilityService::designImageRemovalBlocker($image->id));
        $this->assertNull(ProductPurchaseabilityService::designImageRemovalBlocker($extraImage->id));

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $image->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
    }

    public function test_removing_the_last_allowed_compatibility_is_rejected(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();

        $this->assertNotNull(ProductPurchaseabilityService::compatibilityRemovalBlocker($image->id, $color->id));

        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->call('toggle', $image->id, $color->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }

    public function test_compatibility_removal_is_permitted_when_another_allowed_image_exists(): void
    {
        [$product, $color, $design, $category, $image] = $this->readyBankProduct();
        $imageB = $this->image($design, $color, 'دوم');
        $this->compatibility($imageB, $color);

        $this->assertNull(ProductPurchaseabilityService::compatibilityRemovalBlocker($image->id, $color->id));

        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->call('toggle', $image->id, $color->id)
            ->assertSessionMissing('error');

        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => false,
        ]);
    }
}
