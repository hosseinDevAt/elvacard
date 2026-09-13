<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutAddressTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{product: Product, color: Color, design: Design} */
    private function registerCartCatalog(): array
    {
        $category = CateDesign::create(['name' => 'تست', 'slug' => 'test-category', 'is_active' => true]);

        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت تست',
            'slug' => 'test-card',
            'description' => 'desc',
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 250000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تست',
            'slug' => 'test-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return ['product' => $product, 'color' => $color, 'design' => $design];
    }

    private function addToCart(array $catalog): void
    {
        app(CartService::class)->addItem([
            'product_id' => $catalog['product']->id,
            'color_id' => $catalog['color']->id,
            'design_id' => $catalog['design']->id,
            'quantity' => 1,
            'customization_json' => [],
        ]);
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، خیابان آزادی، کوچه ۱۲',
            'shipping_postal_code' => '1234567890',
            'shipping_plaque' => '۱۲',
            'shipping_description' => 'طبقه دوم، واحد ۳',
            'notes' => 'توضیحات سفارش',
        ], $overrides);
    }

    public function test_checkout_stores_the_shipping_address_snapshot(): void
    {
        $this->addToCart($this->registerCartCatalog());

        $token = 'SUBG'.Str::random(36);
        $this->withSession(['checkout_submission_token' => $token]);

        $response = $this->get(route('checkout.index'));
        $response->assertOk();

        $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertSame('مشتری تست', $order->customer_name);
        $this->assertSame('09123456789', $order->customer_phone);
        $this->assertSame('تهران، خیابان آزادی، کوچه ۱۲', $order->shipping_address);
        $this->assertSame('1234567890', $order->shipping_postal_code);
        $this->assertSame('۱۲', $order->shipping_plaque);
        $this->assertSame('طبقه دوم، واحد ۳', $order->shipping_description);
        $this->assertSame('توضیحات سفارش', $order->notes);
        $this->assertSame(250000, $order->total_price);
        $this->assertSame(1, $order->items()->count());
    }

    public function test_checkout_requires_shipping_address(): void
    {
        $this->registerCartCatalog();

        $response = $this->post(route('checkout.store'), $this->checkoutPayload([
            'shipping_address' => '',
        ]));

        $response->assertSessionHasErrors('shipping_address');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_requires_a_valid_postal_code(): void
    {
        $this->registerCartCatalog();

        $response = $this->post(route('checkout.store'), $this->checkoutPayload([
            'shipping_postal_code' => 'abc123',
        ]));

        $response->assertSessionHasErrors('shipping_postal_code');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_prefills_address_from_the_customer_profile(): void
    {
        $this->addToCart($this->registerCartCatalog());

        $user = User::factory()->create([
            'address' => 'اصفهان، خیابان چهارباغ',
            'postal_code' => '8156111111',
            'plaque' => '۳',
        ]);

        $response = $this->actingAs($user)->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('اصفهان، خیابان چهارباغ', false);
        $response->assertSee('8156111111', false);
    }

    public function test_guest_checkout_has_no_profile_prefill(): void
    {
        $this->addToCart($this->registerCartCatalog());

        $response = $this->get(route('checkout.index'));

        $response->assertOk();
        $response->assertDontSee('اصفهان، خیابان چهارباغ', false);
    }

    public function test_checkout_redirects_to_cart_when_cart_is_empty(): void
    {
        $response = $this->get(route('checkout.index'));

        $response->assertRedirect(route('cart.index'));
    }
}
