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
use App\Models\HomepageSection;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontPurchaseabilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(string $name, bool $isActive = true): CateDesign
    {
        return CateDesign::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.uniqid(),
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);
    }

    private function makeColor(): Color
    {
        return Color::create([
            'name' => 'رنگ آزمون',
            'code_hex' => '#123456',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function makeDesignPath(CateDesign $category, Color $color): Design
    {
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح آزمون',
            'slug' => 'design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $design;
    }

    private function makeCommerceProduct(int $price, string $name): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'commerce-'.uniqid(),
            'base_price' => $price,
            'is_active' => true,
        ]);
    }

    private function makeNoPriceCommerceProduct(string $name): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'no-price-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function makeBankProduct(Color $color, string $name, bool $purchasableDesign): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => $name,
            'slug' => 'bank-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        if ($purchasableDesign) {
            $this->makeDesignPath($this->makeCategory('دسته بانک'), $color);
        }

        return $product;
    }

    private function makeFuelProduct(string $name): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => $name,
            'slug' => 'fuel-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);
    }

    public function test_active_standard_without_price_is_hidden_from_catalog_and_404_on_detail(): void
    {
        $broken = $this->makeNoPriceCommerceProduct('کارت بدون قیمت');

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertDontSee('کارت بدون قیمت');

        $this->get(route('catalog.products.show', $broken->slug))
            ->assertNotFound();
    }

    public function test_active_bank_without_design_path_is_hidden_and_404_everywhere(): void
    {
        $broken = $this->makeBankProduct($this->makeColor(), 'کارت بانکی بدون طرح', false);

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertDontSee('کارت بانکی بدون طرح');

        $this->get(route('catalog.products.show', $broken->slug))
            ->assertNotFound();

        Livewire::test(ProductCustomizer::class, ['productId' => $broken->id])
            ->assertStatus(404);
    }

    public function test_fuel_with_two_active_prices_fails_readiness_and_is_hidden_from_storefront(): void
    {
        $product = $this->makeFuelProduct('کارت سوخت خراب');
        $firstColor = $this->makeColor();
        $secondColor = $this->makeColor();

        $this->makeDesignPath($this->makeCategory('دسته سوخت'), $firstColor);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $firstColor->id,
            'price' => 450000,
            'is_active' => true,
        ]);
        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $secondColor->id,
            'price' => 480000,
            'is_active' => true,
        ]);

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertDontSee('کارت سوخت خراب');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('کارت سوخت خراب');

        $this->get(route('catalog.products.show', $product->slug))
            ->assertNotFound();

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);
    }

    public function test_homepage_skips_featured_product_that_is_not_purchasable(): void
    {
        $valid = $this->makeCommerceProduct(120000, 'کارت سالم');
        $broken = $this->makeBankProduct($this->makeColor(), 'کارت ناقص', false);

        HomepageSection::create([
            'section_type' => 'featured_products',
            'title' => 'ویترین',
            'settings' => ['product_ids' => [$valid->id, $broken->id], 'limit' => 6],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('کارت سالم')
            ->assertDontSee('کارت ناقص');
    }

    public function test_homepage_skips_featured_design_with_inactive_category(): void
    {
        $activeDesign = $this->makeDesignPath($this->makeCategory('دسته فعال طرح'), $this->makeColor());
        $inactiveDesign = $this->makeDesignPath($this->makeCategory('دسته معوق طرح', false), $this->makeColor());

        HomepageSection::create([
            'section_type' => 'featured_designs',
            'title' => 'طرح‌های منتخب',
            'settings' => ['design_ids' => [$activeDesign->id, $inactiveDesign->id], 'limit' => 6],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('طرح آزمون')
            ->assertDontSee('دسته معوق طرح');
    }

    public function test_menu_resolver_returns_null_for_broken_product_and_design(): void
    {
        $menu = Menu::create(['name' => 'اصلی', 'location' => 'header']);

        $brokenProduct = $this->makeBankProduct($this->makeColor(), 'کارت لینک‌خورده', false);
        $brokenDesign = $this->makeDesignPath($this->makeCategory('دسته لینک طرح', false), $this->makeColor());
        $validProduct = $this->makeCommerceProduct(120000, 'کارت لینک سالم');
        $validDesign = $this->makeDesignPath($this->makeCategory('دسته لینک فعال'), $this->makeColor());

        $productItem = MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'product',
            'title' => 'محصول',
            'target_id' => $brokenProduct->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->assertNull($productItem->resolveUrl());

        $designItem = MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'design',
            'title' => 'طرح',
            'target_id' => $brokenDesign->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->assertNull($designItem->resolveUrl());

        $validProductItem = MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'product',
            'title' => 'محصول سالم',
            'target_id' => $validProduct->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->assertNotNull($validProductItem->resolveUrl());

        $validDesignItem = MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'design',
            'title' => 'طرح سالم',
            'target_id' => $validDesign->id,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->assertNotNull($validDesignItem->resolveUrl());
    }

    public function test_purchasable_products_remain_visible_in_catalog_and_detail(): void
    {
        $commerce = $this->makeCommerceProduct(120000, 'کالای معمولی سالم');
        $bankColor = $this->makeColor();
        $bank = $this->makeBankProduct($bankColor, 'کارت بانکی کامل', true);
        $fuelColor = $this->makeColor();
        $fuel = $this->makeFuelProduct('کارت سوخت کامل');
        $this->makeDesignPath($this->makeCategory('دسته سوخت کامل'), $fuelColor);
        ProductColorPrice::create([
            'product_id' => $fuel->id,
            'color_id' => $fuelColor->id,
            'price' => 480000,
            'is_active' => true,
        ]);

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertSee('کالای معمولی سالم')
            ->assertSee('کارت بانکی کامل')
            ->assertSee('کارت سوخت کامل');

        $this->get(route('catalog.products.show', $commerce->slug))
            ->assertOk()
            ->assertSee('افزودن به سبد خرید');

        $this->get(route('catalog.products.show', $bank->slug))
            ->assertOk()
            ->assertSee('انتخاب طرح لیزر روی کارت');

        $this->get(route('catalog.products.show', $fuel->slug))
            ->assertOk();

        Livewire::test(ProductCustomizer::class, ['productId' => $bank->id])
            ->assertStatus(200)
            ->assertSet('workflow', CustomizationWorkflowEnum::BANK_CARD->value);

        Livewire::test(ProductCustomizer::class, ['productId' => $fuel->id])
            ->assertStatus(200)
            ->assertSet('workflow', CustomizationWorkflowEnum::FUEL_CARD->value);
    }

    public function test_unknown_workflow_page_keeps_amber_message_but_never_an_actionable_surface(): void
    {
        $product = $this->makeNoPriceCommerceProduct('کارت سرویس قطع');

        DB::table('products')
            ->where('id', $product->id)
            ->update(['customization_workflow' => 'vaporwave']);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('هنوز قابل خرید نیست')
            ->assertDontSee('افزودن به سبد خرید');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);
    }
}
