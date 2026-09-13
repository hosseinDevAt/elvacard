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
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizerPaginationTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $sports;

    private CateDesign $classic;

    private Color $gold;

    private Color $silver;

    /** @var Design[] */
    private array $sportsDesigns = [];

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

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

        for ($i = 1; $i <= 13; $i++) {
            $design = Design::create([
                'cate_design_id' => $this->sports->id,
                'name' => sprintf('طرح-%02d', $i),
                'slug' => sprintf('sport-%02d', $i),
                'is_active' => true,
                'sort_order' => $i,
            ]);

            $image = DesignImage::create([
                'design_id' => $design->id,
                'color_id' => $this->gold->id,
                'image_path' => sprintf('designs/sport-%02d-gold.png', $i),
                'is_active' => true,
                'sort_order' => 1,
            ]);

            DesignColorCompatibility::create([
                'design_image_id' => $image->id,
                'card_color_id' => $this->gold->id,
                'is_allowed' => true,
            ]);

            $this->sportsDesigns[$i] = $design;
        }

        $classicDesign = Design::create([
            'cate_design_id' => $this->classic->id,
            'name' => 'طرح-کلاسیک',
            'slug' => 'classic-one',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $classicImage = DesignImage::create([
            'design_id' => $classicDesign->id,
            'color_id' => $this->gold->id,
            'image_path' => 'designs/classic-one-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $classicImage->id,
            'card_color_id' => $this->gold->id,
            'is_allowed' => true,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی صفحه‌بندی تست',
            'slug' => 'paginated-card',
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
    }

    public function test_catalog_paginates_server_side_with_native_pagination(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Page 1 shows the first 12 sports designs; the 13th stays off-page.
        $component
            ->assertSet('paginators.page', 1)
            ->assertSet('design_id', $this->sportsDesigns[1]->id)
            ->assertSee('طرح-01')
            ->assertSee('طرح-12')
            ->assertDontSee('طرح-13');

        // The next page server-side fetches only the remainder; the previously
        // selected design and its image survive the page switch.
        $component->call('gotoPage', 2)
            ->assertSet('paginators.page', 2)
            ->assertSet('design_id', $this->sportsDesigns[1]->id)
            ->assertSee('طرح-13')
            ->assertDontSee('طرح-01');
    }

    public function test_paginator_reports_accurate_totals_from_the_service(): void
    {
        $catalog = app(DesignCatalogService::class)->paginate(
            $this->sports->id,
            $this->gold->id,
            null,
            CustomizationWorkflowEnum::BANK_CARD,
        );

        $this->assertSame(13, $catalog->total());
        $this->assertSame(2, $catalog->lastPage());
        $this->assertCount(12, $catalog->items());

        foreach ($catalog->items() as $index => $item) {
            $this->assertSame(['id', 'category_id', 'name', 'preview_image_path'], array_keys($item));
            $this->assertSame($this->sportsDesigns[$index + 1]->id, $item['id']);
            $this->assertStringStartsWith('designs/sport-', $item['preview_image_path']);
        }
    }

    public function test_category_change_resets_to_first_page_and_serves_new_category(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $component->call('gotoPage', 2);

        $component->call('selectCategory', $this->classic->id)
            ->assertSet('paginators.page', 1)
            ->assertSet('selected_category_id', $this->classic->id)
            ->assertSee('طرح-کلاسیک')
            ->assertDontSee('طرح-13')
            ->assertDontSee('طرح-01');
    }

    public function test_color_change_resets_to_first_page_and_refilters_catalog(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $component->call('gotoPage', 2);

        // Silver has no sports image => the catalog empties, page resets, and
        // the previous gold selection is dropped.
        $component->call('selectColor', $this->silver->id)
            ->assertSet('paginators.page', 1)
            ->assertSet('color_id', $this->silver->id)
            ->assertSet('design_id', null)
            ->assertSet('design_image_id', null)
            ->assertSee('رنگ انتخابی موجود نیست')
            ->assertDontSee('طرح-01');
    }

    public function test_invalid_design_ids_cannot_bypass_the_server_side_catalog(): void
    {
        $forbidden = Design::create([
            'cate_design_id' => $this->sports->id,
            'name' => 'طرح-ممنوعه',
            'slug' => 'forbidden-pg',
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $forbiddenImage = DesignImage::create([
            'design_id' => $forbidden->id,
            'color_id' => $this->gold->id,
            'image_path' => 'designs/forbidden-pg.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $forbiddenImage->id,
            'card_color_id' => $this->gold->id,
            'is_allowed' => false,
        ]);

        $inactive = Design::create([
            'cate_design_id' => $this->sports->id,
            'name' => 'طرح-غیرفعال',
            'slug' => 'inactive-pg',
            'is_active' => false,
            'sort_order' => 98,
        ]);

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $defaultDesign = $component->get('design_id');

        $classicDesignId = Design::query()->where('cate_design_id', $this->classic->id)->firstOrFail()->id;

        // Cross-category, color-incompatible, and inactive designs are all ignored.
        $component->call('selectDesign', $classicDesignId);
        $this->assertSame($defaultDesign, $component->get('design_id'));

        $component->call('selectDesign', $forbidden->id);
        $this->assertSame($defaultDesign, $component->get('design_id'));

        $component->call('selectDesign', $inactive->id);
        $this->assertSame($defaultDesign, $component->get('design_id'));

        // A fully valid design within the catalog still selects normally.
        $component->call('selectDesign', $this->sportsDesigns[5]->id)
            ->assertSet('design_id', $this->sportsDesigns[5]->id);
    }

    public function test_workflow_tampering_empties_the_catalog_server_side(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->set('workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->assertSee('رنگ انتخابی موجود نیست');

        // Selection attempts while the workflow gate is closed are rejected.
        $component->call('selectDesign', $this->sportsDesigns[5]->id);
        $this->assertSame($this->sportsDesigns[1]->id, $component->get('design_id'));
    }

    public function test_full_page_load_does_not_put_paginated_items_in_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $vars = get_object_vars($component->instance());

        $this->assertArrayNotHasKey('designs', $vars, 'The paginated catalog must never be stored as Livewire state.');
    }
}
