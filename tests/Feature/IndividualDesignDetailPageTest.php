<?php

namespace Tests\Feature;

use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\HomepageSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndividualDesignDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $activeCategory;

    private CateDesign $inactiveCategory;

    private Color $goldColor;

    private Color $silverColor;

    private Color $inactiveColor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeCategory = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->inactiveCategory = CateDesign::create([
            'name' => 'غیرفعال',
            'slug' => 'inactive-category',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->goldColor = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->silverColor = Color::create([
            'name' => 'نقره‌ای',
            'code_hex' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->inactiveColor = Color::create([
            'name' => 'رنگ خاموش',
            'code_hex' => '#999999',
            'is_active' => false,
            'sort_order' => 99,
        ]);
    }

    private function createDesign(
        string $name,
        string $slug,
        bool $isActive = true,
        bool $isPopular = false,
        ?CateDesign $category = null
    ): Design {
        return Design::create([
            'cate_design_id' => ($category ?? $this->activeCategory)->id,
            'name' => $name,
            'slug' => $slug,
            'description' => 'توضیحات نمونه برای طرح ' . $name,
            'is_active' => $isActive,
            'is_popular' => $isPopular,
            'sort_order' => 1,
        ]);
    }

    private function attachImage(
        Design $design,
        Color $color,
        string $imagePath = 'designs/sample.png',
        bool $isActive = true,
        int $sortOrder = 1
    ): DesignImage {
        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $imagePath,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $image;
    }

    public function test_public_active_design_is_reachable_by_slug(): void
    {
        $design = $this->createDesign('طرح بیت‌کوین مدرن', 'btc-modern');
        $this->attachImage($design, $this->goldColor, 'designs/btc-gold.png');

        $response = $this->get(route('catalog.designs.show', $design->slug));

        $response->assertOk();
        $response->assertSee('طرح بیت‌کوین مدرن');
        $response->assertSee('ورزشی');
        $response->assertSee('طلایی');
        $response->assertSee('designs/btc-gold.png');
        $response->assertSee('فهرست طرح‌ها');
    }

    public function test_unknown_or_inactive_designs_return_404(): void
    {
        // 1. Unknown slug
        $this->get(route('catalog.designs.show', 'non-existent-design'))->assertNotFound();

        // 2. Inactive design with active image and active category
        $inactiveDesign = $this->createDesign('طرح غیرفعال', 'inactive-design', isActive: false);
        $this->attachImage($inactiveDesign, $this->goldColor);
        $this->get(route('catalog.designs.show', $inactiveDesign->slug))->assertNotFound();

        // 3. Active design in inactive category
        $designInactiveCat = $this->createDesign('طرح در دسته غیرفعال', 'design-in-inactive-cat', category: $this->inactiveCategory);
        $this->attachImage($designInactiveCat, $this->goldColor);
        $this->get(route('catalog.designs.show', $designInactiveCat->slug))->assertNotFound();

        // 4. Active design with no active images
        $designNoImages = $this->createDesign('طرح بدون تصویر', 'design-no-images');
        $this->get(route('catalog.designs.show', $designNoImages->slug))->assertNotFound();
    }

    public function test_literal_popular_route_takes_precedence_over_slug(): void
    {
        $popular = $this->createDesign('طرح پرطرفدار تست', 'pop-test', isPopular: true);
        $this->attachImage($popular, $this->goldColor);

        // /catalog/designs/popular must resolve the popular archive, not the show action with slug='popular'
        $response = $this->get('/catalog/designs/popular');

        $response->assertOk();
        $response->assertSee('طرح‌های پرطرفدار');
        $response->assertSee('طرح پرطرفدار تست');
    }

    public function test_detail_page_shows_only_valid_active_variants(): void
    {
        $design = $this->createDesign('طرح چند نسخه', 'multi-variant');

        // Valid Variant 1: Gold
        $this->attachImage($design, $this->goldColor, 'designs/valid-gold.png', isActive: true, sortOrder: 1);

        // Valid Variant 2: Silver
        $this->attachImage($design, $this->silverColor, 'designs/valid-silver.png', isActive: true, sortOrder: 2);

        // Invalid Variant 3: Image inactive
        $this->attachImage($design, $this->goldColor, 'designs/inactive-img.png', isActive: false, sortOrder: 3);

        // Invalid Variant 4: Image with inactive color
        $this->attachImage($design, $this->inactiveColor, 'designs/inactive-color.png', isActive: true, sortOrder: 4);

        // Invalid Variant 5: Image where all compatibilities are explicitly false
        $disabledImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->goldColor->id,
            'image_path' => 'designs/disabled-compat.png',
            'is_active' => true,
            'sort_order' => 5,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $disabledImage->id,
            'card_color_id' => $this->goldColor->id,
            'is_allowed' => false,
        ]);

        $response = $this->get(route('catalog.designs.show', $design->slug));
        $response->assertOk();

        $variants = $response->viewData('variants');
        $this->assertCount(2, $variants);
        $this->assertEquals(['طلایی', 'نقره‌ای'], $variants->pluck('color_name')->all());
        $this->assertEquals(['designs/valid-gold.png', 'designs/valid-silver.png'], $variants->pluck('image_path')->all());
    }

    public function test_switching_color_variant_via_query_param_keeps_same_design_selected(): void
    {
        $design = $this->createDesign('طرح انتخاب رنگ', 'color-switch-design');
        $this->attachImage($design, $this->goldColor, 'designs/switch-gold.png', sortOrder: 1);
        $this->attachImage($design, $this->silverColor, 'designs/switch-silver.png', sortOrder: 2);

        // Request with Silver color_id
        $response = $this->get(route('catalog.designs.show', [
            'slug' => $design->slug,
            'color_id' => $this->silverColor->id,
        ]));

        $response->assertOk();
        $this->assertEquals($design->id, $response->viewData('design')->id);
        $this->assertEquals($this->silverColor->id, $response->viewData('selectedColorId'));
        $this->assertEquals('designs/switch-silver.png', $response->viewData('selectedVariant')['image_path']);
    }

    public function test_invalid_or_foreign_color_id_falls_back_safely_to_first_valid_variant(): void
    {
        $design = $this->createDesign('طرح ایمنی ورودی', 'safe-input-design');
        $this->attachImage($design, $this->goldColor, 'designs/safe-gold.png', sortOrder: 1);

        // Passing nonexistent or foreign color_id
        $response = $this->get(route('catalog.designs.show', [
            'slug' => $design->slug,
            'color_id' => 999999,
        ]));

        $response->assertOk();
        $this->assertEquals($design->id, $response->viewData('design')->id);
        $this->assertEquals($this->goldColor->id, $response->viewData('selectedColorId'));
        $this->assertEquals('designs/safe-gold.png', $response->viewData('selectedVariant')['image_path']);
    }

    public function test_popular_badge_displayed_for_popular_designs(): void
    {
        $popular = $this->createDesign('طرح محبوب جزئیات', 'pop-detail-design', isPopular: true);
        $this->attachImage($popular, $this->goldColor);

        $regular = $this->createDesign('طرح عادی جزئیات', 'reg-detail-design', isPopular: false);
        $this->attachImage($regular, $this->goldColor);

        $popResponse = $this->get(route('catalog.designs.show', $popular->slug));
        $popResponse->assertOk();
        $popResponse->assertSee('طرح پرطرفدار');

        $regResponse = $this->get(route('catalog.designs.show', $regular->slug));
        $regResponse->assertOk();
        $regResponse->assertDontSee('طرح پرطرفدار');
    }

    public function test_design_cards_in_general_catalog_link_to_detail_page(): void
    {
        $design = $this->createDesign('طرح در فهرست عمومی', 'catalog-card-design');
        $this->attachImage($design, $this->goldColor);

        $response = $this->get(route('catalog.designs.index'));
        $response->assertOk();
        $response->assertSee(route('catalog.designs.show', $design->slug), false);
    }

    public function test_design_cards_in_popular_archive_link_to_detail_page(): void
    {
        $popular = $this->createDesign('طرح در آرشیو محبوب', 'popular-card-design', isPopular: true);
        $this->attachImage($popular, $this->goldColor);

        $response = $this->get(route('catalog.designs.popular'));
        $response->assertOk();
        $response->assertSee(route('catalog.designs.show', $popular->slug), false);
    }

    public function test_design_cards_on_homepage_link_to_detail_page(): void
    {
        $popular = $this->createDesign('طرح صفحه اصلی', 'home-card-design', isPopular: true);
        $this->attachImage($popular, $this->goldColor);

        HomepageSection::create([
            'section_type' => 'featured_designs',
            'title' => 'طرح‌های محبوب',
            'settings' => ['limit' => 8],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee(route('catalog.designs.show', $popular->slug), false);
    }

    public function test_designs_without_valid_active_variants_render_gracefully(): void
    {
        $design = $this->createDesign('طرح بدون رنگ فعال', 'no-valid-variant-design');

        // Image is active, but its color is inactive
        DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->inactiveColor->id,
            'image_path' => 'designs/no-color.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get(route('catalog.designs.show', $design->slug));

        $response->assertOk();
        $response->assertSee('طرح بدون رنگ فعال');
        $response->assertSee('در حال حاضر نسخه تصویری فعالی برای این طرح ثبت نشده است');
        $this->assertEmpty($response->viewData('variants'));
        $this->assertNull($response->viewData('selectedVariant'));
    }

    public function test_detail_page_does_not_render_interactive_color_selection_controls_and_cta_links_to_bank_designer(): void
    {
        $design = $this->createDesign('طرح تست بدون انتخاب رنگ', 'no-color-select-test');
        $this->attachImage($design, $this->goldColor, 'designs/test-gold.png', sortOrder: 1);
        $this->attachImage($design, $this->silverColor, 'designs/test-silver.png', sortOrder: 2);

        $response = $this->get(route('catalog.designs.show', $design->slug));

        $response->assertOk();
        // Informational badges exist
        $response->assertSee('رنگ‌بندی‌های قابل سفارش');
        $response->assertSee('طلایی');
        $response->assertSee('نقره‌ای');

        // No interactive Alpine color controls
        $response->assertDontSee('selectVariant(');
        $response->assertDontSee('selectedVariantId');
        $response->assertDontSee('selectedCardColorId');

        // CTA links directly to Bank Designer with design slug and preview variant
        $response->assertSee('طراحی با این طرح');
        $response->assertSee(route('custom-card.bank'), false);
        $response->assertSee('design=' . $design->slug, false);
        $response->assertSee('variant=', false);
        $response->assertDontSee('color=', false);
    }
}
