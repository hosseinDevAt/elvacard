<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Livewire\Forms\BankCardWorkspace;
use App\Livewire\Forms\FuelCardWorkspace;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\BankCard\BankCardCustomization;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\FuelCard\FuelCardCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use ReflectionClass;
use ReflectionProperty;
use Tests\TestCase;

class FuelCardWorkspaceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function createColor(): Color
    {
        return Color::create([
            'name' => 'رنگ تست',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function purchasableDesignPath(Color $color, string $suffix): void
    {
        $category = CateDesign::create([
            'name' => 'دسته تست '.$suffix,
            'slug' => 'fuel-workspace-cat-'.$suffix,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تست '.$suffix,
            'slug' => 'fuel-workspace-design-'.$suffix,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/fuel-workspace-'.$suffix.'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }

    private function createBankProduct(): Product
    {
        $color = $this->createColor();
        $this->purchasableDesignPath($color, 'bank');

        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی تست',
            'slug' => 'fuel-workspace-bank',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        return $product;
    }

    private function createFuelProduct(): Product
    {
        $color = $this->createColor();
        $this->purchasableDesignPath($color, 'fuel');

        $product = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت تست',
            'slug' => 'fuel-workspace-boundary',
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 480000,
            'is_active' => true,
        ]);

        return $product;
    }

    public function test_sanitize_accepts_empty_payload_as_valid(): void
    {
        $this->assertSame([], FuelCardCustomization::sanitize([]));
        $this->assertSame([], FuelCardCustomization::sanitize(['customization_json' => []]));
        $this->assertSame([], FuelCardCustomization::sanitize(['customization_json' => null]));
    }

    public function test_sanitize_strips_unknown_and_bank_keys(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'card_number' => '6274051234567890',
                'cvv2' => '808',
                'expiry_month' => '05',
                'expiry_year' => '29',
                'security_cvv_enabled' => true,
                'card_holder_name' => 'ALI REZA',
                'back_text' => 'TEXT',
                'random_injected_field' => 'hacked',
            ],
        ]);

        $this->assertSame([], $result);
    }

    public function test_product_and_catalog_metadata_cannot_enter_fuel_customization_json(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'product_id' => 1,
                'color_id' => 2,
                'design_id' => 3,
                'design_image_id' => 4,
                'workflow' => 'fuel_card',
                'price' => 800000,
                'product_name' => 'کارت سوخت',
                'color_name' => 'آبی',
                'design_name' => 'طرح تست',
            ],
        ]);

        $this->assertSame([], $result);
    }

    public function test_sanitize_and_json_contract_are_deterministic_and_json_safe(): void
    {
        $payload = ['customization_json' => ['nope' => 1, 'card_number' => '123']];

        $this->assertSame([], FuelCardCustomization::sanitize($payload));
        $this->assertSame([], FuelCardCustomization::sanitize($payload));
        $this->assertSame('[]', json_encode(FuelCardCustomization::sanitize($payload)));
    }

    public function test_rules_and_messages_define_the_fuel_fields(): void
    {
        $this->assertSame(
            ['owner_name', 'car_info', 'vin', 'system_name', 'system_identifier', 'plate_number', 'chip_info'],
            array_keys(FuelCardCustomization::rulesFor())
        );
        $this->assertNotEmpty(FuelCardCustomization::messages());
    }

    public function test_product_customizer_exposes_isolated_fuel_and_bank_workspaces(): void
    {
        $product = $this->createBankProduct();

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $product->id]);

        $fuel = $component->get('fuelCard');
        $bank = $component->get('bankCard');

        $this->assertInstanceOf(FuelCardWorkspace::class, $fuel);
        $this->assertInstanceOf(BankCardWorkspace::class, $bank);
        $this->assertNotInstanceOf(BankCardWorkspace::class, $fuel);
        $this->assertNotInstanceOf(FuelCardWorkspace::class, $bank);
    }

    public function test_fuel_workspace_exposes_only_fuel_fields_and_never_bank_fields(): void
    {
        $product = $this->createBankProduct();

        $fuel = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])->get('fuelCard');

        $this->assertSame(FuelCardCustomization::rulesFor(), $fuel->rules());
        $this->assertSame(FuelCardCustomization::messages(), $fuel->messages());
        $this->assertSame([], $fuel->customizationJson());

        $reflection = new ReflectionClass(FuelCardWorkspace::class);
        $publicProperties = collect(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC)
        )
            ->reject(fn (ReflectionProperty $property) => $property->isStatic())
            ->map(fn (ReflectionProperty $property) => $property->getName())
            ->values()
            ->all();

        $this->assertCount(7, $publicProperties);

        foreach (['owner_name', 'car_info', 'vin', 'system_name', 'system_identifier', 'plate_number', 'chip_info'] as $fuelField) {
            $this->assertContains($fuelField, $publicProperties);
        }

        foreach (['card_number', 'card_holder_name', 'back_text', 'cvv2', 'expiry_month', 'expiry_year', 'security_cvv_enabled', 'security_expiry_enabled'] as $bankField) {
            $this->assertNotContains($bankField, $publicProperties, 'Fuel workspace must never expose any Bank-specific field.');
        }
    }

    public function test_fuel_workspace_customization_json_only_contains_filled_fuel_fields(): void
    {
        $product = $this->createBankProduct();

        $fuel = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])->get('fuelCard');

        $fuel->owner_name = '  علی رضایی ';
        $fuel->vin = 'i-rabcdefgh1234567';
        $fuel->plate_number = '۱۲ م ۳۴۵ ایران';

        $fuel->canonicalize();

        $this->assertSame(
            [
                'owner_name' => 'علی رضایی',
                'vin' => 'IRABCDEFGH1234567',
                'plate_number' => '12 م 345 ایران',
            ],
            $fuel->customizationJson()
        );
    }

    public function test_fuel_boundary_source_has_no_bank_dependency(): void
    {
        $workspaceSource = file_get_contents(base_path('app/Livewire/Forms/FuelCardWorkspace.php'));
        $customizationSource = file_get_contents(base_path('app/Services/FuelCard/FuelCardCustomization.php'));

        $this->assertNotFalse($workspaceSource);
        $this->assertNotFalse($customizationSource);

        $this->assertStringNotContainsString('BankCard', $workspaceSource, 'FuelCardWorkspace must not depend on any Bank card class.');
        $this->assertStringNotContainsString('BankCard', $customizationSource, 'FuelCardCustomization must not depend on any Bank card class.');
    }

    public function test_bank_customization_keeps_behavior_while_fuel_accepts_nothing(): void
    {
        $bankPayload = [
            'customization_json' => [
                'card_number' => '6274 0512 3456 7898',
                'card_holder_name' => ' ALI REZA ',
            ],
        ];

        $bankResult = BankCardCustomization::sanitize($bankPayload);
        $this->assertSame('7898', $bankResult['pan_last4']);
        $this->assertSame('•••• •••• •••• 7898', $bankResult['card_number_masked']);
        $this->assertArrayNotHasKey('card_number', $bankResult);
        $this->assertSame('ALI REZA', $bankResult['card_holder_name']);

        $this->assertSame([], FuelCardCustomization::sanitize($bankPayload), 'The same Bank payload must be entirely rejected by the Fuel boundary.');
    }

    public function test_registry_exposes_both_card_workflows(): void
    {
        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(null));
    }

    public function test_fuel_product_mounts_customizer_after_activation(): void
    {
        $product = $this->createFuelProduct();

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(200)
            ->assertSet('workflow', CustomizationWorkflowEnum::FUEL_CARD->value);

        $this->assertSame('fuel_card', $product->getRawOriginal('customization_workflow'));
    }
}
