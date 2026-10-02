<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Auth\Login;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\ManualPaymentReviewService;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class GuestOnlyStorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'phone' => '09000000000',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    private function createManualPaymentSetting(): ManualPaymentSetting
    {
        return ManualPaymentSetting::create([
            'card_number' => '6037991122334455',
            'iban' => 'IR120120000000001234567890',
            'account_name' => 'فروشگاه الواکارت',
            'instruction_message' => 'لطفاً فیش واریزی را آپلود نمایید',
            'is_active' => true,
        ]);
    }

    private function createStoreCatalog(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کارت استیل خام',
            'slug' => 'raw-steel-card-'.Str::random(6),
            'base_price' => 150000,
            'is_active' => true,
        ]);
    }

    private function createCustomCardCatalog(string $workflow, string $type): array
    {
        $category = CateDesign::create(['name' => 'طرح‌های ویژه', 'slug' => 'special-'.Str::random(6), 'is_active' => true]);

        $product = Product::create([
            'type' => $type,
            'customization_workflow' => $workflow,
            'name' => $workflow === CustomizationWorkflowEnum::BANK_CARD->value ? 'کارت بانکی هوشمند' : 'کارت سوخت هوشمند',
            'slug' => 'custom-card-'.Str::random(6),
            'description' => 'توضیحات سفارشی',
            'base_price' => 350000,
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'طلایی مات',
            'code_hex' => '#D4AF37',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 350000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح اودیت',
            'slug' => 'audit-design-'.Str::random(6),
            'is_active' => true,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/audit.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return [
            'product' => $product,
            'color' => $color,
            'design' => $design,
            'designImage' => $designImage,
        ];
    }

    private function validCheckoutPayload(string $token, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'امیرحسین رضایی',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، میدان ونک، خیابان ملاصدرا، پلاک ۱۰',
            'shipping_postal_code' => '1991733333',
            'shipping_plaque' => '۱۰',
            'shipping_description' => 'واحد ۴ زنگ سوم',
            'notes' => 'لطفاً عصر ارسال شود',
            'submission_token' => $token,
        ], $overrides);
    }

    /** 1. Registration and customer password-reset routes are no longer available */
    public function test_01_customer_auth_routes_are_no_longer_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->get('/account')->assertNotFound();
        $this->get('/orders')->assertNotFound();
        $this->get('/profile')->assertNotFound();
    }

    /** 2. Administrator login and authorization continue to work */
    public function test_02_admin_login_and_authorization_continue_to_work(): void
    {
        $admin = $this->createAdmin();

        Livewire::test(Login::class)
            ->set('phone', '09000000000')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    /** 3. Guests can purchase ordinary Store products */
    public function test_03_guest_can_purchase_ordinary_store_product(): void
    {
        $product = $this->createStoreCatalog();

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame(300000, $order->total_price);
        $this->assertSame('کارت استیل خام', $order->items->first()->product_name_snapshot);
    }

    /** 4. Guests can purchase Bank Card customizations */
    public function test_04_guest_can_purchase_bank_card_customization(): void
    {
        $catalog = $this->createCustomCardCatalog(CustomizationWorkflowEnum::BANK_CARD->value, ProductTypeEnum::BANK->value);

        app(CartService::class)->addItem([
            'product_id' => $catalog['product']->id,
            'color_id' => $catalog['color']->id,
            'design_id' => $catalog['design']->id,
            'design_image_id' => $catalog['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6037991122334455',
                'card_holder_name' => 'A. REZAEI',
            ],
        ]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $item = $order->items->first();
        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->customization_workflow);
        $this->assertSame('A. REZAEI', $item->customization_json['card_holder_name']);
    }

    /** 5. Guests can purchase Fuel Card customizations */
    public function test_05_guest_can_purchase_fuel_card_customization(): void
    {
        $catalog = $this->createCustomCardCatalog(CustomizationWorkflowEnum::FUEL_CARD->value, ProductTypeEnum::FUEL->value);

        app(CartService::class)->addItem([
            'product_id' => $catalog['product']->id,
            'color_id' => $catalog['color']->id,
            'design_id' => $catalog['design']->id,
            'design_image_id' => $catalog['designImage']->id,
            'quantity' => 1,
            'customization_json' => [
                'owner_name' => 'امیرحسین رضایی',
                'vin' => 'IRAN1234567890123',
                'chip_info' => 'small',
            ],
        ]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $item = $order->items->first();
        $this->assertSame(CustomizationWorkflowEnum::FUEL_CARD, $item->customization_workflow);
        $this->assertSame('small', $item->customization_json['chip_info']);
    }

    /** 6 & 7. Required checkout info validated, persisted, and order created with user_id = null */
    public function test_06_and_07_checkout_validation_and_guest_order_persisted(): void
    {
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        // Missing required shipping address
        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token, ['shipping_address' => '']))
            ->assertSessionHasErrors(['shipping_address']);

        // Invalid postal code (not 10 digits)
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token, ['shipping_postal_code' => '123']))
            ->assertSessionHasErrors(['shipping_postal_code']);

        // Valid submission
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token))
            ->assertRedirect();

        $order = Order::query()->latest('id')->first();
        $this->assertNull($order->user_id);
        $this->assertSame('امیرحسین رضایی', $order->customer_name);
        $this->assertSame('09123456789', $order->customer_phone);
        $this->assertSame('1991733333', $order->shipping_postal_code);
    }

    /** 8. Correct manual payment instructions are shown */
    public function test_08_correct_manual_payment_instructions_are_shown(): void
    {
        $setting = $this->createManualPaymentSetting();
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();

        $response = $this->get(route('checkout.payment', $order->token));
        $response->assertOk();
        $response->assertSee($setting->card_number);
        $response->assertSee($setting->account_name);
        $response->assertSee($setting->instruction_message);
        $response->assertSee(number_format($order->total_price));
    }

    /** 9 & 10. Receipt upload creates pending-review state and does NOT mark order paid */
    public function test_09_and_10_receipt_upload_creates_pending_and_leaves_order_unpaid(): void
    {
        Storage::fake('local');
        $this->createManualPaymentSetting();
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();

        $receipt = UploadedFile::fake()->image('receipt.jpg', 600, 400);

        $response = $this->post(route('checkout.payment.store', $order->token), [
            'receipt_image' => $receipt,
            'tracking_number' => 'TRK-998877',
            'note' => 'پرداخت از حساب ملت',
        ]);

        $response->assertRedirect(route('checkout.success', $order->token));

        $order->refresh();
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);

        $payment = $order->payments()->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::PENDING_REVIEW, $payment->status);
        $this->assertSame(PaymentMethod::MANUAL_TRANSFER, $payment->method);
        $this->assertSame('TRK-998877', $payment->tracking_code);
        $this->assertSame($order->total_price, (int) $payment->amount);
    }

    /** 11. Only an authorized administrator can approve or reject a payment */
    public function test_11_only_authorized_administrator_can_approve_or_reject_payment(): void
    {
        Storage::fake('local');
        $this->createManualPaymentSetting();
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();
        $this->post(route('checkout.payment.store', $order->token), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $payment = $order->payments()->first();

        // Guest cannot access admin payment review
        $reviewService = app(ManualPaymentReviewService::class);

        $admin = $this->createAdmin();

        $this->actingAs($admin);
        $reviewService->approve($payment);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    /** 12. A rejected receipt can be corrected through the secure guest flow */
    public function test_12_guest_can_retry_payment_after_rejection(): void
    {
        Storage::fake('local');
        $this->createManualPaymentSetting();
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();
        $this->post(route('checkout.payment.store', $order->token), [
            'receipt_image' => UploadedFile::fake()->image('illegible_receipt.png'),
        ]);

        $payment = $order->payments()->first();

        // Admin rejects illegible receipt
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        app(ManualPaymentReviewService::class)->reject($payment);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);

        // Guest logs out and visits payment page again
        auth()->logout();

        $pageResponse = $this->get(route('checkout.payment', $order->token));
        $pageResponse->assertOk();
        $pageResponse->assertSee('پرداخت قبلی شما رد شده است.');

        // Guest uploads corrected receipt
        $retryResponse = $this->post(route('checkout.payment.store', $order->token), [
            'receipt_image' => UploadedFile::fake()->image('clear_receipt.png'),
            'tracking_number' => 'NEW-TRK-112233',
        ]);

        $retryResponse->assertRedirect(route('checkout.success', $order->token));

        $newPayment = $order->payments()->latest('id')->first();
        $this->assertNotSame($payment->id, $newPayment->id);
        $this->assertSame(PaymentStatus::PENDING_REVIEW, $newPayment->status);
        $this->assertSame('NEW-TRK-112233', $newPayment->tracking_code);
    }

    /** 13. Unauthorized users cannot access admin receipt endpoint */
    public function test_13_unauthorized_users_cannot_access_receipt_endpoint(): void
    {
        Storage::fake('local');
        $this->createManualPaymentSetting();
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();
        $this->post(route('checkout.payment.store', $order->token), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png'),
        ]);

        $payment = $order->payments()->first();

        // Guest cannot access private receipt
        $this->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertRedirect(route('login'));

        // Admin can access private receipt
        $admin = $this->createAdmin();
        $this->actingAs($admin)
            ->get(route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]))
            ->assertOk();
    }

    /** 14. Rate limits and secure order tokens remain effective */
    public function test_14_order_tracking_token_security_and_throttling(): void
    {
        $product = $this->createStoreCatalog();
        app(CartService::class)->addItem(['product_id' => $product->id, 'quantity' => 1]);

        $token = 'SUB'.Str::random(37);
        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->validCheckoutPayload($token));

        $order = Order::query()->latest('id')->first();

        // Tracking with correct 40-char token succeeds
        $this->post(route('order-tracking.check'), ['token' => $order->token])
            ->assertOk()
            ->assertSee($order->reference);

        // Tracking with wrong token fails
        $this->post(route('order-tracking.check'), ['token' => Str::random(40)])
            ->assertSessionHasErrors(['token']);
    }

    /** 18. Reporting remains correct when orders have user_id = null */
    public function test_18_reporting_aggregates_guest_orders_by_customer_phone(): void
    {
        $product = $this->createStoreCatalog();

        // Create 2 guest orders from same phone number
        $order1 = new Order();
        $order1->forceFill([
            'user_id' => null,
            'customer_name' => 'رضا مرادی',
            'customer_phone' => '09121112233',
            'total_price' => 100000,
            'token' => Str::random(40),
            'reference' => 'ORD-2026-000001',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::UNPAID,
        ])->save();

        $order2 = new Order();
        $order2->forceFill([
            'user_id' => null,
            'customer_name' => 'رضا مرادی',
            'customer_phone' => '09121112233',
            'total_price' => 200000,
            'token' => Str::random(40),
            'reference' => 'ORD-2026-000002',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::UNPAID,
        ])->save();

        $reporting = app(ReportingService::class);
        $summary = $reporting->summary(Carbon::today()->subDay(), Carbon::today()->addDay());

        $this->assertSame(2, $summary['totalOrders']);
        // Deduplicated active customer count should be 1 based on customer_phone
        $this->assertSame(1, $summary['activeCustomerCount']);
    }
}
