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
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DesignThumbnailContrastTest extends TestCase
{
    use RefreshDatabase;

    private Product $bankProduct;

    private Product $fuelProduct;

    private Color $cardColorBlack;

    private Color $foilColorGold;

    private Color $foilColorWhite;

    private CateDesign $category;

    private Design $bankDesign;

    private DesignImage $goldVariant;

    private DesignImage $whiteVariant;

    private Design $fuelDesign;

    private DesignImage $fuelWhiteVariant;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        // Physical card base color: Black
        $this->cardColorBlack = Color::create([
            'name' => 'مشکی مات',
            'code_hex' => '#1A1A1A',
            'color_code' => '#1A1A1A',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Foil / laser colors
        $this->foilColorGold = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->foilColorWhite = Color::create([
            'name' => 'سفید',
            'code_hex' => '#FFFFFF',
            'color_code' => '#FFFFFF',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Bank Product
        $this->bankProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-metal-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->foilColorGold->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->foilColorWhite->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        // Fuel Product
        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت فلزی سوخت',
            'slug' => 'fuel-metal-card',
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->cardColorBlack->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        $this->category = CateDesign::create([
            'name' => 'دسته‌بندی طرح‌ها',
            'slug' => 'test-designs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Bank Design with Gold and White variants
        $this->bankDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح بانکی پرواز',
            'slug' => 'flight-bank',
            'workspace_visibility' => 'bank',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->goldVariant = DesignImage::create([
            'design_id' => $this->bankDesign->id,
            'color_id' => $this->foilColorGold->id,
            'image_path' => 'designs/flight-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->whiteVariant = DesignImage::create([
            'design_id' => $this->bankDesign->id,
            'color_id' => $this->foilColorWhite->id,
            'image_path' => 'designs/flight-white.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->goldVariant->id,
            'card_color_id' => $this->foilColorGold->id,
            'is_allowed' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->whiteVariant->id,
            'card_color_id' => $this->foilColorWhite->id,
            'is_allowed' => true,
        ]);

        // Fuel Design (white artwork)
        $this->fuelDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح سوخت اختصاصی',
            'slug' => 'fuel-pattern',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->fuelWhiteVariant = DesignImage::create([
            'design_id' => $this->fuelDesign->id,
            'color_id' => $this->foilColorWhite->id,
            'image_path' => 'designs/fuel-pattern-white.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->fuelWhiteVariant->id,
            'card_color_id' => $this->cardColorBlack->id,
            'is_allowed' => true,
        ]);
    }

    public function test_fuel_card_workspace_renders_dark_thumbnail_container_for_white_artwork(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            ->assertSeeHtml('design-thumbnail-dark')
            ->assertSeeHtml('bg-[#172033]')
            ->assertSeeHtml('border-[#334155]')
            ->assertSeeHtml('background-color: #172033; border-color: #334155;')
            ->assertDontSeeHtml('design-thumbnail-light');
    }

    public function test_bank_card_workspace_renders_light_thumbnail_container_for_gold_variant(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ])
            ->set('color_id', $this->foilColorGold->id)
            ->assertSeeHtml('design-thumbnail-light')
            ->assertSeeHtml('bg-[#F8F7F3]')
            ->assertSeeHtml('border-[#DDD9CF]')
            ->assertSeeHtml('background-color: #F8F7F3; border-color: #DDD9CF;')
            ->assertDontSeeHtml('design-thumbnail-dark');
    }

    public function test_bank_card_workspace_dynamically_switches_thumbnail_container_on_color_selection(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        // 1. Initial Gold color selection -> Light thumbnail container
        $component->call('selectColor', $this->foilColorGold->id)
            ->assertSet('color_id', $this->foilColorGold->id)
            ->assertSeeHtml('design-thumbnail-light')
            ->assertDontSeeHtml('design-thumbnail-dark');

        // 2. Switch to White foil color -> Dynamic Dark thumbnail container
        $component->call('selectColor', $this->foilColorWhite->id)
            ->assertSet('color_id', $this->foilColorWhite->id)
            ->assertSeeHtml('design-thumbnail-dark')
            ->assertSeeHtml('bg-[#172033]')
            ->assertSeeHtml('border-[#334155]')
            ->assertSeeHtml('background-color: #172033; border-color: #334155;')
            ->assertDontSeeHtml('design-thumbnail-light');

        // 3. Switch back to Gold -> Reverts to light thumbnail container
        $component->call('selectColor', $this->foilColorGold->id)
            ->assertSet('color_id', $this->foilColorGold->id)
            ->assertSeeHtml('design-thumbnail-light')
            ->assertDontSeeHtml('design-thumbnail-dark');
    }

    public function test_future_generic_design_with_light_artwork_automatically_gets_dark_thumbnail(): void
    {
        // Create an entirely new future color with white/light hex and name
        $futureLightColor = Color::create([
            'name' => 'سفید صدفی آینده',
            'code_hex' => '#FAFAFA',
            'color_code' => '#FAFAFA',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        // Create a new future design with an arbitrary ID (not 6, 7, 8)
        $futureDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح نسل آینده ۲۱۰',
            'slug' => 'future-gen-design',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $futureVariant = DesignImage::create([
            'design_id' => $futureDesign->id,
            'color_id' => $futureLightColor->id,
            'image_path' => 'designs/future-artwork.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $futureVariant->id,
            'card_color_id' => $this->cardColorBlack->id,
            'is_allowed' => true,
        ]);

        // Service catalog verification
        $catalog = app(DesignCatalogService::class)->paginate(
            $this->category->id,
            null,
            null,
            CustomizationWorkflowEnum::FUEL_CARD,
        );

        $futureItem = collect($catalog->items())->firstWhere('id', $futureDesign->id);
        $this->assertNotNull($futureItem);
        $this->assertTrue($futureItem['is_light_artwork'], 'Future generic design with light color must be detected as is_light_artwork = true without hardcoded IDs.');

        // Livewire UI rendering verification
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            ->assertSeeHtml('design-thumbnail-dark')
            ->assertSeeHtml('bg-[#172033]');
    }

    public function test_future_generic_design_with_dark_artwork_retains_light_thumbnail(): void
    {
        // Create a new future dark color
        $futureDarkColor = Color::create([
            'name' => 'سورمه‌ای تیره',
            'code_hex' => '#0A192F',
            'color_code' => '#0A192F',
            'is_active' => true,
            'sort_order' => 100,
        ]);

        $futureDarkDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح تیره شب',
            'slug' => 'future-dark-night',
            'workspace_visibility' => 'bank',
            'is_active' => true,
            'sort_order' => 11,
        ]);

        $futureDarkVariant = DesignImage::create([
            'design_id' => $futureDarkDesign->id,
            'color_id' => $futureDarkColor->id,
            'image_path' => 'designs/dark-night.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $futureDarkVariant->id,
            'card_color_id' => $futureDarkColor->id,
            'is_allowed' => true,
        ]);

        // Service catalog verification
        $catalog = app(DesignCatalogService::class)->paginate(
            $this->category->id,
            $futureDarkColor->id,
            null,
            CustomizationWorkflowEnum::BANK_CARD,
        );

        $darkItem = collect($catalog->items())->firstWhere('id', $futureDarkDesign->id);
        $this->assertNotNull($darkItem);
        $this->assertFalse($darkItem['is_light_artwork'], 'Future dark design must evaluate to is_light_artwork = false.');
    }

    public function test_outer_button_and_other_ui_elements_remain_unchanged(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            // Outer button background is still #EEF2F7
            ->assertSeeHtml('style="background-color: #EEF2F7;"')
            ->assertSeeHtml('bg-[#EEF2F7]');
    }
}
