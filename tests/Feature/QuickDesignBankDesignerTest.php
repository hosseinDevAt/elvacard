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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QuickDesignBankDesignerTest extends TestCase
{
    use RefreshDatabase;

    private Product $bankProduct;
    private CateDesign $category;
    private Color $goldColor;
    private Color $silverColor;
    private Color $blackColor;
    private Design $popularDesign;
    private DesignImage $goldImage;
    private DesignImage $silverImage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->bankProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $this->goldColor = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->silverColor = Color::create([
            'name' => 'نقره‌ای',
            'code_hex' => '#C0C0C0',
            'color_code' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->blackColor = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#111111',
            'color_code' => '#111111',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->goldColor->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->silverColor->id,
            'price' => 550000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->blackColor->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        $baseDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح پایه کاتالوگ',
            'slug' => 'default-base-design',
            'is_active' => true,
            'sort_order' => 0,
        ]);
        Storage::disk('public')->put('designs/base.png', 'fake base');
        $baseImg = DesignImage::create([
            'design_id' => $baseDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/base.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $baseImg->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        $this->popularDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح شیر طلایی',
            'slug' => 'golden-lion',
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put('designs/lion-gold.png', 'fake image gold');
        Storage::disk('public')->put('designs/lion-silver.png', 'fake image silver');

        $this->goldImage = DesignImage::create([
            'design_id' => $this->popularDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/lion-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->silverImage = DesignImage::create([
            'design_id' => $this->popularDesign->id,
            'color_id' => $this->silverColor->id,
            'image_path' => 'designs/lion-silver.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Compatible with gold and silver card colors, NOT black
        DesignColorCompatibility::create([
            'design_image_id' => $this->goldImage->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->silverImage->id,
            'card_color_id' => $this->silverColor->id,
            'is_allowed' => true,
        ]);

        // silverImage is also compatible with gold card color (allowing test of variant choice)
        DesignColorCompatibility::create([
            'design_image_id' => $this->silverImage->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);
    }

    public function test_detail_page_cta_links_to_bank_designer_with_design_slug_and_preview_variant(): void
    {
        $response = $this->get(route('catalog.designs.show', ['slug' => 'golden-lion']));

        $response->assertOk();
        $response->assertSee(route('custom-card.bank'), false);
        $response->assertSee('design=golden-lion', false);
        $response->assertSee('variant=' . $this->goldImage->id, false);
        $response->assertDontSee('color=' . $this->goldColor->id, false);
        $response->assertSee('طراحی با این طرح');
        $response->assertSee('رنگ‌بندی‌های قابل سفارش');
    }

    public function test_detail_page_has_no_interactive_color_selectors(): void
    {
        $response = $this->get(route('catalog.designs.show', ['slug' => 'golden-lion']));

        $response->assertOk();
        // No Alpine x-data container for variant selection
        $response->assertDontSee('selectVariant(');
        $response->assertDontSee('selectedVariantId');
        $response->assertDontSee('selectedCardColorId');
    }

    public function test_quick_design_without_color_params_preselects_design_default_color_and_first_variant(): void
    {
        Livewire::withQueryParams([
            'design' => 'golden-lion',
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
            ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->goldImage->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_preview_variant_determines_compatible_card_color_when_default_color_is_incompatible_with_that_variant(): void
    {
        // Design has Variant A (compatible ONLY with silverColor) and Variant B (compatible ONLY with goldColor).
        $dualDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح دو رنگ',
            'slug' => 'dual-design',
            'is_active' => true,
            'sort_order' => 5,
        ]);
        Storage::disk('public')->put('designs/dual-gold.png', 'fake dual gold');
        Storage::disk('public')->put('designs/dual-silver.png', 'fake dual silver');

        $variantGold = DesignImage::create([
            'design_id' => $dualDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/dual-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $variantGold->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        $variantSilver = DesignImage::create([
            'design_id' => $dualDesign->id,
            'color_id' => $this->silverColor->id,
            'image_path' => 'designs/dual-silver.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $variantSilver->id,
            'card_color_id' => $this->silverColor->id,
            'is_allowed' => true,
        ]);

        // Default card color on product is goldColor.
        // Arriving via Quick Design with variant=variantSilver->id must select silverColor and preserve variantSilver!
        Livewire::withQueryParams([
            'design' => 'dual-design',
            'variant' => (string) $variantSilver->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'dual-design',
                'initialVariantId' => $variantSilver->id,
            ])
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $dualDesign->id)
            ->assertSet('design_image_id', $variantSilver->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_first_compatible_card_color_is_selected_by_priority_when_variant_compatible_with_multiple_colors(): void
    {
        // Design has Variant compatible with silverColor (priority 2) and blackColor (priority 3), but NOT goldColor (priority 1).
        $priorityDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح اولویت رنگ',
            'slug' => 'priority-design',
            'is_active' => true,
            'sort_order' => 6,
        ]);
        Storage::disk('public')->put('designs/priority.png', 'fake priority');

        $variantMulti = DesignImage::create([
            'design_id' => $priorityDesign->id,
            'color_id' => $this->silverColor->id,
            'image_path' => 'designs/priority.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        // Compatible with both silver and black:
        DesignColorCompatibility::create([
            'design_image_id' => $variantMulti->id,
            'card_color_id' => $this->silverColor->id,
            'is_allowed' => true,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $variantMulti->id,
            'card_color_id' => $this->blackColor->id,
            'is_allowed' => true,
        ]);

        Livewire::withQueryParams([
            'design' => 'priority-design',
            'variant' => (string) $variantMulti->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'priority-design',
                'initialVariantId' => $variantMulti->id,
            ])
            // silverColor has higher priority (sort 2) in colorPrices than blackColor (sort 3)
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $priorityDesign->id)
            ->assertSet('design_image_id', $variantMulti->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_foreign_variant_belonging_to_another_design_is_rejected_safely(): void
    {
        $otherDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح متفرقه',
            'slug' => 'other-design',
            'is_active' => true,
            'sort_order' => 7,
        ]);
        Storage::disk('public')->put('designs/other.png', 'fake other');
        $otherImage = DesignImage::create([
            'design_id' => $otherDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/other.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $otherImage->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        // Requesting popularDesign slug with otherDesign variant ID
        $component = Livewire::withQueryParams([
            'design' => 'golden-lion',
            'variant' => (string) $otherImage->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialVariantId' => $otherImage->id,
            ]);

        // Must reject without selecting the design or the foreign variant
        $this->assertNotSame($this->popularDesign->id, $component->get('design_id'));
        $this->assertNotSame($otherImage->id, $component->get('design_image_id'));
        $this->assertSame($this->goldColor->id, $component->get('color_id'));
    }

    public function test_inactive_variant_is_rejected_safely(): void
    {
        $inactiveImage = DesignImage::create([
            'design_id' => $this->popularDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/inactive.png',
            'is_active' => false,
            'sort_order' => 10,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $inactiveImage->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        $component = Livewire::withQueryParams([
            'design' => 'golden-lion',
            'variant' => (string) $inactiveImage->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialVariantId' => $inactiveImage->id,
            ]);

        // Must reject without selecting the requested inactive variant
        $this->assertNotSame($inactiveImage->id, $component->get('design_image_id'));
        $this->assertNotSame($this->popularDesign->id, $component->get('design_id'));
        $this->assertSame($this->goldColor->id, $component->get('color_id'));
    }

    public function test_variant_with_no_compatible_card_colors_is_rejected_and_cannot_be_added_to_cart(): void
    {
        $unsupportedDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح بدون سازگاری',
            'slug' => 'unsupported-design',
            'is_active' => true,
            'sort_order' => 8,
        ]);
        Storage::disk('public')->put('designs/unsupported.png', 'fake unsupported');
        $unsupportedImage = DesignImage::create([
            'design_id' => $unsupportedDesign->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/unsupported.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        // No DesignColorCompatibility record created

        $component = Livewire::withQueryParams([
            'design' => 'unsupported-design',
            'variant' => (string) $unsupportedImage->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'unsupported-design',
                'initialVariantId' => $unsupportedImage->id,
            ]);

        // Neither the unsupported design nor its variant are selected
        $this->assertNotSame($unsupportedDesign->id, $component->get('design_id'));
        $this->assertNotSame($unsupportedImage->id, $component->get('design_image_id'));

        // If user manually forces this invalid variant and attempts to add to cart, validation blocks it
        $component->set('design_id', $unsupportedDesign->id)
            ->set('design_image_id', $unsupportedImage->id)
            ->call('addToCart')
            ->assertHasErrors(['design_image_id']);
    }

    public function test_deterministic_card_color_selection_when_default_color_is_incompatible(): void
    {
        // Design silver-falcon is only compatible with silverColor, not goldColor (which is default)
        $silverFalcon = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'شاهین نقره‌ای',
            'slug' => 'silver-falcon',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        Storage::disk('public')->put('designs/falcon.png', 'falcon fake');
        $falconImg = DesignImage::create([
            'design_id' => $silverFalcon->id,
            'color_id' => $this->silverColor->id,
            'image_path' => 'designs/falcon.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $falconImg->id,
            'card_color_id' => $this->silverColor->id,
            'is_allowed' => true,
        ]);

        // Arriving via Quick Design with just the slug
        Livewire::withQueryParams([
            'design' => 'silver-falcon',
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'silver-falcon',
            ])
            // Deterministically selects silverColor because goldColor is incompatible
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $silverFalcon->id)
            ->assertSet('design_image_id', $falconImg->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_bank_designer_page_renders_with_query_parameters(): void
    {
        $response = $this->get(route('custom-card.bank', [
            'design' => 'golden-lion',
            'color' => $this->silverColor->id,
            'variant' => $this->silverImage->id,
        ]));

        $response->assertOk();
        $response->assertSee('طراحی کارت بانکی');
    }

    public function test_selected_design_variant_is_transferred_correctly_into_bank_designer(): void
    {
        // On gold card, both goldImage and silverImage are compatible.
        // goldImage is first in sort_order. Requesting silverImage must preselect silverImage, not goldImage.
        Livewire::withQueryParams([
            'design' => 'golden-lion',
            'color' => (string) $this->goldColor->id,
            'variant' => (string) $this->silverImage->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialColorId' => $this->goldColor->id,
                'initialVariantId' => $this->silverImage->id,
            ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('selected_category_id', $this->category->id)
            ->assertSet('design_image_id', $this->silverImage->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_card_color_remains_unchanged_during_variant_transfer(): void
    {
        Livewire::withQueryParams([
            'design' => 'golden-lion',
            'color' => (string) $this->silverColor->id,
            'variant' => (string) $this->silverImage->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialColorId' => $this->silverColor->id,
                'initialVariantId' => $this->silverImage->id,
            ])
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_image_id', $this->silverImage->id);
    }

    public function test_invalid_or_incompatible_variant_does_not_silently_select_unrelated_variant(): void
    {
        // Requesting an invalid variant ID 99999
        $component = Livewire::withQueryParams([
            'design' => 'golden-lion',
            'color' => (string) $this->goldColor->id,
            'variant' => '99999',
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialColorId' => $this->goldColor->id,
                'initialVariantId' => 99999,
            ]);

        // It must NOT silently select goldImage (or silverImage) for the design
        $this->assertNotSame($this->popularDesign->id, $component->get('design_id'));
    }

    public function test_bank_designer_falls_back_gracefully_when_query_params_are_absent(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
        ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('designSource', 'catalog');
    }

    public function test_bank_designer_falls_back_gracefully_when_design_slug_is_invalid(): void
    {
        Livewire::withQueryParams([
            'design' => 'non-existent-slug-xyz',
            'color' => (string) $this->goldColor->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'non-existent-slug-xyz',
                'initialColorId' => $this->goldColor->id,
            ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertOk();
    }

    public function test_bank_designer_falls_back_gracefully_when_color_is_incompatible_with_design(): void
    {
        // Black color has no compatibility records for golden-lion design
        $component = Livewire::withQueryParams([
            'design' => 'golden-lion',
            'color' => (string) $this->blackColor->id,
        ])
            ->test(ProductCustomizer::class, [
                'productId' => $this->bankProduct->id,
                'initialDesignSlug' => 'golden-lion',
                'initialColorId' => $this->blackColor->id,
            ])
            ->assertSet('color_id', $this->blackColor->id)
            ->assertOk();

        // Design is not compatible with black, so it is not preselected
        $this->assertNotSame($this->popularDesign->id, $component->get('design_id'));
    }

    public function test_bank_designer_preserves_design_when_switching_to_compatible_color(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
            'initialColorId' => $this->goldColor->id,
        ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->goldImage->id);

        // Switch to silver color: golden-lion has silverImage compatible with silver
        $component->call('selectColor', $this->silverColor->id)
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->silverImage->id);
    }

    public function test_bank_designer_clears_design_when_switching_to_incompatible_color(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
            'initialColorId' => $this->goldColor->id,
        ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id);

        // Switch to black: golden-lion has NO compatible images for black
        $component->call('selectColor', $this->blackColor->id)
            ->assertSet('color_id', $this->blackColor->id);

        // In render, because golden-lion is not compatible with black, design_id cannot be golden-lion
        $this->assertNotSame($this->popularDesign->id, $component->get('design_id'));
    }

    public function test_subsequent_user_interactions_do_not_re_force_initial_parameters(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
            'initialColorId' => $this->goldColor->id,
        ])
            ->assertSet('design_id', $this->popularDesign->id);

        // Create a second design
        $design2 = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح دوم عقاب',
            'slug' => 'eagle-design',
            'is_active' => true,
            'sort_order' => 2,
        ]);
        Storage::disk('public')->put('designs/eagle.png', 'fake image eagle');
        $img2 = DesignImage::create([
            'design_id' => $design2->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/eagle.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $img2->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => true,
        ]);

        // User manually chooses design 2
        $component->call('selectDesign', $design2->id)
            ->assertSet('design_id', $design2->id);

        // User switches step
        $component->call('setStep', 2)
            ->assertSet('step', 2)
            // Must NOT reset design_id back to golden-lion
            ->assertSet('design_id', $design2->id);
    }

    public function test_fuel_card_designer_remains_isolated_and_unaffected(): void
    {
        $fuelCategory = CateDesign::create([
            'name' => 'خودرویی',
            'slug' => 'fuel-auto',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت اختصاصی',
            'slug' => 'fuel-card',
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $fuelProduct->id,
            'color_id' => $this->blackColor->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        $fuelDesign = Design::create([
            'cate_design_id' => $fuelCategory->id,
            'name' => 'طرح سوخت',
            'slug' => 'fuel-pattern',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Storage::disk('public')->put('designs/fuel-pattern.png', 'fake fuel img');

        $fuelImg = DesignImage::create([
            'design_id' => $fuelDesign->id,
            'color_id' => $this->blackColor->id,
            'image_path' => 'designs/fuel-pattern.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $fuelImg->id,
            'card_color_id' => $this->blackColor->id,
            'is_allowed' => true,
        ]);

        // Mounting Fuel Product ignores bank deep-link design
        $fuelComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $fuelProduct->id,
            'initialDesignSlug' => 'golden-lion',
            'initialColorId' => $this->goldColor->id,
        ])
            ->assertSet('color_id', $this->blackColor->id);

        // Attempting to select another color on Fuel card fails / no-op
        $fuelComponent->call('selectColor', $this->goldColor->id)
            ->assertSet('color_id', $this->blackColor->id);
    }

    public function test_changing_design_variant_preserves_card_color_and_selected_design(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
        ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->goldImage->id);

        // Change design variant to silverImage
        $component->call('selectDesignImage', $this->silverImage->id)
            ->assertSet('design_image_id', $this->silverImage->id)
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_id', $this->popularDesign->id);
    }

    public function test_changing_physical_card_color_preserves_compatible_variant(): void
    {
        // silverImage is compatible with both goldColor and silverColor
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
        ]);

        $component->call('selectDesignImage', $this->silverImage->id)
            ->assertSet('design_image_id', $this->silverImage->id)
            ->assertSet('color_id', $this->goldColor->id);

        // Switch card color to silverColor
        $component->call('selectColor', $this->silverColor->id)
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->silverImage->id);
    }

    public function test_changing_physical_card_color_switches_variant_when_current_variant_is_incompatible(): void
    {
        // goldImage is ONLY compatible with goldColor, NOT silverColor
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
        ])
            ->assertSet('color_id', $this->goldColor->id)
            ->assertSet('design_image_id', $this->goldImage->id);

        // Switch to silverColor: golden-lion has silverImage compatible with silver
        $component->call('selectColor', $this->silverColor->id)
            ->assertSet('color_id', $this->silverColor->id)
            ->assertSet('design_id', $this->popularDesign->id)
            ->assertSet('design_image_id', $this->silverImage->id);
    }

    public function test_adding_customized_card_to_cart_preserves_selected_variant_color_and_pricing(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
        ]);

        $component->set('bankCard.card_number', '6274051234567898')
            ->set('bankCard.card_holder_name', 'علی محمدی')
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertRedirect(route('cart.index'));

        $cart = app(\App\Services\CartService::class)->getCart();
        $this->assertNotEmpty($cart['items']);
        $item = $cart['items'][0];

        $this->assertEquals($this->bankProduct->id, $item['product_id']);
        $this->assertEquals($this->goldColor->id, $item['color_id']);
        $this->assertEquals($this->popularDesign->id, $item['design_id']);
        $this->assertEquals($this->goldImage->id, $item['design_image_id']);
        $this->assertEquals(500000, $item['unit_price_snapshot']);
        $this->assertEquals(500000, $item['final_price']);
    }

    public function test_incompatible_variant_cannot_be_submitted_to_cart(): void
    {
        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => 'golden-lion',
        ]);

        // Artificially change color_id to blackColor without selecting compatible image
        $component->set('color_id', $this->blackColor->id)
            ->call('addToCart')
            ->assertHasErrors(['design_image_id']);
    }
}
