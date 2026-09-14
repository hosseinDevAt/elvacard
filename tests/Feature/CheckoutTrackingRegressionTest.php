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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutTrackingRegressionTest extends TestCase
{
    use RefreshDatabase;

    private array $catalog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalog = $this->createCatalog();
    }

    private function createCatalog(): array
    {
        $category = CateDesign::create([
            'name' => 'تست',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت تست',
            'slug' => 'test-card-regression',
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
            'slug' => 'test-design-regression',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'design-images/test-black.png',
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return compact('product', 'color', 'design', 'designImage', 'category');
    }

    private function addToCart(): void
    {
        app(CartService::class)->addItem([
            'product_id' => $this->catalog['product']->id,
            'color_id' => $this->catalog['color']->id,
            'design_id' => $this->catalog['design']->id,
            'design_image_id' => $this->catalog['designImage']->id,
            'quantity' => 1,
            'customization_json' => [],
        ]);
    }

    private function checkoutPayload(string $phone, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'مشتری تست',
            'customer_phone' => $phone,
            'shipping_address' => 'تهران، خیابان آزادی',
            'shipping_postal_code' => '1234567890',
            'submission_token' => 'SUB'.Str::random(37),
        ], $overrides);
    }

    // ───────── regression: +98 → 09 round-trip ─────────

    public function test_checkout_with_plus98_stores_canonical_and_tracking_finds_it(): void
    {
        $this->addToCart();

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token]);

        $this->post(route('checkout.store'), $this->checkoutPayload('+989123456789', [
            'submission_token' => $token,
        ]))->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('09123456789', $order->customer_phone);

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone' => '09123456789',
        ])->assertSee($order->reference, false);
    }

    public function test_tracking_with_plus98_finds_order_stored_as_09(): void
    {
        $this->addToCart();

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token]);

        $this->post(route('checkout.store'), $this->checkoutPayload('09123456789', [
            'submission_token' => $token,
        ]))->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone' => '+989123456789',
        ])->assertSee($order->reference, false);
    }

    #[DataProvider('alternativeFormatProvider')]
    public function test_checkout_with_various_formats_stores_canonical_and_tracking_matches(
        string $checkoutPhone,
        string $trackingPhone,
    ): void {
        $this->addToCart();

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token]);

        $this->post(route('checkout.store'), $this->checkoutPayload($checkoutPhone, [
            'submission_token' => $token,
        ]))->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('09123456789', $order->customer_phone);

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone' => $trackingPhone,
        ])->assertSee($order->reference, false);
    }

    /** @return array<string, array{string, string}> */
    public static function alternativeFormatProvider(): array
    {
        return [
            '0098 checkout → 09 tracking' => ['00989123456789', '09123456789'],
            '+98 with spaces → 09' => ['+98 912 345 6789', '09123456789'],
            '0098 with dashes → persian' => ['0098-912-345-6789', '۰۹۱۲۳۴۵۶۷۸۹'],
            'persian digits checkout → +98 tracking' => ['۰۹۱۲۳۴۵۶۷۸۹', '+989123456789'],
            'dashes checkout → spaces tracking' => ['0912-345-6789', '091 234 56789'],
        ];
    }

    // ───────── rejection ─────────

    public function test_checkout_rejects_invalid_phone(): void
    {
        $this->addToCart();

        $this->post(route('checkout.store'), $this->checkoutPayload('not-a-phone', [
            'submission_token' => 'SUB'.Str::random(37),
        ]))->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_too_short_phone(): void
    {
        $this->addToCart();

        $this->post(route('checkout.store'), $this->checkoutPayload('0912', [
            'submission_token' => 'SUB'.Str::random(37),
        ]))->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_rejects_phone_not_starting_with_09(): void
    {
        $this->addToCart();

        $this->post(route('checkout.store'), $this->checkoutPayload('08123456789', [
            'submission_token' => 'SUB'.Str::random(37),
        ]))->assertSessionHasErrors('customer_phone');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tracking_rejects_invalid_phone(): void
    {
        $this->post(route('order-tracking.check'), [
            'reference' => 'ORD-2026-000001',
            'customer_phone' => 'not-a-phone',
        ])->assertSessionHasErrors('customer_phone');
    }

    public function test_tracking_rejects_empty_phone(): void
    {
        $this->post(route('order-tracking.check'), [
            'reference' => 'ORD-2026-000001',
            'customer_phone' => '',
        ])->assertSessionHasErrors('customer_phone');
    }

    // ───────── canonical stored (existing behavior guard) ─────────

    public function test_canonical_checkout_phone_is_stored_unchanged(): void
    {
        $this->addToCart();

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token]);

        $this->post(route('checkout.store'), $this->checkoutPayload('09123456789', [
            'submission_token' => $token,
        ]))->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertSame('09123456789', $order->customer_phone);
    }
}
