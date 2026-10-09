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

class HomepageAndArchivePopularDesignsTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $activeCategory;

    private CateDesign $inactiveCategory;

    private Color $activeColor;

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
            'slug' => 'inactive-cat',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $this->activeColor = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createDesign(
        string $name,
        string $slug,
        bool $isPopular = false,
        bool $isActive = true,
        ?CateDesign $category = null,
        int $imageCount = 1,
        int $sortOrder = 1,
        int $popularSortOrder = 0
    ): Design {
        $category = $category ?? $this->activeCategory;

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'is_popular' => $isPopular,
            'popular_sort_order' => $popularSortOrder,
            'is_active' => $isActive,
            'sort_order' => $sortOrder,
        ]);

        for ($i = 1; $i <= $imageCount; $i++) {
            $image = DesignImage::create([
                'design_id' => $design->id,
                'color_id' => $this->activeColor->id,
                'image_path' => "designs/{$slug}-{$i}.png",
                'is_active' => true,
                'sort_order' => $i,
            ]);

            DesignColorCompatibility::create([
                'design_image_id' => $image->id,
                'card_color_id' => $this->activeColor->id,
                'is_allowed' => true,
            ]);
        }

        return $design;
    }

    private function createHomepageSection(array $settings = ['limit' => 8]): HomepageSection
    {
        return HomepageSection::create([
            'section_type' => 'featured_designs',
            'title' => 'طرح‌های محبوب',
            'content' => 'برترین طرح‌های الواکارت',
            'settings' => $settings,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_homepage_section_includes_only_popular_and_publicly_visible_designs(): void
    {
        $popular1 = $this->createDesign('طرح محبوب یک', 'pop-1', isPopular: true);
        $popular2 = $this->createDesign('طرح محبوب دو', 'pop-2', isPopular: true);
        $regular = $this->createDesign('طرح معمولی', 'reg-1', isPopular: false);
        $inactive = $this->createDesign('طرح محبوب غیرفعال', 'pop-inact', isPopular: true, isActive: false);
        $inactiveCat = $this->createDesign('طرح دسته غیرفعال', 'pop-inact-cat', isPopular: true, category: $this->inactiveCategory);

        $this->createHomepageSection();

        $response = $this->get('/');
        $response->assertOk();

        $response->assertSee('طرح محبوب یک');
        $response->assertSee('طرح محبوب دو');
        $response->assertDontSee('طرح معمولی');
        $response->assertDontSee('طرح محبوب غیرفعال');
        $response->assertDontSee('طرح دسته غیرفعال');

        $data = $response->viewData('sectionData');
        $this->assertCount(2, $data['featured_designs']['designs']);
        $this->assertTrue($data['featured_designs']['designs']->has($popular1->id));
        $this->assertTrue($data['featured_designs']['designs']->has($popular2->id));
        $this->assertFalse($data['featured_designs']['designs']->has($regular->id));
    }

    public function test_homepage_section_is_hidden_when_no_popular_designs_exist(): void
    {
        // Only non-popular active designs exist
        $this->createDesign('طرح معمولی یک', 'reg-1', isPopular: false);
        $this->createDesign('طرح معمولی دو', 'reg-2', isPopular: false);

        $this->createHomepageSection();

        $response = $this->get('/');
        $response->assertOk();

        // Must not fall back to displaying all designs
        $response->assertDontSee('طرح معمولی یک');
        $response->assertDontSee('طرح معمولی دو');
        $response->assertDontSee('طرح‌های محبوب');

        $data = $response->viewData('sectionData');
        $this->assertTrue($data['featured_designs']['designs']->isEmpty());
    }

    public function test_homepage_section_displays_at_most_one_card_per_design_with_multiple_images(): void
    {
        // Create popular design with 3 images / color variants
        $popular = $this->createDesign('طرح چند رنگ', 'multi-color', isPopular: true, imageCount: 3);

        $this->createHomepageSection();

        $response = $this->get('/');
        $response->assertOk();

        $data = $response->viewData('sectionData');
        $this->assertCount(1, $data['featured_designs']['designs']);
        $this->assertEquals(1, substr_count($response->getContent(), 'کارت شخصی با طرح '.$popular->name));
    }

    public function test_homepage_view_all_button_links_to_popular_archive(): void
    {
        $this->createDesign('طرح محبوب', 'pop-link', isPopular: true);
        $this->createHomepageSection();

        $response = $this->get('/');
        $response->assertOk();

        $response->assertSee('href="'.route('catalog.designs.popular').'"', false);
        $response->assertSee('مشاهده همه طرح‌ها');
    }

    public function test_homepage_section_supports_manual_design_ids(): void
    {
        $designA = $this->createDesign('طرح مشخص الف', 'spec-a', isPopular: false);
        $designB = $this->createDesign('طرح محبوب عادی', 'pop-b', isPopular: true);

        // Section explicitly configured with designA
        $this->createHomepageSection(['design_ids' => [$designA->id], 'limit' => 8]);

        $response = $this->get('/');
        $response->assertOk();

        $data = $response->viewData('sectionData');
        $this->assertCount(1, $data['featured_designs']['designs']);
        $this->assertTrue($data['featured_designs']['designs']->has($designA->id));
        $this->assertFalse($data['featured_designs']['designs']->has($designB->id));
    }

    public function test_popular_archive_lists_only_popular_and_publicly_visible_designs(): void
    {
        $pop1 = $this->createDesign('طرح پرطرفدار اول', 'pop-arch-1', isPopular: true);
        $pop2 = $this->createDesign('طرح پرطرفدار دوم', 'pop-arch-2', isPopular: true);
        $reg = $this->createDesign('طرح عادی گالری', 'reg-arch-1', isPopular: false);
        $inact = $this->createDesign('طرح پرطرفدار خاموش', 'pop-arch-inact', isPopular: true, isActive: false);
        $inactCat = $this->createDesign('طرح پرطرفدار دسته خاموش', 'pop-arch-inact-cat', isPopular: true, category: $this->inactiveCategory);

        $response = $this->get(route('catalog.designs.popular'));
        $response->assertOk();

        $response->assertSee('طرح پرطرفدار اول');
        $response->assertSee('طرح پرطرفدار دوم');
        $response->assertDontSee('طرح عادی گالری');
        $response->assertDontSee('طرح پرطرفدار خاموش');
        $response->assertDontSee('طرح پرطرفدار دسته خاموش');
        $response->assertSee('طرح‌های پرطرفدار');
    }

    public function test_popular_archive_renders_empty_state_without_falling_back_to_all_designs(): void
    {
        // Only non-popular designs exist
        $this->createDesign('طرح عادی غیرمحبوب', 'reg-only', isPopular: false);

        $response = $this->get(route('catalog.designs.popular'));
        $response->assertOk();

        $response->assertDontSee('طرح عادی غیرمحبوب');
        $response->assertSee('داده‌ای برای فهرست طرح‌ها یافت نشد');
    }

    public function test_popular_archive_pagination_remains_constrained_to_popular_designs(): void
    {
        // Create 15 popular designs
        for ($i = 1; $i <= 15; $i++) {
            $this->createDesign("طرح محبوب شماره {$i}", "pop-page-{$i}", isPopular: true, sortOrder: $i);
        }

        // Also create 5 non-popular designs
        for ($i = 1; $i <= 5; $i++) {
            $this->createDesign("طرح غیرمحبوب {$i}", "reg-page-{$i}", isPopular: false);
        }

        // Page 1: 12 per page
        $page1 = $this->get(route('catalog.designs.popular'));
        $page1->assertOk();
        $catalogPage1 = $page1->viewData('catalog');
        $this->assertCount(12, $catalogPage1);
        $this->assertEquals(15, $catalogPage1->total());
        $this->assertEquals(2, $catalogPage1->lastPage());
        $page1->assertSee('طرح محبوب شماره 1');
        $page1->assertDontSee('طرح غیرمحبوب');

        // Page 2: remaining 3
        $page2 = $this->get(route('catalog.designs.popular', ['page' => 2]));
        $page2->assertOk();
        $catalogPage2 = $page2->viewData('catalog');
        $this->assertCount(3, $catalogPage2);
        $page2->assertSee('طرح محبوب شماره 13');
        $page2->assertDontSee('طرح غیرمحبوب');
    }

    public function test_catalog_designs_index_with_popular_category_delegates_to_popular_view(): void
    {
        $popular = $this->createDesign('طرح ستاره‌دار', 'pop-starred', isPopular: true);
        $regular = $this->createDesign('طرح ساده', 'reg-simple', isPopular: false);

        $response = $this->get(route('catalog.designs.index', ['category' => 'popular-designs']));
        $response->assertOk();

        $response->assertSee('طرح ستاره‌دار');
        $response->assertDontSee('طرح ساده');
        $response->assertSee('طرح‌های پرطرفدار');
    }

    public function test_popular_archive_respects_category_and_color_filters(): void
    {
        $categoryMusic = CateDesign::create([
            'name' => 'موسیقی',
            'slug' => 'music',
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $goldColor = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Popular design in sports with black color image
        $sportsPopular = $this->createDesign('طرح ورزشی محبوب', 'pop-sport', isPopular: true, category: $this->activeCategory);

        // Popular design in music with gold color image
        $musicPopular = Design::create([
            'cate_design_id' => $categoryMusic->id,
            'name' => 'طرح موسیقی محبوب',
            'slug' => 'pop-music',
            'is_popular' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $musicImage = DesignImage::create([
            'design_id' => $musicPopular->id,
            'color_id' => $goldColor->id,
            'image_path' => 'designs/pop-music.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $musicImage->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        // Filter by music category: only music popular design appears
        $catResponse = $this->get(route('catalog.designs.popular', ['category' => 'music']));
        $catResponse->assertOk();
        $catResponse->assertSee('طرح موسیقی محبوب');
        $catResponse->assertDontSee('طرح ورزشی محبوب');

        // Filter by gold color: only music popular design (which has gold image) appears
        $colorResponse = $this->get(route('catalog.designs.popular', ['color_id' => $goldColor->id]));
        $colorResponse->assertOk();
        $colorResponse->assertSee('طرح موسیقی محبوب');
        $colorResponse->assertDontSee('طرح ورزشی محبوب');
    }
}
