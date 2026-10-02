<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\OtpPurpose;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Exceptions\SmsSendingFailedException;
use App\Exceptions\UnknownPaymentGatewayException;
use App\Exceptions\UnknownSmsProviderException;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OtpCode;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderStateMachine;
use App\Services\OtpService;
use App\Services\PaymentGatewayManager;
use App\Services\SmsManager;
use App\Sms\LogSmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * N-Onyx-47 â€” Authentication, Guest Access & Payment Infrastructure Hardening.
 *
 * Three areas, each asserted against a real attack rather than a description:
 *
 *   SEC-001  guest order lookup must not be enumerable
 *   SEC-002  OTP must fail closed when no production SMS transport exists
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
            '/^ORD-\d{4}-\d{6}$/',
            $order->reference,
            'Sanity: the reference is sequential, so it can never be a secret.',
        );

        // The predictable reference, even paired with the order's real phone
        // number, must not authorise anything.
        $this->post(route('order-tracking.check'), [
            'reference' => $order->reference,
            'customer_phone' => '09120000000',
        ])->assertSessionHasErrors('token');

        $this->assertGuest();
    }

    public function test_a_guest_can_track_an_order_with_their_tracking_code(): void
    {
        $order = $this->placeOrder('09120000000');

        $this->post(route('order-tracking.check'), [
            'token' => $order->token,
        ])
            ->assertOk()
            ->assertSee($order->reference, false);
    }

    public function test_a_wrong_tracking_code_leaks_nothing(): void
    {
        $order = $this->placeOrder('09120000000');

        $response = $this->post(route('order-tracking.check'), [
            'token' => Str::random(40),
        ]);

        $response->assertSessionHasErrors('token');

        $content = $response->getContent() ?: '';

        $this->assertStringNotContainsString($order->reference, $content);
        $this->assertStringNotContainsString('09120000000', $content);
        $this->assertGuest();
    }

    public function test_walking_the_sequential_reference_range_confirms_nothing(): void
    {
        $order = $this->placeOrder('09120000000');

        $forged = [];

        for ($sequence = 1; $sequence <= 5; $sequence++) {
            $forged[] = sprintf('ORD-%d-%06d', (int) now()->year, $sequence);
        }

        foreach ($forged as $guess) {
            $response = $this->post(route('order-tracking.check'), [
                'token' => $guess,
            ]);

            $response->assertSessionHasErrors('token');
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

        $this->post(route('order-tracking.check'), ['token' => $order->token])->assertOk();

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

        $content = $this->post(route('order-tracking.check'), ['token' => $order->token])
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
                'token' => Str::random(40),
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

    // =========================================================================
    // SEC-002 â€” OTP MUST FAIL CLOSED WITHOUT A PRODUCTION SMS TRANSPORT
    // =========================================================================

    public function test_the_log_driver_refuses_to_send_in_production(): void
    {
        $this->forceProductionEnvironment();

        $this->expectException(SmsSendingFailedException::class);

        app(SmsManager::class)->provider('log')->sendOtp('09120000000', '123456');
    }

    public function test_the_disabled_driver_never_claims_a_delivery(): void
    {
        $this->forceProductionEnvironment();

        $this->expectException(SmsSendingFailedException::class);

        app(SmsManager::class)->provider('disabled')->sendOtp('09120000000', '123456');
    }

    public function test_issuing_an_otp_in_production_fails_and_persists_nothing(): void
    {
        $this->forceProductionEnvironment();

        try {
            app(OtpService::class)->issue('09120000000', OtpPurpose::PASSWORD_RESET);
            $this->fail('OTP issuance must fail closed in production.');
        } catch (SmsSendingFailedException) {
            // Expected.
        }

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_a_production_otp_is_never_written_to_a_log(): void
    {
        $this->forceProductionEnvironment();
        Log::spy();

        try {
            app(OtpService::class)->issue('09120000000', OtpPurpose::REGISTRATION);
        } catch (SmsSendingFailedException) {
            // Expected.
        }

        Log::shouldNotHaveReceived('channel');
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_no_test_double_is_registered_as_an_sms_provider(): void
    {
        foreach (config('sms.providers') as $name => $class) {
            $this->assertStringStartsNotWith(
                'Tests\\',
                $class,
                "SMS provider [{$name}] must never point at a test double.",
            );
        }
    }

    public function test_an_unknown_sms_provider_cannot_be_resolved(): void
    {
        $manager = app(SmsManager::class);

        $this->assertFalse($manager->has('evil'));

        $this->expectException(UnknownSmsProviderException::class);

        $manager->resolve('evil');
    }

    public function test_a_class_name_cannot_be_used_as_an_sms_provider_name(): void
    {
        $this->expectException(UnknownSmsProviderException::class);

        app(SmsManager::class)->resolve(LogSmsProvider::class);
    }

    public function test_the_otp_is_stored_hashed_and_never_in_plaintext(): void
    {
        app(OtpService::class)->issue('09120000000', OtpPurpose::REGISTRATION);

        $otp = OtpCode::query()->latest('id')->first();

        $this->assertNotNull($otp);
        $this->assertTrue(Hash::isHashed($otp->code_hash), 'The OTP must be stored as a hash.');
        $this->assertFalse(
            ctype_digit($otp->code_hash),
            'The raw OTP must never be readable from the database.',
        );
    }

    public function test_a_wrong_otp_never_verifies_and_stops_verifying_at_the_attempt_limit(): void
    {
        $code = $this->issueAndCapture('09120000000', OtpPurpose::REGISTRATION);
        $maxAttempts = (int) config('sms.otp.max_attempts');

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $this->assertFalse(
                app(OtpService::class)->verify(
                    '09120000000',
                    OtpPurpose::REGISTRATION,
                    $this->wrongCode($code),
                ),
                'A wrong OTP must never verify.',
            );
        }

        $this->assertFalse(
            app(OtpService::class)->verify('09120000000', OtpPurpose::REGISTRATION, $code),
            'Even the correct OTP must be refused once the attempt budget is spent.',
        );
    }

    public function test_an_otp_is_single_use(): void
    {
        $code = $this->issueAndCapture('09120000000', OtpPurpose::REGISTRATION);

        $this->assertTrue(app(OtpService::class)->verify('09120000000', OtpPurpose::REGISTRATION, $code));
        $this->assertFalse(
            app(OtpService::class)->verify('09120000000', OtpPurpose::REGISTRATION, $code),
            'An OTP must be single use.',
        );
    }

    public function test_an_otp_expires(): void
    {
        config(['sms.otp.expires_in' => 1]);

        $code = $this->issueAndCapture('09120000000', OtpPurpose::REGISTRATION);

        $this->travel(120)->seconds();

        $this->assertFalse(
            app(OtpService::class)->verify('09120000000', OtpPurpose::REGISTRATION, $code),
            'An expired OTP must not verify.',
        );
    }

    public function test_an_otp_is_bound_to_its_phone_number(): void
    {
        $code = $this->issueAndCapture('09120000000', OtpPurpose::REGISTRATION);

        $this->assertFalse(
            app(OtpService::class)->verify('09120000009', OtpPurpose::REGISTRATION, $code),
            'A valid OTP must not verify a different phone number.',
        );
    }

    public function test_an_otp_does_not_cross_purposes(): void
    {
        $code = $this->issueAndCapture('09120000000', OtpPurpose::REGISTRATION);

        $this->assertFalse(
            app(OtpService::class)->verify('09120000000', OtpPurpose::PASSWORD_RESET, $code),
            'A registration OTP must not authorise a password reset.',
        );
    }

    public function test_issuing_a_new_otp_invalidates_the_previous_one(): void
    {
        $codes = $this->captureCodes(function (): void {
            app(OtpService::class)->issue('09120000000', OtpPurpose::REGISTRATION);
            app(OtpService::class)->issue('09120000000', OtpPurpose::REGISTRATION);
        });

        $this->assertCount(2, $codes, 'Both issuances must reach the transport.');

        [$first, $second] = $codes;

        $this->assertNotSame($first, $second);
        $this->assertFalse(
            app(OtpService::class)->verify('09120000000', OtpPurpose::REGISTRATION, $first),
            'Resending must invalidate the superseded code.',
        );
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

        // Tracking without the secret 40-char token fails
        $this->post(route('order-tracking.check'), ['token' => Str::random(40)])
            ->assertSessionHasErrors(['token']);
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

    /**
     * Issue an OTP through the development driver while capturing the code it
     * emitted, so verification is exercised end to end without weakening the
     * hashing or bypassing the transport.
     */
    private function issueAndCapture(string $phone, OtpPurpose $purpose): string
    {
        $codes = $this->captureCodes(
            fn () => app(OtpService::class)->issue($phone, $purpose),
        );

        $this->assertNotEmpty($codes, 'The development driver did not emit an OTP code.');

        return $codes[0];
    }

    /**
     * Run the given issuance with the development log driver captured, and
     * return every code it emitted, in order.
     *
     * @param  callable(): void  $action
     * @return list<string>
     */
    private function captureCodes(callable $action): array
    {
        $captured = [];

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturnUsing(
            function ($message, array $context = []) use (&$captured): void {
                if (isset($context['code'])) {
                    $captured[] = (string) $context['code'];
                }
            }
        );

        Log::spy();
        Log::shouldReceive('channel')->with('sms')->andReturn($logger);

        $action();

        return $captured;
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }
}
