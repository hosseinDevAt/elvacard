<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\Customization\FuelCardActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FuelCardActivationBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    private DesignColorCompatibility $compatibility;

    private Product $fuelProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'دسته سوخت',
            'slug' => 'fuel-activation-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'مشکی سوخت',
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح خطوط',
            'slug' => 'fuel-activation-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/fuel-activation-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->compatibility = DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت فعال‌سازی',
            'slug' => 'fuel-activation-product-'.uniqid(),
            'base_price' => 450000,
            'is_active' => false,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createColor(string $suffix): Color
    {
        return Color::create([
            'name' => 'رنگ'.$suffix,
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createPrice(Product $product, Color $color, int $price, bool $isActive = true): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    private function makeReady(): Product
    {
        $this->createPrice($this->fuelProduct, $this->color, 480000);

        return $this->fuelProduct;
    }

    private function readinessErrors(Product $product): array
    {
        return FuelCardActivationService::readinessErrors($product->id);
    }

    public function test_fuel_fully_ready_product_has_no_activation_blockers(): void
    {
        $product = $this->makeReady();

        $this->assertSame([], $this->readinessErrors($product));
        $this->assertSame([], FuelCardActivationService::activationBlockers($product->id));
    }

    public function test_fuel_rejected_without_active_color(): void
    {
        $errors = $this->readinessErrors($this->fuelProduct);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('یک رنگ فعال', implode(' ', $errors));
    }

    public function test_fuel_rejected_with_multiple_active_colors(): void
    {
        $this->createPrice($this->fuelProduct, $this->color, 480000);
        $this->createPrice($this->fuelProduct, $this->createColor('B'), 520000);

        $errors = $this->readinessErrors($this->fuelProduct);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('دقیقاً یک رنگ و قیمت فعال', implode(' ', $errors));
    }

    public function test_fuel_inactive_extra_color_rows_do_not_block_activation(): void
    {
        $this->createPrice($this->fuelProduct, $this->color, 480000);
        $this->createPrice($this->fuelProduct, $this->createColor('B'), 520000, false);

        $this->assertSame([], $this->readinessErrors($this->fuelProduct));
    }

    public function test_fuel_rejected_when_single_active_color_is_inactive(): void
    {
        $this->makeReady();
        Color::query()->where('id', $this->color->id)->update(['is_active' => false]);

        $errors = $this->readinessErrors($this->fuelProduct);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('رنگ فعال محصول', implode(' ', $errors));
    }

    public function test_fuel_rejected_without_design(): void
    {
        $this->makeReady();
        Design::query()->delete();

        $errors = $this->readinessErrors($this->fuelProduct);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('طرح قابل خرید', implode(' ', $errors));
    }

    public function test_fuel_rejected_when_design_is_inactive(): void
    {
        $this->makeReady();
        Design::query()->where('id', $this->design->id)->update(['is_active' => false]);

        $this->assertNotEmpty($this->readinessErrors($this->fuelProduct));
    }

    public function test_fuel_rejected_when_category_is_inactive(): void
    {
        $this->makeReady();
        CateDesign::query()->where('id', $this->category->id)->update(['is_active' => false]);

        $this->assertNotEmpty($this->readinessErrors($this->fuelProduct));
    }

    public function test_fuel_rejected_when_design_image_is_inactive(): void
    {
        $this->makeReady();
        DesignImage::query()->where('id', $this->designImage->id)->update(['is_active' => false]);

        $this->assertNotEmpty($this->readinessErrors($this->fuelProduct));
    }

    public function test_fuel_rejected_when_compatibility_is_not_allowed(): void
    {
        $this->makeReady();
        DesignColorCompatibility::query()->where('id', $this->compatibility->id)->update(['is_allowed' => false]);

        $this->assertNotEmpty($this->readinessErrors($this->fuelProduct));
    }

    public function test_fuel_rejected_when_stored_workflow_is_not_fuel(): void
    {
        $bankProduct = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی',
            'slug' => 'fuel-activation-bank-'.uniqid(),
            'is_active' => true,
        ]);

        $errors = FuelCardActivationService::readinessErrors($bankProduct->id);

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('کارت سوخت', implode(' ', $errors));
    }

    public function test_fuel_activation_blocked_for_new_product_without_id(): void
    {
        $blockers = FuelCardActivationService::activationBlockers(null);

        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('بدون فعال‌سازی ذخیره کنید', implode(' ', $blockers));
    }

    public function test_fuel_activation_blocked_when_stored_workflow_is_not_fuel(): void
    {
        $bankProduct = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی',
            'slug' => 'fuel-activation-bank-switch-'.uniqid(),
            'is_active' => true,
        ]);

        $blockers = FuelCardActivationService::activationBlockers($bankProduct->id);

        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('فرآیند سوخت منتقل کنید', implode(' ', $blockers));
    }

    public function test_fuel_activation_blocked_for_missing_product(): void
    {
        $blockers = FuelCardActivationService::activationBlockers(999999);

        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('محصول یافت نشد', implode(' ', $blockers));
    }

    public function test_fuel_activation_still_blocked_while_registry_is_inactive(): void
    {
        $this->makeReady();

        $product = $this->fuelProduct;
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::FUEL->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->set('name', $product->name)
            ->set('isActive', true)
            ->call('save');

        $component->assertHasErrors([
            'customizationWorkflow' => 'سرویس کارت سوخت هنوز فعال نشده است؛ محصول قابل فروش نیست و نمی‌تواند فعال ذخیره شود.',
        ]);

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_bank_product_activation_is_unchanged(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی فعال',
            'slug' => 'fuel-activation-bank-ok-'.uniqid(),
            'is_active' => false,
        ]);
        $this->createPrice($product, $this->color, 700000);

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
        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD->value, $product->fresh()->getRawOriginal('customization_workflow'));
    }

    public function test_bank_product_color_price_rules_are_unchanged(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی چند رنگ',
            'slug' => 'fuel-activation-bank-multi-'.uniqid(),
            'is_active' => true,
        ]);

        $colorA = $this->color;
        $colorB = $this->createColor('B');
        $colorC = $this->createColor('C');
        $this->createPrice($product, $colorA, 700000);
        $this->createPrice($product, $colorB, 750000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $product->id)
            ->set('colorId', $colorC->id)
            ->set('price', 800000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_public_fuel_page_remains_unavailable_even_when_fully_ready(): void
    {
        $this->makeReady();

        // The admin gate prevents activating a fuel product, so this DB update
        // simulates a product whose row is already active; the registry still
        // keeps the public purchase closed and the page must show "unavailable".
        $this->fuelProduct->update(['is_active' => true]);

        $response = $this->get(route('catalog.products.show', $this->fuelProduct->slug));

        $response->assertOk();
        $response->assertSee('هنوز قابل خرید نیست');
        $response->assertDontSee('افزودن به سبد خرید');
        $response->assertDontSee('انتخاب طرح لیزر روی کارت');

        Livewire::test(ProductCustomizer::class, ['productId' => $this->fuelProduct->id])
            ->assertStatus(404);
    }

    public function test_public_bank_page_still_mounts_customizer(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی',
            'slug' => 'fuel-activation-bank-page-'.uniqid(),
            'is_active' => true,
        ]);
        $this->createPrice($product, $this->color, 700000);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('انتخاب طرح لیزر روی کارت')
            ->assertDontSee('هنوز قابل خرید نیست');
    }

    public function test_public_commerce_page_still_offers_add_to_cart(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'کالای معمولی',
            'slug' => 'fuel-activation-commerce-'.uniqid(),
            'base_price' => 120000,
            'is_active' => true,
        ]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('افزودن به سبد خرید')
            ->assertDontSee('هنوز قابل خرید نیست');
    }

    public function test_registry_still_excludes_fuel_and_public_purchase_stays_impossible(): void
    {
        $this->assertSame([CustomizationWorkflowEnum::BANK_CARD], CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS);
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));
    }
}
