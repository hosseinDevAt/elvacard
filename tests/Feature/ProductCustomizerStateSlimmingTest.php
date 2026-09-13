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
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizerStateSlimmingTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private CateDesign $secondCategory;

    private Color $gold;

    private Color $silver;

    private Design $lionDesign;

    private DesignImage $lionGoldImage;

    private Design $eagleDesign;

    private DesignImage $eagleGoldImage;

    private DesignImage $eagleSilverImage;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->secondCategory = CateDesign::create([
            'name' => 'کلاسیک',
            'slug' => 'classic',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->gold = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->silver = Color::create([
            'name' => 'نقره‌ای',
            'code_hex' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی تست',
            'slug' => 'slim-state-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->gold->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->silver->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        $this->lionDesign = $this->createDesign($this->category, 'طرح شیر', 'lion', 1);
        $this->lionGoldImage = $this->createImage($this->lionDesign, $this->gold, 'designs/lion-gold.png', 1, true);

        $this->eagleDesign = $this->createDesign($this->secondCategory, 'طرح عقاب', 'eagle', 1);
        $this->eagleGoldImage = $this->createImage($this->eagleDesign, $this->gold, 'designs/eagle-gold.png', 1, true);
        $this->eagleSilverImage = $this->createImage($this->eagleDesign, $this->silver, 'designs/eagle-silver.png', 2, true);
    }

    private function createDesign(CateDesign $category, string $name, string $slug, int $sortOrder): Design
    {
        return Design::create([
            'cate_design_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function createImage(Design $design, Color $color, string $path, int $sortOrder, bool $allowed): DesignImage
    {
        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => $allowed,
        ]);

        return $image;
    }

    private function designTableQueries(): array
    {
        $tables = ['cate_designs', 'designs', 'design_images', 'design_color_compatibilities'];

        return array_filter(
            array_map(fn (array $query) => $query['query'], DB::getQueryLog()),
            fn (string $sql) => (bool) preg_match('~from (`|")(\w+)(`|")~i', $sql, $m) && in_array($m[2], $tables, true)
        );
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

    public function test_catalog_is_not_livewire_state_and_service_items_are_flat_scalar_arrays(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $vars = get_object_vars($component->instance());
        $this->assertArrayNotHasKey('designs', $vars, 'The paginated design catalog must never be persisted as Livewire state.');

        $catalog = app(DesignCatalogService::class)->paginate(
            $this->category->id,
            $this->gold->id,
            collect([$this->lionGoldImage->id]),
            CustomizationWorkflowEnum::BANK_CARD,
        );

        $this->assertSame(1, $catalog->total());

        $item = $catalog->items()[0];
        $this->assertSame($this->lionDesign->id, $item['id']);
        $this->assertSame($this->category->id, $item['category_id']);
        $this->assertSame('طرح شیر', $item['name']);
        $this->assertSame('designs/lion-gold.png', $item['preview_image_path']);

        // The catalog rows must be flat scalars: no nested collections, no models, no compat payload.
        $this->assertArrayNotHasKey('images', $item);
        $this->assertArrayNotHasKey('compatibilities', $item);
        $this->assertArrayNotHasKey('category', $item);
    }

    public function test_legacy_catalog_state_properties_are_not_persisted(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])->instance();

        $vars = get_object_vars($component);

        foreach (['catalog', 'designOptions', 'designImageOptions', 'designs', 'product'] as $legacy) {
            $this->assertArrayNotHasKey($legacy, $vars, "Property '{$legacy}' must not be persisted as Livewire state.");
        }
    }

    public function test_mount_auto_selects_first_category_design_and_image(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->assertSet('color_id', $this->gold->id)
            ->assertSet('selected_category_id', $this->category->id)
            ->assertSet('design_id', $this->lionDesign->id)
            ->assertSet('design_image_id', $this->lionGoldImage->id);
    }

    public function test_category_change_switches_rendered_designs_server_side(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectCategory', $this->secondCategory->id)
            ->assertSet('selected_category_id', $this->secondCategory->id)
            ->assertDontSee('طرح شیر')
            ->assertSee('طرح عقاب');

        $this->assertGreaterThan(0, count($this->designTableQueries()), 'Category switching must resolve the server-side catalog for the new category.');
    }

    public function test_mount_loads_only_selected_design_images_into_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // The first page only contains the sports category: the classic eagle
        // is neither in state nor rendered until its tab is visited.
        $component->assertSee('طرح شیر')
            ->assertDontSee('طرح عقاب');

        $designImages = $component->get('designImages');
        $this->assertCount(1, $designImages);
        $this->assertSame($this->lionGoldImage->id, $designImages[0]['id']);
        $this->assertSame($this->lionDesign->id, $designImages[0]['design_id']);
        $this->assertSame($this->lionDesign->id, $component->get('design_id'));
    }

    public function test_design_change_lazily_updates_image_options(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->call('selectCategory', $this->secondCategory->id);
        $component->call('selectDesign', $this->eagleDesign->id)
            ->assertSet('design_id', $this->eagleDesign->id)
            ->assertSet('design_image_id', $this->eagleGoldImage->id);

        // Switching designs swaps ONLY the selected design's image chips.
        $this->assertSame([$this->eagleGoldImage->id], array_column($component->get('designImages'), 'id'));
    }

    public function test_select_design_queries_do_not_reload_categories_or_reuse_stale_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $component->call('selectCategory', $this->secondCategory->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectDesign', $this->eagleDesign->id)
            ->assertSet('design_id', $this->eagleDesign->id)
            ->assertSet('design_image_id', $this->eagleGoldImage->id);

        $this->assertCount(0, $this->queriesForDesignTable('cate_designs'), 'Design selection must not reload the category list.');
        $this->assertCount(3, $this->queriesForDesignTable('designs'), 'Server-side catalog re-provisions on render: the selectDesign existence check plus the paginate count and page queries.');
        $this->assertCount(2, $this->queriesForDesignTable('design_color_compatibilities'), 'Allowed image IDs resolve once in the action and once in render.');
        $this->assertSame(
            4,
            count($this->queriesForDesignTable('design_images')),
            'The server-side catalog re-provisions on render: existence check, paginate count/page previews, plus only the selected design\'s chips.'
        );

        $this->assertSame([$this->eagleGoldImage->id], array_column($component->get('designImages'), 'id'));
    }

    public function test_image_change_does_not_reload_categories(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $component->call('selectCategory', $this->secondCategory->id);
        $component->call('selectDesign', $this->eagleDesign->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectDesignImage', $this->eagleGoldImage->id)
            ->assertSet('design_image_id', $this->eagleGoldImage->id)
            ->assertSet('design_id', $this->eagleDesign->id);

        $this->assertCount(0, $this->queriesForDesignTable('cate_designs'), 'Image switching must not reload the category list.');
    }

    public function test_unknown_design_image_id_is_ignored(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->call('selectDesignImage', 999999)
            ->assertSet('design_image_id', $this->lionGoldImage->id)
            ->assertSet('design_id', $this->lionDesign->id);
    }

    public function test_quantity_survives_across_interactions(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->set('quantity', 3)
            ->call('selectCategory', $this->secondCategory->id)
            ->call('selectDesign', $this->eagleDesign->id)
            ->assertSet('quantity', 3);
    }

    public function test_color_change_resets_selection_and_filters_to_compatible_designs(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Silver has no sports design: the current page of the sports catalog
        // empties, the previous gold selection is dropped and pagination resets.
        $component->call('selectColor', $this->silver->id)
            ->assertSet('color_id', $this->silver->id)
            ->assertSet('design_id', null)
            ->assertSet('design_image_id', null)
            ->assertSet('paginators.page', 1)
            ->assertSee('رنگ انتخابی موجود نیست')
            ->assertDontSee('طرح شیر');

        // Browsing to the classic tab reveals its silver-compatible design.
        $component->call('selectCategory', $this->secondCategory->id)
            ->assertSet('design_id', $this->eagleDesign->id)
            ->assertSet('design_image_id', $this->eagleSilverImage->id)
            ->assertSee('طرح عقاب')
            ->assertDontSee('موجود نیست');
    }

    public function test_incompatible_design_is_excluded_from_server_catalog(): void
    {
        $forbiddenDesign = $this->createDesign($this->category, 'طرح ممنوعه', 'forbidden', 2);
        $this->createImage($forbiddenDesign, $this->gold, 'designs/forbidden-gold.png', 1, false);

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Default color is gold => the forbidden design has no allowed image => excluded server-side.
        $component->assertSee('طرح شیر')
            ->assertDontSee('طرح ممنوعه');
        $this->assertSame($this->lionDesign->id, $component->get('design_id'));

        // A direct select attempt cannot bypass the server-side catalog filter.
        $component->call('selectDesign', $forbiddenDesign->id)
            ->assertSet('design_id', $this->lionDesign->id);
    }
}
