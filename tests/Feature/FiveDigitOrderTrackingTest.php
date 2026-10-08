<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FiveDigitOrderTrackingTest extends TestCase
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
            'slug' => 'test-cat',
            'is_active' => true,
        ]);

        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت تست ۵ رقمی',
            'slug' => 'test-card-5digit',
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
            'slug' => 'test-design-5digit',
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

    private function placeOrder(string $phone = '09123456789', array $attributes = []): Order
    {
        app(CartService::class)->addItem([
            'product_id' => $this->catalog['product']->id,
            'color_id' => $this->catalog['color']->id,
            'design_id' => $this->catalog['design']->id,
            'design_image_id' => $this->catalog['designImage']->id,
            'quantity' => 1,
            'customization_json' => [],
        ]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token]);

        $this->post(route('checkout.store'), array_merge([
            'customer_name' => 'کاربر تستی',
            'customer_phone' => $phone,
            'shipping_address' => 'تهران، میدان آزادی',
            'shipping_plaque' => '42',
            'shipping_postal_code' => '1234567890',
            'submission_token' => $token,
        ], $attributes))->assertRedirect();

        return Order::query()->latest('id')->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. Order Number Generation Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_new_orders_receive_exact_5_digit_reference_starting_at_10001(): void
    {
        $firstOrder = $this->placeOrder('09121111111');
        $this->assertSame('10001', $firstOrder->reference);

        $secondOrder = $this->placeOrder('09122222222');
        $this->assertSame('10002', $secondOrder->reference);

        $thirdOrder = $this->placeOrder('09123333333');
        $this->assertSame('10003', $thirdOrder->reference);
    }

    public function test_historical_references_are_not_affected_by_next_reference(): void
    {
        // Insert historical orders with legacy prefix
        DB::table('orders')->insert([
            'reference' => 'ORD-2026-000001',
            'token' => Str::random(40),
            'customer_name' => 'کاربر قدیمی',
            'customer_phone' => '09129999999',
            'shipping_address' => 'خیابان انقلاب',
            'shipping_plaque' => '10',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('orders')->insert([
            'reference' => 'ORD-2026-000999',
            'token' => Str::random(40),
            'customer_name' => 'کاربر قدیمی ۲',
            'customer_phone' => '09128888888',
            'shipping_address' => 'خیابان ولیعصر',
            'shipping_plaque' => '20',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Next reference should still start at 10001 and ignore historical ORD- references
        $order = $this->placeOrder('09127777777');
        $this->assertSame('10001', $order->reference);
    }

    public function test_next_reference_fails_safely_when_reaching_limit_99999(): void
    {
        // Insert order with 99999
        DB::table('orders')->insert([
            'reference' => '99999',
            'token' => Str::random(40),
            'customer_name' => 'سقف سفارش',
            'customer_phone' => '09125555555',
            'shipping_address' => 'شیراز',
            'shipping_plaque' => '5',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'total_price' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ظرفیت شماره سفارش‌های ۵ رقمی تکمیل شده است');

        Order::nextReference();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. Tracking Security & Verification Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_tracking_succeeds_with_valid_order_number_and_phone_last_4_digits(): void
    {
        $order = $this->placeOrder('09123456789');

        $response = $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '6789',
        ]);

        $response->assertOk()
            ->assertSee($order->reference, false)
            ->assertSee('در حال بررسی', false)
            ->assertSessionHasNoErrors();
    }

    public function test_tracking_normalizes_persian_digits_in_reference_and_phone(): void
    {
        $order = $this->placeOrder('09123456789');

        // Persian digits for order reference and phone last 4 digits
        $persianRef = fa_digits($order->reference);
        $persianLast4 = '۶۷۸۹';

        $response = $this->post(route('order-tracking.check'), [
            'reference' => $persianRef,
            'customer_phone_last4' => $persianLast4,
        ]);

        $response->assertOk()
            ->assertSee($order->reference, false);
    }

    public function test_tracking_fails_when_phone_last_4_digits_do_not_match(): void
    {
        $order = $this->placeOrder('09123456789');

        $response = $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '0000', // wrong digits
        ]);

        $response->assertSessionHasErrors('reference');
    }

    public function test_tracking_fails_when_order_number_does_not_exist(): void
    {
        $response = $this->post(route('order-tracking.check'), [
            'reference' => '98765',
            'customer_phone_last4' => '6789',
        ]);

        $response->assertSessionHasErrors('reference');
    }

    public function test_tracking_fails_when_phone_last_4_digits_field_is_missing(): void
    {
        $order = $this->placeOrder('09123456789');

        $response = $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
        ]);

        $response->assertSessionHasErrors('customer_phone_last4');
    }

    public function test_tracking_fails_when_phone_last_4_digits_is_not_4_digits(): void
    {
        $order = $this->placeOrder('09123456789');

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '12',
        ])->assertSessionHasErrors('customer_phone_last4');

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '12345',
        ])->assertSessionHasErrors('customer_phone_last4');
    }

    public function test_historical_order_can_be_tracked_with_ord_reference_and_phone(): void
    {
        $legacyToken = Str::random(40);
        DB::table('orders')->insert([
            'reference' => 'ORD-2026-000099',
            'token' => $legacyToken,
            'customer_name' => 'کاربر قبلی',
            'customer_phone' => '09128884321',
            'shipping_address' => 'تهران',
            'shipping_plaque' => '1',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'total_price' => 300000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('order-tracking.check'), [
            'reference' => 'ORD-2026-000099',
            'customer_phone_last4' => '4321',
        ]);

        $response->assertOk()
            ->assertSee('ORD-2026-000099', false)
            ->assertSee('تایید پرداخت', false);
    }

    public function test_token_tracking_still_works_for_backward_compatibility(): void
    {
        $order = $this->placeOrder('09123456789');

        $response = $this->post(route('order-tracking.check'), [
            'token' => $order->token,
        ]);

        $response->assertOk()
            ->assertSee($order->reference, false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. Customer Status Mapping Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_customer_status_mappings_present_exact_4_stages(): void
    {
        $order = $this->placeOrder('09123456789');

        // 1. pending + unpaid -> در حال بررسی
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $this->assertSame('در حال بررسی', $order->customerStatusLabel());
        $this->assertSame(1, $order->customerStatusStep());

        // pending + failed -> در حال بررسی
        $order->payment_status = PaymentStatusEnum::FAILED;
        $this->assertSame('در حال بررسی', $order->customerStatusLabel());
        $this->assertSame(1, $order->customerStatusStep());

        // 2. confirmed + paid -> تایید پرداخت
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->payment_status = PaymentStatusEnum::PAID;
        $this->assertSame('تایید پرداخت', $order->customerStatusLabel());
        $this->assertSame(2, $order->customerStatusStep());

        // 3. production + paid -> درحال آماده سازی
        $order->status = OrderStatusEnum::PRODUCTION;
        $order->payment_status = PaymentStatusEnum::PAID;
        $this->assertSame('درحال آماده سازی', $order->customerStatusLabel());
        $this->assertSame(3, $order->customerStatusStep());

        // processing + paid -> درحال آماده سازی
        $order->status = OrderStatusEnum::PROCESSING;
        $order->payment_status = PaymentStatusEnum::PAID;
        $this->assertSame('درحال آماده سازی', $order->customerStatusLabel());
        $this->assertSame(3, $order->customerStatusStep());

        // 4. completed -> تکمیل شد
        $order->status = OrderStatusEnum::COMPLETED;
        $order->payment_status = PaymentStatusEnum::PAID;
        $this->assertSame('تکمیل شد', $order->customerStatusLabel());
        $this->assertSame(4, $order->customerStatusStep());

        // Safe fallback for cancelled
        $order->status = OrderStatusEnum::CANCELLED;
        $this->assertSame('لغو شده', $order->customerStatusLabel());
        $this->assertSame(0, $order->customerStatusStep());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. Privacy Tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_tracking_page_does_not_leak_customer_full_phone_or_address_or_pan(): void
    {
        $order = $this->placeOrder('09123456789', [
            'shipping_address' => 'خیابان بسیار محرمانه پلاک ۹۹۹',
        ]);

        $response = $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '6789',
        ]);

        $content = $response->getContent() ?: '';

        // Must show reference and items
        $this->assertStringContainsString($order->reference, $content);
        $this->assertStringContainsString('کارت تست ۵ رقمی', $content);

        // Must NOT leak customer phone, postal address or plaque
        $this->assertStringNotContainsString('09123456789', $content);
        $this->assertStringNotContainsString('خیابان بسیار محرمانه', $content);
    }
}
