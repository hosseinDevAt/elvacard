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

class ProductCustomizerInactiveCategoryTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $activeCategory;

    private CateDesign $secondCategory;

    private CateDesign $inactiveCategory;

    private Design $hiddenDesign;

    private Color $gold;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activeCategory = CateDesign::create([
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

        $this->inactiveCategory = CateDesign::create([
            'name' => 'آرشیو مخفی',
            'slug' => 'archived',
            'is_active' => false,
            'sort_order' => 99,
        ]);

        $this->gold = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $visibleDesign = $this->createDesign($this->activeCategory, 'طرح قابل مشاهده', 'visible', 1);
        $visibleImage = $this->createImage($visibleDesign);

        $this->hiddenDesign = $this->createDesign($this->inactiveCategory, 'طرح پنهان', 'hidden', 1);
        $this->createImage($this->hiddenDesign);

        DesignColorCompatibility::create([
            'design_image_id' => $visibleImage->id,
            'card_color_id' => $this->gold->id,
            'is_allowed' => true,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی تست مخفی‌سازی',
            'slug' => 'hidden-category-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->gold->id,
            'price' => 600000,
            'is_active' => true,
        ]);
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

    private function createImage(Design $design): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->gold->id,
            'image_path' => 'designs/'.$design->slug.'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_inactive_category_does_not_appear_in_active_categories(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $ids = array_column($component->get('categories'), 'id');

        $this->assertContains($this->activeCategory->id, $ids);
        $this->assertContains($this->secondCategory->id, $ids);
        $this->assertNotContains($this->inactiveCategory->id, $ids);

        $component->assertDontSee('آرشیو مخفی');
    }

    public function test_paginate_does_not_return_designs_from_inactive_category(): void
    {
        $service = app(DesignCatalogService::class);

        $fromInactive = $service->paginate(
            $this->inactiveCategory->id,
            $this->gold->id,
            null,
            CustomizationWorkflowEnum::BANK_CARD,
        );
        $this->assertSame(0, $fromInactive->total());

        // The strongest guard check: browsing every category must not surface
        // designs whose category is inactive.
        $allActive = $service->paginatePublic(null, null, null);
        $this->assertNotContains(
            $this->hiddenDesign->id,
            array_column($allActive->items(), 'id'),
            'An active design inside an inactive category must never be browsed.',
        );
    }

    public function test_is_design_in_catalog_rejects_design_from_inactive_category(): void
    {
        $service = app(DesignCatalogService::class);

        $this->assertFalse(
            $service->isDesignInCatalog(
                $this->hiddenDesign->id,
                $this->inactiveCategory->id,
                null,
                CustomizationWorkflowEnum::BANK_CARD,
            ),
            'A design whose category is inactive must fail the catalog validity check.',
        );
    }

    public function test_tampered_category_selection_cannot_bypass_the_service_guard(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $defaultDesign = $component->get('design_id');

        // selected_category_id is a public Livewire property: a crafted payload
        // could hydrate it directly. The service-level guard must still hide
        // the inactive category's designs.
        $component->set('selected_category_id', $this->inactiveCategory->id)
            ->assertSet('selected_category_id', $this->inactiveCategory->id)
            ->assertDontSee('طرح پنهان');

        $component->call('selectDesign', $this->hiddenDesign->id);
        $this->assertSame($defaultDesign, $component->get('design_id'));
    }

    public function test_select_category_membership_guard_ignores_inactive_category(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->call('selectCategory', $this->inactiveCategory->id)
            ->assertSet('selected_category_id', $this->activeCategory->id);
    }
}
