<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class FuelCardFinalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    private Product $fuelProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'امنیت سوخت',
            'slug' => 'fuel-security-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'مس سوخت',
            'code_hex' => '#B87333',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح امن',
            'slug' => 'fuel-security-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/fuel-security-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت امنیتی',
            'slug' => 'fuel-security-product-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'price' => 480000,
            'is_active' => true,
        ]);
    }

    private function addFuelItem(array $overrides = []): array
    {
        return app(CartService::class)->addItem([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            ...$overrides,
        ]);
    }

    private function createSecondColor(): Color
    {
        return Color::create([
            'name' => 'نقره‌ای امنیت',
            'code_hex' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function createBankProduct(): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی امنیت',
            'slug' => 'fuel-security-bank-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_live_registry_exposes_both_card_workflows(): void
    {
        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );

        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(null));
    }

    public function test_ready_fuel_product_is_publicly_purchasable_through_cart_and_order(): void
    {
        $cart = $this->addFuelItem();

        $item = $cart['items'][0];

        $this->assertSame($this->color->id, $item['color_id']);
        $this->assertSame($this->color->name, $item['color_name_snapshot']);
        $this->assertSame($this->design->id, $item['design_id']);
        $this->assertSame($this->designImage->id, $item['design_image_id']);
        $this->assertSame(480000, $item['unit_price_snapshot']);
        $this->assertSame([], $item['customization_json']);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $stored = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::FUEL_CARD, $stored->customization_workflow);
        $this->assertSame(CustomizationWorkflowEnum::FUEL_CARD->value, $stored->getRawOriginal('customization_workflow'));
        $this->assertSame($this->designImage->id, $stored->design_image_id);
        $this->assertSame([], $stored->customization_json);
    }

    public function test_public_customizer_mounts_fuel_workspace_and_never_bank_fields(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->fuelProduct->id])
            ->assertStatus(200)
            ->assertSet('workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->assertSee('انتخاب طرح لیزر روی کارت')
            ->call('setStep', 2)
            ->assertSee('۲. مشخصات کارت سوخت')
            ->assertSee('پیش‌نمایش پشت کارت سوخت')
            ->assertDontSee('حکاکی CVV2')
            ->assertDontSee('شماره کارت (۱۶ رقمی)')
            ->assertDontSee('مقدار CVV2 واقعی')
            ->assertDontSee('حکاکی تاریخ انقضا');
    }

    public function test_client_workflow_tampering_cannot_turn_fuel_into_bank(): void
    {
        $this->addFuelItem([
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_json' => [
                'card_number' => '6274051234567890',
                'card_holder_name' => 'ALI REZA',
                'cvv2' => '808',
                'expiry_month' => '05',
                'expiry_year' => '29',
                'positions' => ['card_number' => ['x' => 0.5, 'y' => 0.5]],
            ],
        ]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $stored = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::FUEL_CARD, $stored->customization_workflow);
        $this->assertSame([], $stored->customization_json);
    }

    public function test_client_color_tampering_is_rejected(): void
    {
        $otherColor = $this->createSecondColor();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected color is not valid');

        $this->addFuelItem(['color_id' => $otherColor->id]);
    }

    public function test_missing_color_resolves_from_database_not_client(): void
    {
        $item = $this->addFuelItem(['color_id' => null])['items'][0];

        $this->assertSame($this->color->id, $item['color_id']);
        $this->assertSame($this->color->name, $item['color_name_snapshot']);
    }

    public function test_multiple_active_colors_are_rejected_on_public_cart(): void
    {
        $otherColor = $this->createSecondColor();

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $otherColor->id,
            'price' => 510000,
            'is_active' => true,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one active color');

        $this->addFuelItem();
    }

    public function test_inactive_design_is_rejected_on_public_cart(): void
    {
        $this->design->update(['is_active' => false]);
        $this->designImage->update(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected design is not available');

        $this->addFuelItem();
    }

    public function test_image_from_another_design_is_rejected_on_public_cart(): void
    {
        $otherDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح دیگر',
            'slug' => 'fuel-security-other-'.uniqid(),
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong to selected design');

        $this->addFuelItem([
            'design_id' => $this->design->id,
            'design_image_id' => DesignImage::create([
                'design_id' => $otherDesign->id,
                'color_id' => $this->color->id,
                'image_path' => 'designs/fuel-security-other-'.uniqid().'.png',
                'is_active' => true,
                'sort_order' => 1,
            ])->id,
        ]);
    }

    public function test_not_allowed_compatibility_is_rejected_on_public_cart(): void
    {
        DesignColorCompatibility::query()->update(['is_allowed' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not compatible');

        $this->addFuelItem();
    }

    public function test_forged_bank_json_on_fuel_is_stripped_to_empty_boundary(): void
    {
        $cart = $this->addFuelItem([
            'customization_json' => [
                'card_number' => '6274051234567890',
                'qr_code_path' => '/tmp/hacked.png',
                'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
                'injected_field' => 'hacked',
            ],
        ]);

        $this->assertSame([], $cart['items'][0]['customization_json']);
    }

    public function test_bank_workflow_is_untouched_by_fuel_activation(): void
    {
        $product = $this->createBankProduct();

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $product->colorPrices()->first()->color_id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'customization_json' => [
                'card_number' => '6274051234567890',
            ],
        ]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $stored = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $stored->customization_workflow);
        $this->assertSame('6274051234567890', $stored->customization_json['card_number']);
    }
}
