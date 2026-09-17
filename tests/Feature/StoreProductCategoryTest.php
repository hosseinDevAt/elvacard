<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, bool $active = true): ProductCategory
    {
        return ProductCategory::create([
            'name' => $name,
            'slug' => 'store-cat-'.uniqid(),
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function product(string $name, ?ProductCategory $category = null): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'store-cat-product-'.uniqid(),
            'base_price' => 250000,
            'product_category_id' => $category?->id,
            'is_active' => true,
        ]);
    }

    public function test_store_index_filters_products_by_category(): void
    {
        $bags = $this->category('کیف و کاور');
        $wallets = $this->category('کیف پول');

        $this->product('محصول کیف کاور', $bags);
        $this->product('محصول کیف پول', $wallets);

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertSee('محصول کیف کاور')
            ->assertSee('محصول کیف پول');

        $this->get(route('catalog.products.index', ['category' => $bags->slug]))
            ->assertOk()
            ->assertSee('محصول کیف کاور')
            ->assertDontSee('محصول کیف پول')
            ->assertSee('کیف و کاور');
    }

    public function test_inactive_category_filter_is_ignored(): void
    {
        $inactive = $this->category('دسته غیرفعال', false);
        $this->product('محصول دسته غیرفعال', $inactive);

        $this->get(route('catalog.products.index', ['category' => $inactive->slug]))
            ->assertOk()
            ->assertSee('محصول دسته غیرفعال');
    }

    public function test_product_card_shows_its_category_name(): void
    {
        $category = $this->category('لوازم جانبی');
        $this->product('محصول لوازم جانبی', $category);

        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertSee('لوازم جانبی');
    }
}
