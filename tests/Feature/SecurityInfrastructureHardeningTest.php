<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderStateMachine;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * N-Onyx-47 â€” Authentication, Guest Access & Payment Infrastructure Hardening.
 *
 * Two areas, each asserted against a real attack rather than a description:
 *
 *   SEC-001  guest order lookup must not be enumerable
 *   OP-01    a fake gateway must never be a production payment path
 */
class SecurityInfrastructureHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CateDesign::create([
            'name' => 'Ø¯Ø³ØªÙ‡ Ø§Ù…Ù†ÛŒØª',
            'slug' => 'sec-47-'.uniqid(),
            'is_active' => true,
        ]);

        $this->color = Color::create([
            'name' => 'Ù…Ø´Ú©ÛŒ',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'Ú©Ø§Ø±Øª Ø§Ù…Ù†ÛŒØª',
            'slug' => 'sec-47-'.uniqid(),
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'price' => 250000,
            'is_active' => true,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'Ø·Ø±Ø­ Ø§Ù…Ù†ÛŒØª',
            'slug' => 'sec-47-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'design-images/sec-47.png',
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    // =========================================================================
    // SEC-001 â€” GUEST ORDER LOOKUP MUST NOT BE ENUMERABLE
    // =========================================================================

    public function test_the_sequential_reference_alone_cannot_authorise_a_guest_lookup(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->assertMatchesRegularExpression(
            '/^[0-9]{5}$/',
            $order->reference,
            'Sanity: the reference is sequential 5 digits, so it can never be a secret alone.',
        );

        // The predictable reference alone without the correct phone last 4 digits
        // must not authorise anything.
        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
        ])->assertSessionHasErrors('customer_phone_last4');

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '9999', // wrong last 4 digits
        ])->assertSessionHasErrors('reference');

        $this->assertGuest();
    }

    public function test_a_guest_can_track_an_order_with_their_tracking_code(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '0000',
        ])
            ->assertOk()
            ->assertSee($order->reference, false);
    }

    public function test_a_wrong_tracking_code_leaks_nothing(): void
    {
        $order = $this->placeOrder('09120000000');

        $response = $this->post(route('order-tracking.check'), [
            'reference' => '99999',
            'customer_phone_last4' => '1234',
        ]);

        $response->assertSessionHasErrors('reference');

        $content = $response->getContent() ?: '';

        $this->assertStringNotContainsString($order->reference, $content);
        $this->assertStringNotContainsString('09120000000', $content);
        $this->assertGuest();
    }

    public function test_walking_the_sequential_reference_range_confirms_nothing(): void
    {
        $order = $this->placeOrder('09120000000');

        $forged = ['10001', '10002', '10003', '10004', '10005'];

        foreach ($forged as $guess) {
            $response = $this->post(route('order-tracking.check'), [
                'reference' => $guess,
                'customer_phone_last4' => '9999', // wrong phone
            ]);

            $response->assertSessionHasErrors('reference');
            $this->assertStringNotContainsString(
                $order->reference,
                $response->getContent() ?: '',
                'Enumerating sequential references must never confirm that an order exists.',
            );
        }
    }

    public function test_tracking_an_order_never_creates_an_authenticated_session(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '0000',
        ])->assertOk();

        $this->assertGuest();
    }

    public function test_tracking_does_not_expose_a_receipt_or_any_admin_surface(): void
    {
        $order = $this->placeOrder('09120000000');

        $payment = $order->payments()->create([
            'method' => PaymentMethod::MANUAL_TRANSFER,
            'status' => PaymentStatus::SUCCESS,
            'amount' => (int) $order->total_price,
            'paid_amount' => (int) $order->total_price,
            'receipt_path' => 'receipts/secret-receipt.pdf',
            'paid_at' => now(),
        ]);

        $content = $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '0000',
        ])
            ->assertOk()
            ->getContent() ?: '';

        $this->assertStringNotContainsString('secret-receipt.pdf', $content);
        $this->assertStringNotContainsString(
            route('admin.payments.receipt', [$order->id, $payment->id]),
            $content,
            'Guest tracking must never link to the admin receipt.',
        );

        // The admin receipt route must stay closed to a normal customer.
        $this->actingAs($this->customer())
            ->get(route('admin.payments.receipt', [$order->id, $payment->id]))
            ->assertForbidden();
    }

    public function test_guest_tracking_is_rate_limited(): void
    {
        $response = null;

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $response = $this->post(route('order-tracking.check'), [
                'reference' => '10001',
                'customer_phone_last4' => '1234',
            ]);
        }

        $this->assertNotNull($response);
        $this->assertSame(
            429,
            $response->getStatusCode(),
            'Guest order lookup must be throttled so it cannot be enumerated online.',
        );
    }

    public function test_the_tracking_code_is_long_and_unique_per_order(): void
    {
        $first = $this->placeOrder('09120000001');
        $second = $this->placeOrder('09120000002');

        $this->assertGreaterThanOrEqual(32, strlen($first->token));
        $this->assertNotSame($first->token, $first->reference);
        $this->assertNotSame($first->token, $second->token);
    }

    public function test_a_customer_is_handed_a_tracking_link_at_checkout(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->get(route('checkout.success', $order->token))
            ->assertOk()
            ->assertSee(route('order-tracking.index'), false)
            ->assertSee($order->token, false);
    }

    public function test_a_blocked_account_is_logged_out_even_with_a_valid_session(): void
    {
        $blocked = $this->customer();
        $blocked->forceFill(['is_active' => false])->save();

        $this->actingAs($blocked)
            ->get(route('home'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    // =========================================================================
    // OP-01 â€” A FAKE GATEWAY MUST NEVER BE A PRODUCTION PAYMENT PATH
    // =========================================================================

    public function test_no_gateway_at_all_is_registered_for_production(): void
    {
        $registered = config('payments.gateways', []);

        foreach ($registered as $name => $class) {
            $this->assertStringStartsNotWith(
                'Tests\\',
                $class,
                "Gateway [{$name}] must never point at a test double.",
            );
        }

        $this->assertSame(
            [],
            $registered,
            'Online payment must stay unavailable rather than run on a stub.',
        );
    }

    public function test_resolving_any_gateway_fails_while_none_is_configured(): void
    {
        $manager = app(PaymentGatewayManager::class);

        $this->assertSame([], $manager->names());

        $this->expectException(UnknownPaymentGatewayException::class);

        $manager->resolve('zarinpal');
    }

    public function test_a_gateway_name_cannot_be_smuggled_in_as_a_class_name(): void
    {
        $this->expectException(UnknownPaymentGatewayException::class);

        app(PaymentGatewayManager::class)->resolve(FakePaymentGateway::class);
    }

    public function test_an_unregistered_gateway_cannot_start_a_payment(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->post(route('checkout.payment.gateway.initiate', $order->token), [
            'gateway' => 'fake',
        ])->assertSessionHasErrors('payment');

        $this->assertFalse(
            $order->payments()->where('method', PaymentMethod::GATEWAY->value)->exists(),
            'An unregistered gateway must never create a payment.',
        );
    }

    public function test_a_forged_callback_cannot_settle_a_payment(): void
    {
        $order = $this->placeOrder('09120000000');

        $payment = $order->payments()->create([
            'method' => PaymentMethod::GATEWAY,
            'status' => PaymentStatus::PENDING,
            'amount' => (int) $order->total_price,
            'gateway' => 'fake',
            'transaction_id' => 'TXN-'.Str::random(10),
        ]);

        $this->postJson(route('checkout.payment.callback', 'fake'), [
            'reference' => $payment->transaction_id,
            'status' => 'success',
            'amount' => (int) $order->total_price,
            'paid_amount' => (int) $order->total_price,
        ])->assertNotFound();

        $this->assertSame(
            PaymentStatus::PENDING,
            $payment->fresh()->status,
            'A forged callback must never settle a payment.',
        );

        $this->assertSame(
            PaymentStatusEnum::UNPAID,
            $order->fresh()->payment_status,
            'A forged callback must never mark an order paid.',
        );
    }

    public function test_a_callback_for_an_unregistered_gateway_is_rejected(): void
    {
        $this->postJson(route('checkout.payment.callback', 'not-a-gateway'), [
            'reference' => 'anything',
        ])->assertNotFound();
    }

    public function test_a_cancelled_order_cannot_be_settled_by_a_payment(): void
    {
        $order = $this->placeOrder('09120000000');

        app(OrderStateMachine::class)->transition($order, OrderStatusEnum::CANCELLED);

        $payment = $order->payments()->create([
            'method' => PaymentMethod::GATEWAY,
            'status' => PaymentStatus::PENDING,
            'amount' => (int) $order->total_price,
            'gateway' => 'fake',
            'transaction_id' => 'TXN-'.Str::random(10),
        ]);

        $this->postJson(route('checkout.payment.callback', 'fake'), [
            'reference' => $payment->transaction_id,
        ])->assertNotFound();

        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->fresh()->payment_status);
    }

    public function test_manual_card_to_card_payment_still_works_without_a_gateway(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->get(route('checkout.payment', $order->token))
            ->assertOk()
            ->assertSee($order->reference, false);
    }

    public function test_a_numeric_order_id_is_not_a_valid_payment_credential(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->actingAs($this->customer())
            ->get('/checkout/payment/'.$order->id)
            ->assertNotFound();
    }

    public function test_a_stranger_cannot_reach_another_customers_order_page(): void
    {
        $owner = $this->customer();
        $order = $this->placeOrder('09120000000');
        $order->forceFill(['user_id' => $owner->id])->save();

        // Direct /orders/{id} route is retired and returns 404
        $this->get('/orders/'.$order->id)
            ->assertNotFound();

        // Tracking with wrong phone last 4 digits fails
        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone_last4' => '9999', // wrong last 4 digits
        ])->assertSessionHasErrors(['reference']);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    private function placeOrder(string $phone): Order
    {
        app(CartService::class)->addItem([
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [],
        ]);

        return app(CartService::class)->createDraftOrder([
            'customer_name' => 'Ù…Ø´ØªØ±ÛŒ Ø§Ù…Ù†ÛŒØª',
            'customer_phone' => $phone,
        ]);
    }

    private function forceProductionEnvironment(): void
    {
        config(['app.env' => 'production']);
        $this->app['env'] = 'production';
    }
}
