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
use App\Services\Customization\CardPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhiteArtworkPreviewContrastTest extends TestCase
{
    use RefreshDatabase;

    private Product $bankProduct;

    private Product $fuelProduct;

    private Color $cardColorBlack;

    private Color $foilColorGold;

    private Color $foilColorWhite;

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

        // Card physical base color: Black
        $this->cardColorBlack = Color::create([
            'name' => 'مشکی مات',
            'code_hex' => '#1A1A1A',
            'color_code' => '#1A1A1A',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Laser foil colors
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
            'color_id' => $this->cardColorBlack->id,
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

        $category = CateDesign::create([
            'name' => 'دسته‌بندی تست',
            'slug' => 'test-category',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Bank Design with two variants: Gold and White
        $this->bankDesign = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح عقاب بانکی',
            'slug' => 'eagle-bank',
            'workspace_visibility' => 'bank',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->goldVariant = DesignImage::create([
            'design_id' => $this->bankDesign->id,
            'color_id' => $this->foilColorGold->id,
            'image_path' => 'designs/eagle-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->whiteVariant = DesignImage::create([
            'design_id' => $this->bankDesign->id,
            'color_id' => $this->foilColorWhite->id,
            'image_path' => 'designs/eagle-white.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->goldVariant->id,
            'card_color_id' => $this->cardColorBlack->id,
            'is_allowed' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->whiteVariant->id,
            'card_color_id' => $this->cardColorBlack->id,
            'is_allowed' => true,
        ]);

        // Fuel Design (rendered in white only)
        $this->fuelDesign = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح سوخت اختصاصی',
            'slug' => 'fuel-pattern',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
            'sort_order' => 2,
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

    public function test_fuel_card_workspace_always_uses_dark_contrasting_background(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#070e24]');
    }

    public function test_fuel_card_workspace_preserves_dark_background_on_view_toggle_and_step_change(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ])
            // Front preview initially
            ->assertSet('activeView', 'front')
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            // Toggle to back view
            ->call('setActiveView', 'back')
            ->assertSet('activeView', 'back')
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            // Advance to step 2 (specifications)
            ->call('setStep', 2)
            ->assertSet('step', 2)
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"');
    }

    public function test_bank_workspace_preserves_light_background_for_non_white_artwork(): void
    {
        // When Gold variant is active, background should be preserved as #EEF2F7
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ])
            ->call('selectDesign', $this->bankDesign->id)
            ->call('selectDesignImage', $this->goldVariant->id)
            ->assertSet('isDarkPreview', false)
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#EEF2F7]')
            ->assertDontSeeHtml('style="background-color: #070e24;"');
    }

    public function test_bank_workspace_dynamically_switches_to_dark_background_when_white_artwork_selected(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ])
            ->call('selectDesign', $this->bankDesign->id)
            // 1. Gold selected -> light preview
            ->call('selectDesignImage', $this->goldVariant->id)
            ->assertSet('isDarkPreview', false)
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#EEF2F7]')
            ->assertDontSeeHtml('style="background-color: #070e24;"')
            // 2. White selected -> dynamically switches to dark preview without page reload
            ->call('selectDesignImage', $this->whiteVariant->id)
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#070e24]')
            // 3. Switch back to Gold -> reverts to light preview
            ->call('selectDesignImage', $this->goldVariant->id)
            ->assertSet('isDarkPreview', false)
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#EEF2F7]')
            ->assertDontSeeHtml('style="background-color: #070e24;"');
    }

    public function test_bank_workspace_maintains_dark_background_in_both_front_and_back_previews_when_white_selected(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ])
            ->call('selectDesign', $this->bankDesign->id)
            ->call('selectDesignImage', $this->whiteVariant->id)
            ->assertSet('isDarkPreview', true)
            ->assertSet('activeView', 'front')
            ->assertSeeHtml('style="background-color: #070e24;"')
            // Toggle to back view
            ->call('setActiveView', 'back')
            ->assertSet('activeView', 'back')
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            // Advance to Step 2
            ->call('setStep', 2)
            ->assertSet('step', 2)
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"');
    }

    public function test_bank_workspace_dynamically_switches_to_dark_background_when_white_custom_design_uploaded(): void
    {
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->image('white-logo.png', 800, 600);

        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ])
            ->call('setDesignSource', 'custom')
            ->assertSet('designSource', 'custom')
            ->assertSet('isDarkPreview', false)
            ->set('customImage', $fakeFile)
            ->assertSet('isDarkPreview', true)
            ->assertSeeHtml('style="background-color: #070e24;"')
            ->call('removeCustomImage')
            ->assertSet('isDarkPreview', false)
            ->assertSeeHtml('lg:col-span-7 min-w-0 p-6 sm:p-8 bg-[#EEF2F7]');
    }

    public function test_card_presenter_is_light_artwork_unit_checks(): void
    {
        // 1. Color name checks
        $this->assertTrue(CardPresenter::isLightArtwork(colorName: 'سفید'));
        $this->assertTrue(CardPresenter::isLightArtwork(colorName: 'سفید مات'));
        $this->assertTrue(CardPresenter::isLightArtwork(colorName: 'White Laser'));
        $this->assertTrue(CardPresenter::isLightArtwork(colorName: 'نقره‌ای روشن'));

        // 2. Color hex checks
        $this->assertTrue(CardPresenter::isLightArtwork(colorHex: '#FFFFFF'));
        $this->assertTrue(CardPresenter::isLightArtwork(colorHex: '#FAFAFF'));
        $this->assertTrue(CardPresenter::isLightArtwork(colorHex: '#F0F0F0'));

        // Non-white colors must return false
        $this->assertFalse(CardPresenter::isLightArtwork(colorHex: '#FFD700', colorName: 'طلایی'));
        $this->assertFalse(CardPresenter::isLightArtwork(colorHex: '#1A1A1A', colorName: 'مشکی مات'));
        $this->assertFalse(CardPresenter::isLightArtwork(colorHex: '#000080', colorName: 'سرمه‌ای'));
        $this->assertFalse(CardPresenter::isLightArtwork(colorHex: '#DC143C', colorName: 'قرمز'));
        $this->assertFalse(CardPresenter::isLightArtwork(colorHex: '#B87333', colorName: 'مسی'));

        // 3. Filename check
        $this->assertTrue(CardPresenter::isLightArtwork(imagePath: 'designs/tiger-white.png'));
        $this->assertTrue(CardPresenter::isLightArtwork(imagePath: 'designs/pattern_white.png'));
        $this->assertFalse(CardPresenter::isLightArtwork(imagePath: 'designs/tiger-gold.png'));

        // 4. GD pixel inspection on disk (transparent PNG with white lines vs black lines)
        $imWhite = imagecreatetruecolor(100, 100);
        imagesavealpha($imWhite, true);
        $trans = imagecolorallocatealpha($imWhite, 0, 0, 0, 127);
        imagefill($imWhite, 0, 0, $trans);
        $white = imagecolorallocate($imWhite, 255, 255, 255);
        imageline($imWhite, 10, 10, 90, 90, $white);
        ob_start();
        imagepng($imWhite);
        $whitePng = ob_get_clean();
        imagedestroy($imWhite);

        Storage::disk('public')->put('designs/pixel-white.png', $whitePng);
        $this->assertTrue(CardPresenter::isLightArtwork(imagePath: 'designs/pixel-white.png', disk: 'public'));

        $imDark = imagecreatetruecolor(100, 100);
        imagesavealpha($imDark, true);
        imagefill($imDark, 0, 0, $trans);
        $dark = imagecolorallocate($imDark, 30, 30, 30);
        imageline($imDark, 10, 10, 90, 90, $dark);
        ob_start();
        imagepng($imDark);
        $darkPng = ob_get_clean();
        imagedestroy($imDark);

        Storage::disk('public')->put('designs/pixel-dark.png', $darkPng);
        $this->assertFalse(CardPresenter::isLightArtwork(imagePath: 'designs/pixel-dark.png', disk: 'public'));
    }
}
