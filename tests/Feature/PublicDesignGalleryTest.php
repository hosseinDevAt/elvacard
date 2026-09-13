<?php

namespace Tests\Feature;

use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicDesignGalleryTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $sports;

    private CateDesign $classic;

    private CateDesign $archived;

    private Color $gold;

    /** @var Design[] */
    private array $sportsDesigns = [];

    /** @var Design[] */
    private array $classicDesigns = [];

    private Design $hiddenDesign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gold = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->sports = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->classic = CateDesign::create([
            'name' => 'کلاسیک',
            'slug' => 'classic',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->archived = CateDesign::create([
            'name' => 'دسته مخفی',
            'slug' => 'archived',
            'is_active' => false,
            'sort_order' => 3,
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->sportsDesigns[$i] = $this->createDesign($this->sports, "طرح-ورزشی-{$i}", "sport-{$i}", $i);
        }

        for ($i = 11; $i <= 13; $i++) {
            $this->classicDesigns[$i] = $this->createDesign($this->classic, "طرح-کلاسیک-{$i}", "classic-{$i}", $i);
        }

        $this->hiddenDesign = $this->createDesign($this->archived, 'طرح مخفی دسته‌بندی', 'hidden-gallery', 30);
    }

    private function createDesign(CateDesign $category, string $name, string $slug, int $sortOrder): Design
    {
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->gold->id,
            'image_path' => 'designs/'.$slug.'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $design;
    }

    private function queriesForDesignTable(string $table): array
    {
        return array_values(array_filter(
            array_map(fn (array $query) => $query['query'], DB::getQueryLog()),
            function (string $sql) use ($table) {
                preg_match_all('~from (`|")(\w+)(`|")~i', $sql, $matches);

                return in_array($table, $matches[2] ?? [], true);
            }
        ));
    }

    public function test_gallery_is_paginated_to_twelve_cards_per_page(): void
    {
        $response = $this->get(route('catalog.designs.index'));

        $response->assertOk();

        $catalog = $response->viewData('catalog');
        $this->assertSame(13, $catalog->total());
        $this->assertSame(12, $catalog->perPage());
        $this->assertSame(1, $catalog->currentPage());
        $this->assertCount(12, $catalog->items());
        $this->assertSame(12, substr_count($response->getContent(), 'id="design-'), 'Exactly 12 design anchors must render.');

        // Deep-link anchors from MenuItem / featured designs must survive.
        $response->assertSee('id="design-'.$this->sportsDesigns[1]->id.'"', false);

        $pageTwo = $this->get(route('catalog.designs.index', ['page' => 2]));
        $pageTwo->assertOk();
        $this->assertCount(1, $pageTwo->viewData('catalog')->items());
        $this->assertSame(1, substr_count($pageTwo->getContent(), 'id="design-'), 'Page two must render exactly the single remaining card.');
    }

    public function test_gallery_is_bounded_and_does_not_hydrate_the_full_catalog(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('catalog.designs.index'))->assertOk();

        $this->assertCount(2, $this->queriesForDesignTable('designs'), 'The gallery must resolve designs with exactly the paginate count and page queries, never a full-catalog hydration.');
        $this->assertCount(0, $this->queriesForDesignTable('design_color_compatibilities'), 'Browsing without a color filter must not touch the compatibility table.');
    }

    public function test_gallery_is_bounded_by_the_service(): void
    {
        $catalog = app(DesignCatalogService::class)->paginatePublic($this->sports->id, null, null);

        $this->assertSame(10, $catalog->total());
        $this->assertCount(10, $catalog->items());

        foreach ($catalog->items() as $item) {
            $this->assertSame(['id', 'category_id', 'name', 'preview_image_path'], array_keys($item));
        }
    }

    public function test_inactive_category_is_not_exposed_through_the_query_parameter(): void
    {
        $response = $this->get(route('catalog.designs.index', ['category' => 'archived']));

        $response->assertOk();
        $response->assertDontSee('طرح مخفی دسته‌بندی');
        $response->assertDontSee('دسته مخفی');
    }

    public function test_active_category_filtering_still_works(): void
    {
        $response = $this->get(route('catalog.designs.index', ['category' => 'classic']));

        $response->assertOk();
        $response->assertSee('طرح-کلاسیک-13');
        $response->assertDontSee('طرح-ورزشی-1');

        $catalog = $response->viewData('catalog');
        $this->assertSame(3, $catalog->total());
    }

    public function test_invalid_category_value_behaves_safely(): void
    {
        $response = $this->get(route('catalog.designs.index', ['category' => 'does-not-exist']));

        $response->assertOk();
        $response->assertDontSee('طرح مخفی دسته‌بندی');

        // Unknown category safely falls back to the default browse of active
        // categories instead of failing.
        $catalog = $response->viewData('catalog');
        $this->assertSame(13, $catalog->total());
    }
}
