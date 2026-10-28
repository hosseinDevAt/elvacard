<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutDuplicateOrderRiskTest extends TestCase
{
    use RefreshDatabase;

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

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/test-design.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return ['product' => $product, 'color' => $color, 'design' => $design, 'designImage' => $designImage];
    }

    private function addToCart(array $catalog): void
    {
        app(CartService::class)->addItem([
            'product_id' => $catalog['product']->id,
            'color_id' => $catalog['color']->id,
            'design_id' => $catalog['design']->id,
            'design_image_id' => $catalog['designImage']->id,
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

    /**
     * Case 1: Two submissions using the same token (Double-click or network retry).
     */
    public function test_two_submissions_using_same_token_creates_single_order_and_redirects_second(): void
    {
        $catalog = $this->registerCartCatalog();
        $this->addToCart($catalog);

        $token = 'SUBG'.Str::random(36);
        $this->withSession(['checkout_submission_token' => $token]);

        // First submission
        $res1 = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));
        $res1->assertRedirect();
        $this->assertDatabaseCount('orders', 1);

        $order = Order::first();
        $this->assertSame($token, $order->token);

        // Second submission with identical token (simulating double-click or rapid duplicate)
        $res2 = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));
        $res2->assertRedirect(route('checkout.payment', $order->token));
        $res2->assertSessionHas('info');

        // Total orders MUST remain exactly 1
        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Case 2: Two submissions using different tokens sequentially.
     * The first succeeds and clears cart; the second fails because cart is empty.
     */
    public function test_two_submissions_using_different_tokens_sequentially_second_fails_cart_empty(): void
    {
        $catalog = $this->registerCartCatalog();
        $this->addToCart($catalog);

        $token1 = 'SUB1'.Str::random(36);
        $token2 = 'SUB2'.Str::random(36);

        // First submission succeeds
        $res1 = $this->withSession(['checkout_submission_token' => $token1])
            ->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token1]));
        $res1->assertRedirect();
        $this->assertDatabaseCount('orders', 1);

        // Second submission with a different token against the same session (where cart is now empty)
        $res2 = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token2]));
        $res2->assertSessionHasErrors('cart');

        // Total orders MUST remain exactly 1
        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Case 3: Retry after a timeout or network drop where the order was persisted.
     */
    public function test_retry_after_timeout_or_redirect_safely_resolves_existing_order(): void
    {
        $catalog = $this->registerCartCatalog();
        $this->addToCart($catalog);

        $token = 'SUBTIMEOUT'.Str::random(30);

        // Order was created by the first attempt
        $res1 = $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));
        $res1->assertRedirect();

        $order = Order::where('token', $token)->firstOrFail();

        // User retries submission from browser retry dialog with same token
        $resRetry = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));
        $resRetry->assertRedirect(route('checkout.payment', $order->token));
        $resRetry->assertSessionHas('info');

        $this->assertDatabaseCount('orders', 1);
    }

    /**
     * Case 4: Back navigation and refresh behavior.
     */
    public function test_refresh_or_back_navigation_followed_by_submission_prevents_duplicate(): void
    {
        $catalog = $this->registerCartCatalog();
        $this->addToCart($catalog);

        $token = 'SUBNAV'.Str::random(34);

        // Step 1: Initial checkout succeeds
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));

        $order = Order::where('token', $token)->firstOrFail();

        // Sub-case 4A: Browser BFCache re-submits the old form with token
        $resBackSubmit = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token]));
        $resBackSubmit->assertRedirect(route('checkout.payment', $order->token));
        $this->assertDatabaseCount('orders', 1);

        // Sub-case 4B: Browser performs GET /checkout after order completed (cart is now empty)
        $resCheckoutGet = $this->get(route('checkout.index'));
        $resCheckoutGet->assertRedirect(route('cart.index'));
        $resCheckoutGet->assertSessionHas('error', 'سبد خرید شما خالی است.');
    }

    /**
     * Case 5: Legitimate second order is permitted when cart is refilled.
     */
    public function test_legitimate_second_order_succeeds_when_cart_is_refilled(): void
    {
        $catalog = $this->registerCartCatalog();

        // Order 1
        $this->addToCart($catalog);
        $token1 = 'SUBORDER1'.Str::random(30);
        $this->withSession(['checkout_submission_token' => $token1])
            ->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token1]));

        $this->assertDatabaseCount('orders', 1);
        $order1 = Order::first();

        // Customer intentionally adds items again for a 2nd order
        $this->addToCart($catalog);

        // Customer visits checkout: fresh token generated in session
        $resGet = $this->get(route('checkout.index'));
        $resGet->assertOk();
        $token2 = session('checkout_submission_token');
        $this->assertNotEmpty($token2);
        $this->assertNotEquals($token1, $token2);

        // Customer submits 2nd order
        $resOrder2 = $this->post(route('checkout.store'), $this->checkoutPayload(['submission_token' => $token2]));
        $resOrder2->assertRedirect();

        $this->assertDatabaseCount('orders', 2);
        $order2 = Order::latest('id')->first();
        $this->assertNotEquals($order1->id, $order2->id);
        $this->assertNotEquals($order1->reference, $order2->reference);
    }
}
