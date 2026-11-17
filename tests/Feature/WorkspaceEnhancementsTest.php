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
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use App\Services\FuelCard\FuelCardCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkspaceEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Product $fuelProduct;

    private Product $bankProduct;

    private Color $cardColor;

    private CateDesign $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cardColor = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت فلزی سوخت',
            'slug' => 'fuel-card-test',
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->cardColor->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        $this->bankProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-card-test',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->cardColor->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $this->category = CateDesign::create([
            'name' => 'دسته‌بندی اصلی',
            'slug' => 'main-cat',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Create 15 designs to trigger pagination (12 per page)
        for ($i = 1; $i <= 15; $i++) {
            $design = Design::create([
                'cate_design_id' => $this->category->id,
                'name' => "طرح شماره {$i}",
                'slug' => "design-{$i}",
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $image = DesignImage::create([
                'design_id' => $design->id,
                'color_id' => $this->cardColor->id,
                'image_path' => "designs/design-{$i}.png",
                'is_active' => true,
                'sort_order' => 1,
            ]);

            DesignColorCompatibility::create([
                'design_image_id' => $image->id,
                'card_color_id' => $this->cardColor->id,
                'is_allowed' => true,
            ]);
        }
    }

    /**
     * Requirement 1: Owner Name and Phone Number are optional in Fuel Card workspace.
     */
    public function test_fuel_card_workspace_allows_empty_owner_name_and_phone_number(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            // Leave owner_name and phone_number empty
            ->set('fuelCard.owner_name', '')
            ->set('fuelCard.phone_number', '')
            // Set required vehicle type and chip
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasNoErrors(['fuelCard.owner_name', 'fuelCard.phone_number']);

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $item = $cart['items'][0];
        $this->assertSame('سواری شخصی', $item['customization_json']['vehicle_type']);
        $this->assertArrayNotHasKey('owner_name', $item['customization_json']);
        $this->assertArrayNotHasKey('phone_number', $item['customization_json']);
    }

    public function test_fuel_card_workspace_requires_vehicle_type(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            ->set('fuelCard.owner_name', '')
            ->set('fuelCard.phone_number', '')
            ->set('fuelCard.vehicle_type', '')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.vehicle_type' => 'required']);
    }

    public function test_fuel_card_workspace_labels_indicate_optional_status(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            // Step 2 specifications view
            ->call('setStep', 2)
            ->assertSeeHtml('نام مالک کارت / خودرو')
            ->assertSeeHtml('شماره تلفن همراه')
            // Vehicle type has the required indicator
            ->assertSeeHtml('نوع وسیله نقلیه <span class="text-[#ffde5b]">*</span>');
    }

    /**
     * Requirement 2: Vehicle types contain «کامیون» instead of «کامیونت».
     */
    public function test_vehicle_types_do_not_contain_kamyonet_word(): void
    {
        foreach (FuelCardCustomization::VALID_VEHICLE_TYPES as $type) {
            $this->assertStringNotContainsString('کامیونت', $type);
        }

        $this->assertContains('کامیون کشنده عمومی', FuelCardCustomization::VALID_VEHICLE_TYPES);
        $this->assertContains('کامیون عمومی', FuelCardCustomization::VALID_VEHICLE_TYPES);
    }

    public function test_fuel_card_workspace_dropdown_renders_updated_vehicle_names(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            ->call('setStep', 2)
            ->assertSee('کامیون کشنده عمومی')
            ->assertSee('کامیون عمومی')
            ->assertDontSee('کامیونت');
    }

    /**
     * Requirement 3: Workspace pagination in mobile and desktop.
     */
    public function test_pagination_does_not_render_untranslated_strings(): void
    {
        // 1. Bank card workspace
        $bankTest = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        $bankHtml = $bankTest->html();
        $this->assertStringNotContainsString('pagination.previous', $bankHtml);
        $this->assertStringNotContainsString('pagination.next', $bankHtml);
        $this->assertStringContainsString('صفحه قبل', $bankHtml);
        $this->assertStringContainsString('صفحه بعد', $bankHtml);

        // 2. Fuel card workspace
        $fuelTest = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);

        $fuelHtml = $fuelTest->html();
        $this->assertStringNotContainsString('pagination.previous', $fuelHtml);
        $this->assertStringNotContainsString('pagination.next', $fuelHtml);
        $this->assertStringContainsString('صفحه قبل', $fuelHtml);
        $this->assertStringContainsString('صفحه بعد', $fuelHtml);
    }

    public function test_pagination_does_not_contain_body_scroll_into_view_script(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        $html = $component->html();

        // Confirms scrollIntoView snippet that causes the jump is not present
        $this->assertStringNotContainsString("document.querySelector('body').scrollIntoView()", $html);
        $this->assertStringNotContainsString("closest('body')", $html);
    }

    public function test_pagination_can_navigate_pages_without_errors(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        // Initially on page 1
        $component->assertSet('paginators.page', 1)
            ->assertSee('طرح شماره 1')
            ->call('nextPage')
            ->assertSet('paginators.page', 2)
            ->assertSee('طرح شماره 13')
            ->call('previousPage')
            ->assertSet('paginators.page', 1)
            ->assertSee('طرح شماره 1');
    }
}
