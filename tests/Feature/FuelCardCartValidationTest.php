<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\FuelCard\FuelCardCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use ReflectionMethod;
use Tests\TestCase;

class FuelCardCartValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createColor(string $hex = '#0000FF'): Color
    {
        return Color::create([
            'name' => 'رنگ تست',
            'code_hex' => $hex,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createCategory(): CateDesign
    {
        return CateDesign::create([
            'name' => 'تست',
            'slug' => 'fuel-cart-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createDesign(Color $color, bool $allowed = true): array
    {
        $design = Design::create([
            'cate_design_id' => $this->createCategory()->id,
            'name' => 'طرح تست',
            'slug' => 'fuel-cart-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/fuel-cart-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => $allowed,
        ]);

        return ['design' => $design, 'designImage' => $designImage];
    }

    private function createFuelProduct(array $colorPrices): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت تست',
            'slug' => 'fuel-cart-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);

        foreach ($colorPrices as $colorPrice) {
            ProductColorPrice::create($colorPrice + ['product_id' => $product->id]);
        }

        return $product;
    }

    private function createBankProduct(array $colors): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی تست',
            'slug' => 'fuel-cart-bank-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        foreach ($colors as $index => $color) {
            ProductColorPrice::create([
                'product_id' => $product->id,
                'color_id' => $color->id,
                'price' => 600000 + ($index * 50000),
                'is_active' => true,
            ]);
        }

        return $product;
    }

    private function fuelValidation(Product $product, array $payload): array
    {
        $cartService = app(CartService::class);
        $method = new ReflectionMethod(CartService::class, 'validateCustomizationPayload');

        return $method->invoke(
            $cartService,
            $product,
            CustomizationWorkflowEnum::FUEL_CARD,
            isset($payload['color_id']) && $payload['color_id'] !== '' ? (int) $payload['color_id'] : null,
            isset($payload['design_id']) && $payload['design_id'] !== '' ? (int) $payload['design_id'] : null,
            isset($payload['design_image_id']) && $payload['design_image_id'] !== '' ? (int) $payload['design_image_id'] : null,
            (int) ($payload['quantity'] ?? 1),
            $payload,
            false,
        );
    }

    private function sanitize(?CustomizationWorkflowEnum $workflow, array $payload): array
    {
        $cartService = app(CartService::class);
        $method = new ReflectionMethod(CartService::class, 'sanitizeCustomization');

        return $method->invoke($cartService, $workflow, $payload);
    }

    public function test_fuel_single_active_color_accepted_and_customization_json_is_empty(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $validated = $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274051234567890',
                'cvv2' => '808',
                'card_holder_name' => 'ALI REZA',
            ],
        ]);

        $this->assertSame($product->id, $validated['product_id']);
        $this->assertSame($color->id, $validated['color_id']);
        $this->assertSame($color->name, $validated['color_name_snapshot']);
        $this->assertSame($designData['design']->id, $validated['design_id']);
        $this->assertSame($designData['designImage']->id, $validated['design_image_id']);
        $this->assertSame($designData['designImage']->image_path, $validated['design_image_path_snapshot']);
        $this->assertSame(480000, $validated['unit_price_snapshot']);
        $this->assertSame(480000, $validated['final_price']);
        $this->assertSame([], $validated['customization_json']);
    }

    public function test_fuel_accepted_color_comes_from_database_not_client(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $validated = $this->fuelValidation($product, [
            'product_id' => $product->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);

        $this->assertSame($color->id, $validated['color_id']);
        $this->assertSame($color->name, $validated['color_name_snapshot']);
        $this->assertSame(480000, $validated['unit_price_snapshot']);
    }

    public function test_fuel_zero_active_color_rows_rejected(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one active color');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_inactive_only_color_rows_rejected(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => false],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one active color');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_multiple_active_color_rows_rejected(): void
    {
        $firstColor = $this->createColor();
        $secondColor = $this->createColor('#C0C0C0');
        $designData = $this->createDesign($firstColor);
        $product = $this->createFuelProduct([
            ['color_id' => $firstColor->id, 'price' => 480000, 'is_active' => true],
            ['color_id' => $secondColor->id, 'price' => 510000, 'is_active' => true],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exactly one active color');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $firstColor->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_wrong_client_color_rejected(): void
    {
        $configuredColor = $this->createColor();
        $otherColor = $this->createColor('#FF0000');
        $designData = $this->createDesign($configuredColor);
        $product = $this->createFuelProduct([
            ['color_id' => $configuredColor->id, 'price' => 480000, 'is_active' => true],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected color is not valid');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $otherColor->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_incompatible_design_rejected(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color, allowed: false);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not compatible');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_image_belonging_to_another_design_rejected(): void
    {
        $color = $this->createColor();
        $designDataA = $this->createDesign($color);
        $designDataB = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong to selected design');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designDataA['design']->id,
            'design_image_id' => $designDataB['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_fuel_inactive_design_rejected(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $designData['design']->update(['is_active' => false]);
        $designData['designImage']->update(['is_active' => false]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected design is not available');

        $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_bank_multiple_colors_accepted(): void
    {
        $firstColor = $this->createColor();
        $secondColor = $this->createColor('#C0C0C0');
        $designData = $this->createDesign($secondColor);
        $product = $this->createBankProduct([$firstColor, $secondColor]);

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $secondColor->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274000000000000',
            ],
        ]);

        $item = $cart['items'][0];
        $this->assertSame($secondColor->id, $item['color_id']);
        $this->assertSame($secondColor->name, $item['color_name_snapshot']);
        $this->assertSame(650000, $item['unit_price_snapshot']);
        $this->assertSame('6274000000000000', $item['customization_json']['card_number']);
    }

    public function test_bank_customization_json_unchanged_by_dispatch(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct([$color]);

        $payload = [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274 0512 3456 7890',
                'card_holder_name' => ' ALI REZA ',
                'back_text' => 'BORN TO LEAD',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
                'expiry_year' => '29',
            ],
        ];

        $cart = app(CartService::class)->addItem($payload);

        $this->assertSame(
            BankCardCustomization::sanitize($payload),
            $cart['items'][0]['customization_json'],
            'The CartService dispatch must hand the Bank payload to BankCardCustomization untouched.'
        );
    }

    public function test_bank_card_fields_preserved_through_cart(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct([$color]);

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '۶۲۷۴ ۰۵۱۲ ۳۴۵۶ ۷۸۹۰',
                'card_holder_name' => 'ALI REZA',
                'back_text' => 'TEXT',
                'security_cvv_enabled' => true,
                'cvv2' => '0808',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
                'expiry_year' => '29',
            ],
        ]);

        $customization = $cart['items'][0]['customization_json'];

        $this->assertSame('6274051234567890', $customization['card_number']);
        $this->assertSame('ALI REZA', $customization['card_holder_name']);
        $this->assertSame('TEXT', $customization['back_text']);
        $this->assertTrue($customization['security_cvv_enabled']);
        $this->assertSame('0808', $customization['cvv2']);
        $this->assertTrue($customization['security_expiry_enabled']);
        $this->assertSame('05', $customization['expiry_month']);
        $this->assertSame('29', $customization['expiry_year']);
    }

    public function test_fuel_is_active_and_public_cart_accepts_valid_payload(): void
    {
        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));

        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274051234567890',
                'cvv2' => '808',
            ],
        ]);

        $item = $cart['items'][0];

        $this->assertSame($color->id, $item['color_id']);
        $this->assertSame($color->name, $item['color_name_snapshot']);
        $this->assertSame([], $item['customization_json']);
    }

    public function test_client_workflow_tampering_is_ignored(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct([$color]);

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'customization_json' => [
                'card_number' => '6274000000000000',
                'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            ],
        ]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->customization_workflow);
        $this->assertSame('6274000000000000', $item->customization_json['card_number']);
        $this->assertArrayNotHasKey('customization_workflow', $item->customization_json);
    }

    public function test_fuel_forged_customization_json_is_stripped_to_empty(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createFuelProduct([
            ['color_id' => $color->id, 'price' => 480000, 'is_active' => true],
        ]);

        $validated = $this->fuelValidation($product, [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274051234567890',
                'cvv2' => '808',
                'expiry_month' => '05',
                'qr_code_enabled' => true,
                'positions' => ['card_number' => ['x' => 0.5, 'y' => 0.5]],
                'product_id' => $product->id,
                'color_id' => $color->id,
                'design_id' => $designData['design']->id,
                'design_image_id' => $designData['designImage']->id,
                'customization_workflow' => 'bank_card',
                'injected_field' => 'hacked',
            ],
        ]);

        $this->assertSame([], $validated['customization_json']);
    }

    public function test_bank_forged_customization_json_is_stripped_to_allowlisted_fields(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct([$color]);

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274000000000000',
                'positions' => ['card_number' => ['x' => 0.5, 'y' => 0.5]],
                'qr_code_path' => '/tmp/hacked.png',
                'customization_workflow' => 'fuel_card',
                'injected_field' => 'hacked',
            ],
        ]);

        $this->assertSame(['card_number' => '6274000000000000'], $cart['items'][0]['customization_json']);
    }

    public function test_cart_service_rejects_invalid_payload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid product selection');

        app(CartService::class)->addItem(['product_id' => 0, 'quantity' => 1]);
    }

    public function test_cart_service_rejects_inactive_product(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول غیرفعال',
            'slug' => 'fuel-cart-inactive-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected product is not available');

        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_cart_service_rejects_nonexistent_bank_color(): void
    {
        $color = $this->createColor();
        $otherColor = $this->createColor('#FF0000');
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct([$color]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected color is not valid');

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $otherColor->id,
            'design_id' => $designData['design']->id,
            'design_image_id' => $designData['designImage']->id,
            'quantity' => 1,
        ]);
    }

    public function test_sanitizer_dispatches_explicitly_per_workflow(): void
    {
        $bankPayload = ['customization_json' => ['card_number' => '6274051234567890']];
        $fuelPayload = ['customization_json' => ['card_number' => '6274051234567890']];

        $this->assertSame(BankCardCustomization::sanitize($bankPayload), $this->sanitize(CustomizationWorkflowEnum::BANK_CARD, $bankPayload));
        $this->assertSame([], $this->sanitize(CustomizationWorkflowEnum::FUEL_CARD, $fuelPayload));
        $this->assertSame([], $this->sanitize(null, $fuelPayload));

        $this->assertSame([], FuelCardCustomization::sanitize($fuelPayload));
    }

    public function test_cart_service_dispatches_with_static_switch_not_dynamic_resolution(): void
    {
        $source = file_get_contents(base_path('app/Services/CartService.php'));

        $this->assertNotFalse($source);

        $this->assertStringContainsString('switch ($workflow) {', $source);
        $this->assertStringContainsString('case CustomizationWorkflowEnum::BANK_CARD:', $source);
        $this->assertStringContainsString('return BankCardCustomization::sanitize($payload);', $source);
        $this->assertStringContainsString('case CustomizationWorkflowEnum::FUEL_CARD:', $source);
        $this->assertStringContainsString('return FuelCardCustomization::sanitize($payload);', $source);
        $this->assertStringContainsString('default:', $source);
        $this->assertStringNotContainsString('container->make', $source);
        $this->assertStringNotContainsString('instanceof', $source);
    }
}
