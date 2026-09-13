<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Livewire\Forms\BankCardWorkspace;
use App\Livewire\Forms\FuelCardWorkspace;
use App\Models\Color;
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

    private function createBankProduct(): Product
    {
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
            'color_id' => $this->createColor()->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        return $product;
    }

    private function createFuelProduct(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت تست',
            'slug' => 'fuel-workspace-boundary',
            'base_price' => 450000,
            'is_active' => true,
        ]);
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

    public function test_rules_and_messages_are_empty_until_fuel_fields_are_defined(): void
    {
        $this->assertSame([], FuelCardCustomization::rulesFor());
        $this->assertSame([], FuelCardCustomization::messages());
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

    public function test_fuel_workspace_contract_is_empty_and_bank_free(): void
    {
        $product = $this->createBankProduct();

        $fuel = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])->get('fuelCard');

        $this->assertSame([], $fuel->rules());
        $this->assertSame([], $fuel->messages());
        $this->assertSame([], $fuel->customizationJson());

        $reflection = new ReflectionClass(FuelCardWorkspace::class);
        $publicProperties = array_filter(
            $reflection->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $property) => ! $property->isStatic()
        );

        $this->assertCount(0, $publicProperties, 'Fuel workspace must not leak any Bank-specific or placeholder fields yet.');
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
                'card_number' => '6274 0512 3456 7890',
                'card_holder_name' => ' ALI REZA ',
            ],
        ];

        $bankResult = BankCardCustomization::sanitize($bankPayload);
        $this->assertSame('6274051234567890', $bankResult['card_number']);
        $this->assertSame('ALI REZA', $bankResult['card_holder_name']);

        $this->assertSame([], FuelCardCustomization::sanitize($bankPayload), 'The same Bank payload must be entirely rejected by the Fuel boundary.');
    }

    public function test_registry_still_exposes_only_bank_card(): void
    {
        $this->assertSame([CustomizationWorkflowEnum::BANK_CARD], CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS);
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(null));
    }

    public function test_fuel_product_remains_unavailable_via_customizer(): void
    {
        $product = $this->createFuelProduct();

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);

        $this->assertSame('fuel_card', $product->getRawOriginal('customization_workflow'));
    }
}
