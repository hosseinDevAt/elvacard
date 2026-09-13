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
use App\Services\CartService;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizerWorkflowOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'orchestration-sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح شیر',
            'slug' => 'orchestration-lion',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/orchestration-lion.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    private function createSecondColor(): Color
    {
        return Color::create([
            'name' => 'نقره‌ای',
            'code_hex' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    private function createBankProduct(array $colors = []): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی اورکستر',
            'slug' => 'orchestration-bank-'.uniqid(),
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

    private function createFuelProduct(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت اورکستر',
            'slug' => 'orchestration-fuel-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);
    }

    public function test_bank_workflow_branch_uses_bank_workspace_and_resolves_color(): void
    {
        $product = $this->createBankProduct([$this->color]);

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $product->id]);

        $this->assertInstanceOf(BankCardWorkspace::class, $component->get('bankCard'));
        $this->assertInstanceOf(FuelCardWorkspace::class, $component->get('fuelCard'));
        $this->assertSame('bank_card', $component->get('workflow'));
        $this->assertSame($this->color->id, $component->get('color_id'));
    }

    public function test_fuel_workflow_branch_mounts_fuel_workspace(): void
    {
        $product = $this->createFuelProduct();

        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $product->id]);

        $component->assertStatus(200);
        $this->assertSame('fuel_card', $component->get('workflow'));
        $this->assertInstanceOf(FuelCardWorkspace::class, $component->get('fuelCard'));
        $this->assertSame([], $component->get('fuelCard')->customizationJson());

        $this->assertSame('fuel_card', $product->getRawOriginal('customization_workflow'));
    }

    public function test_bank_step_two_renders_bank_workspace_forms_only(): void
    {
        $product = $this->createBankProduct([$this->color]);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setStep', 2)
            ->assertSee('شماره کارت (۱۶ رقمی)')
            ->assertSee('نام دارنده کارت (لاتین)')
            ->assertSee('متن دلخواه یا جمله اختصاصی (حکاکی پشت)')
            ->assertSee('حکاکی CVV2')
            ->assertSee('حکاکی تاریخ انقضا')
            ->assertDontSee('Fuel customization workspace');
    }

    public function test_bank_workspace_view_renders_bank_fields(): void
    {
        $bankCard = new class
        {
            public string $card_number = '6274051234567890';

            public string $card_holder_name = 'ALI REZA';

            public string $back_text = 'BORN TO LEAD';

            public string $cvv2 = '808';

            public string $expiry_month = '05';

            public string $expiry_year = '29';

            public bool $security_cvv_enabled = true;

            public bool $security_expiry_enabled = true;
        };

        $html = view('livewire.catalog.product-customizer-bank', [
            'bankCard' => $bankCard,
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString('card_number', $html);
        $this->assertStringContainsString('cvv2', $html);
        $this->assertStringContainsString('حکاکی تاریخ انقضا', $html);
        $this->assertStringContainsString('شماره کارت (۱۶ رقمی)', $html);
    }

    public function test_fuel_workspace_view_contains_no_bank_markup(): void
    {
        $html = view('livewire.catalog.product-customizer-fuel')->render();

        $this->assertStringContainsString('Fuel customization workspace', $html);
        $this->assertStringNotContainsString('card_number', $html);
        $this->assertStringNotContainsString('card_holder_name', $html);
        $this->assertStringNotContainsString('cvv', $html);
        $this->assertStringNotContainsString('expiry', $html);
        $this->assertStringNotContainsString('مغناطیسی', $html);
        $this->assertStringNotContainsString('شماره کارت', $html);
        $this->assertStringNotContainsString('نام دارنده کارت', $html);
    }

    public function test_bank_product_page_never_renders_bank_forms_for_fuel(): void
    {
        $fuelProduct = $this->createFuelProduct();
        $bankProduct = $this->createBankProduct([$this->color]);

        $fuelResponse = $this->get(route('catalog.products.show', $fuelProduct->slug));
        $fuelResponse->assertOk();
        $fuelResponse->assertSee('انتخاب طرح لیزر روی کارت');
        $fuelResponse->assertDontSee('حکاکی CVV2');
        $fuelResponse->assertDontSee('مقدار CVV2 واقعی');
        $fuelResponse->assertDontSee('حکاکی تاریخ انقضا');
        $fuelResponse->assertDontSee('شماره کارت (۱۶ رقمی)');

        $bankResponse = $this->get(route('catalog.products.show', $bankProduct->slug));
        $bankResponse->assertOk();
        $bankResponse->assertSee('انتخاب طرح لیزر روی کارت');
    }

    public function test_fuel_color_is_locked_server_side(): void
    {
        $product = $this->createFuelProduct();

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        // Direct instance: the color lock is exercised at the action level
        // against the product row, independent of public hydration.
        $component = new ProductCustomizer;
        $component->product_id = $product->id;
        $component->workflow = CustomizationWorkflowEnum::FUEL_CARD->value;
        $component->color_id = $this->color->id;
        $component->design_id = $this->design->id;
        $component->design_image_id = $this->designImage->id;
        $component->colorPrices = [[
            'color_id' => $this->color->id,
            'name' => $this->color->name,
            'color_hex' => $this->color->code_hex,
            'price' => 450000,
        ]];

        // Any color other than the configured one is rejected outright.
        $component->selectColor(999999);
        $this->assertSame($this->color->id, $component->color_id);
        $this->assertSame($this->design->id, $component->design_id);

        // The configured color remains locked/unchanged even when re-selected.
        $component->selectColor($this->color->id);
        $this->assertSame($this->color->id, $component->color_id);
    }

    public function test_bank_workflow_keeps_multi_color_selection(): void
    {
        $secondColor = $this->createSecondColor();
        $product = $this->createBankProduct([$this->color, $secondColor]);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('selectColor', $secondColor->id)
            ->assertSet('color_id', $secondColor->id);
    }

    public function test_hydrated_workflow_tampering_cannot_change_bank_cart_payload(): void
    {
        $product = $this->createBankProduct([$this->color]);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->set('bankCard.card_number', '6274051234567890')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];

        // addToCart branches on the product row, so a tampered workflow still
        // produces the Bank snapshot - never the empty Fuel boundary.
        $this->assertArrayHasKey('card_number', $customization);
        $this->assertSame('6274051234567890', $customization['card_number']);
        $this->assertNotSame([], $customization);
    }

    public function test_hydrated_workflow_tampering_cannot_change_bank_color_behavior(): void
    {
        $secondColor = $this->createSecondColor();
        $product = $this->createBankProduct([$this->color, $secondColor]);

        // selectColor resolves the authoritative workflow from the product row:
        // a bank product stays multi-color even if the client hydrates fuel.
        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->call('selectColor', $secondColor->id)
            ->assertSet('color_id', $secondColor->id);
    }

    public function test_orchestrator_switches_explicitly_and_uses_literal_view_paths(): void
    {
        $customizerSource = file_get_contents(base_path('app/Livewire/Catalog/ProductCustomizer.php'));
        $bladeSource = file_get_contents(base_path('resources/views/livewire/catalog/product-customizer.blade.php'));

        $this->assertNotFalse($customizerSource);
        $this->assertNotFalse($bladeSource);

        // Explicit, static workflow branches - never dynamic class resolution.
        $this->assertStringContainsString('switch ($this->authoritativeWorkflow())', $customizerSource);
        $this->assertStringContainsString('case CustomizationWorkflowEnum::BANK_CARD->value:', $customizerSource);
        $this->assertStringContainsString('case CustomizationWorkflowEnum::FUEL_CARD->value:', $customizerSource);
        $this->assertStringContainsString('$this->fuelCard->customizationJson()', $customizerSource);

        // Literal, compile-time include paths - never a variable-derived view.
        $this->assertStringContainsString("@include('livewire.catalog.product-customizer-bank')", $bladeSource);
        $this->assertStringContainsString("@include('livewire.catalog.product-customizer-fuel')", $bladeSource);
        $this->assertStringNotContainsString('product-customizer-{{', $bladeSource);
    }
}
