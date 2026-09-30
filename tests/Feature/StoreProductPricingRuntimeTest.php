<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreProductPricingRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private function storeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کالای فروشگاهی',
            'slug' => 'runtime-pricing-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ], $overrides));
    }

    private function color(string $name = 'طلایی'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function variant(Product $product, Color $color, int $price): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    public function test_simple_product_displays_and_charges_its_single_price(): void
    {
        $product = $this->storeProduct(['base_price' => 380000]);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('افزودن به سبد خرید');
        $response->assertSee('380,000');
        $response->assertDontSee('name="color_id"');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        $item = app(CartService::class)->getCart()['items'][0];
        $this->assertSame(380000, (int) $item['unit_price_snapshot']);
    }

    public function test_variable_product_resolves_selected_variant_price_and_snapshots_it_into_the_order(): void
    {
        $product = $this->storeProduct();
        $gold = $this->color('طلایی');
        $silver = $this->color('نقره‌ای');
        $this->variant($product, $gold, 300000);
        $this->variant($product, $silver, 420000);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $silver->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        $item = app(CartService::class)->getCart()['items'][0];
        $this->assertSame($silver->id, (int) $item['color_id']);
        $this->assertSame(420000, (int) $item['unit_price_snapshot']);
        $this->assertSame('نقره‌ای', $item['color_name_snapshot']);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'کاربر تست',
            'customer_phone' => '09120000000',
        ]);

        $orderItem = $order->items()->firstOrFail();
        $this->assertSame(420000, (int) $orderItem->unit_price_snapshot);
    }

    public function test_cart_ignores_a_forged_client_price_and_charges_the_catalog_price(): void
    {
        $product = $this->storeProduct(['base_price' => 250000]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1,
            'unit_price' => 1000,
            'unit_price_snapshot' => 999,
            'final_price' => 5,
        ])->assertRedirect(route('cart.index'));

        $item = app(CartService::class)->getCart()['items'][0];
        $this->assertSame(250000, (int) $item['unit_price_snapshot']);
        $this->assertSame(250000, (int) $item['final_price']);
    }

    public function test_catalog_price_changes_are_reflected_on_the_next_cart_revalidation(): void
    {
        $product = $this->storeProduct(['base_price' => 250000]);
        $color = $this->color();
        $this->variant($product, $color, 310000);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $color->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        ProductColorPrice::where('product_id', $product->id)->where('color_id', $color->id)
            ->update(['price' => 410000]);

        $cart = app(CartService::class)->getCart();
        $this->assertSame(310000, (int) $cart['items'][0]['unit_price_snapshot']);
    }
}
