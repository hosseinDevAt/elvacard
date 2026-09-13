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

    public function test_catalog_state_is_slim_scalar_arrays_not_eloquent_models(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])->instance();

        $this->assertIsArray($component->categories);
        $this->assertIsArray($component->designs);
        $this->assertIsArray($component->designImages);
        $this->assertIsArray($component->colorPrices);

        $this->assertSame($this->category->id, $component->categories[0]['id']);
        $this->assertSame($this->category->name, $component->categories[0]['name']);
        $this->assertSame($this->lionDesign->id, $component->designs[0]['id']);
        $this->assertSame($this->category->id, $component->designs[0]['category_id']);
        $this->assertSame('designs/lion-gold.png', $component->designs[0]['preview_image_path']);

        $this->assertSame($this->lionGoldImage->id, $component->designImages[0]['id']);
        $this->assertSame($this->lionDesign->id, $component->designImages[0]['design_id']);
        $this->assertSame($this->gold->name, $component->designImages[0]['color_name']);

        // The catalog must be flat scalars: no nested collections, no models, no compat payload.
        $this->assertArrayNotHasKey('images', $component->designs[0]);
        $this->assertArrayNotHasKey('compatibilities', $component->designImages[0]);
        $this->assertArrayNotHasKey('card_color', $component->designImages[0]);
    }

    public function test_legacy_catalog_state_properties_are_not_persisted(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])->instance();

        $vars = get_object_vars($component);

        foreach (['catalog', 'designOptions', 'designImageOptions', 'product'] as $legacy) {
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

    public function test_category_change_switches_available_designs_without_querying_catalog(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectCategory', $this->secondCategory->id)
            ->assertSet('selected_category_id', $this->secondCategory->id);

        $this->assertCount(0, $this->designTableQueries(), 'Category switching must not re-query the design catalog.');

        $designs = collect($component->get('designs'))->where('category_id', $this->secondCategory->id)->values();
        $this->assertCount(1, $designs);
        $this->assertSame($this->eagleDesign->id, $designs[0]['id']);
    }

    public function test_mount_loads_only_selected_design_images_into_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // The full design grid is intentional, but only the SELECTED design's image chips enter the state.
        $designs = collect($component->get('designs'))->values();
        $this->assertSame(
            [$this->lionDesign->id, $this->eagleDesign->id],
            $designs->pluck('id')->all()
        );

        $designImages = $component->get('designImages');
        $this->assertCount(1, $designImages);
        $this->assertSame($this->lionGoldImage->id, $designImages[0]['id']);
        $this->assertSame($this->lionDesign->id, $designImages[0]['design_id']);
        $this->assertSame($this->lionDesign->id, $component->get('design_id'));
    }

    public function test_design_change_lazily_updates_image_options(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->call('selectDesign', $this->eagleDesign->id)
            ->assertSet('design_id', $this->eagleDesign->id)
            ->assertSet('design_image_id', $this->eagleGoldImage->id);

        // Switching designs swaps ONLY the chips: the grid (designs) is untouched and still complete.
        $this->assertCount(2, $component->get('designs'));
        $this->assertSame([$this->eagleGoldImage->id], array_column($component->get('designImages'), 'id'));
    }

    public function test_select_design_lazily_queries_only_selected_design_images(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectDesign', $this->eagleDesign->id);

        $this->assertCount(0, $this->queriesForDesignTable('cate_designs'), 'Design selection must not reload the category list.');
        $this->assertCount(0, $this->queriesForDesignTable('designs'), 'Design selection must not reload the full design list.');
        $this->assertCount(1, $this->queriesForDesignTable('design_images'), "Design selection must query only the selected design's images.");
        $this->assertCount(1, $this->queriesForDesignTable('design_color_compatibilities'), 'Design selection must perform a single color-compatibility lookup.');

        $this->assertSame([$this->eagleGoldImage->id], array_column($component->get('designImages'), 'id'));
    }

    public function test_image_change_sets_correct_design_without_querying_catalog(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->call('selectDesign', $this->eagleDesign->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $component->call('selectDesignImage', $this->eagleGoldImage->id)
            ->assertSet('design_image_id', $this->eagleGoldImage->id)
            ->assertSet('design_id', $this->eagleDesign->id);

        $this->assertCount(0, $this->designTableQueries(), 'Image switching must not re-query the design catalog.');
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

        $component->call('selectColor', $this->silver->id)
            ->assertSet('color_id', $this->silver->id)
            ->assertSet('design_id', $this->eagleDesign->id)
            ->assertSet('design_image_id', $this->eagleSilverImage->id);

        // The gold-only lion image must be dropped: only the silver-compatible design remains.
        $designs = collect($component->get('designs'))->values();
        $this->assertCount(1, $designs);
        $this->assertSame($this->eagleDesign->id, $designs[0]['id']);
    }

    public function test_incompatible_design_is_excluded_from_state(): void
    {
        $forbiddenDesign = $this->createDesign($this->category, 'طرح ممنوعه', 'forbidden', 2);
        $this->createImage($forbiddenDesign, $this->gold, 'designs/forbidden-gold.png', 1, false);

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Default color is gold => the forbidden design has no allowed image => absent from state.
        $designs = collect($component->get('designs'))->where('category_id', $this->category->id)->values();
        $this->assertCount(1, $designs);
        $this->assertSame($this->lionDesign->id, $designs[0]['id']);
    }
}
