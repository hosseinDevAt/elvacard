<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\GatewayInitiationService;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Provider-agnostic Gateway Payment Core tests.
 *
 * These run entirely against FakePaymentGateway (a test double that is never
 * registered in the production gateway registry). SQLite test DBs do not
 * support MySQL row locks, so the lockForUpdate() calls are inert here; the
 * same state guards still hold because every state change re-checks the
 * current status after acquiring the lock.
 */
class GatewayPaymentCoreTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Gateway Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function registerFakeGateway(): FakePaymentGateway
    {
        $fake = new FakePaymentGateway;

        $this->app->singleton(FakePaymentGateway::class, fn (): FakePaymentGateway => $fake);
        $this->app->instance(PaymentGatewayManager::class, new PaymentGatewayManager($this->app, [
            'fake' => FakePaymentGateway::class,
        ]));

        return $fake;
    }

    private function initiateViaHttp(Order $order, string $gateway = 'fake'): TestResponse
    {
        return $this->post(route('checkout.payment.gateway.initiate', $order->token), [
            'gateway' => $gateway,
        ]);
    }

    private function latestGatewayPayment(Order $order): ?Payment
    {
        return $order->payments()
            ->where('method', PaymentMethod::GATEWAY->value)
            ->latest('id')
            ->first();
    }

    private function callbackViaHttp(Payment $payment, array $data = []): TestResponse
    {
        return $this->postJson(route('checkout.payment.callback', $payment->gateway), array_merge([
            'reference' => $payment->transaction_id,
        ], $data));
    }

    public function test_initiation_creates_gateway_payment_before_redirect(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder(total: 225000);

        $response = $this->initiateViaHttp($order);

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => 225000,
            'gateway' => 'fake',
        ]);
    }

    public function test_initiation_amount_originates_from_db_order_total(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder(total: 337000);

        $this->initiateViaHttp($order);

        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame(337000, (int) $payment->amount);
        $this->assertNull($payment->paid_amount);
    }

    public function test_initiation_stores_provider_reference_and_returns_redirect(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $response = $this->initiateViaHttp($order);

        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame("REF-{$order->reference}-1", $payment->transaction_id);
        $this->assertSame("https://redir.example.test/pay/{$order->reference}", $response->headers->get('Location'));
    }

    public function test_initiation_failure_is_safe_and_failed_not_success(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnInitiate = true;
        $order = $this->createOrder(total: 70000);

        $response = $this->initiateViaHttp($order);

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertNotSame("https://redir.example.test/pay/{$order->reference}", $response->headers->get('Location'));

        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('initiation_failed', $payment->metadata['reason'] ?? null);
        $this->assertNull($payment->paid_amount);
    }

    public function test_initiation_provider_exception_marks_payment_failed_without_500(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->throwOnInitiate = true;
        $order = $this->createOrder(total: 140000);

        $response = $this->initiateViaHttp($order);

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertNotSame("https://redir.example.test/pay/{$order->reference}", $response->headers->get('Location'));

        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('provider_exception', $payment->metadata['reason'] ?? null);
        $this->assertNull($payment->paid_amount);
    }

    public function test_empty_gateway_registry_degrades_gracefully(): void
    {
        $order = $this->createOrder();

        $response = $this->initiateViaHttp($order);

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_empty_gateway_registry_service_returns_unavailable_result(): void
    {
        $order = $this->createOrder();
        $service = $this->app->make(GatewayInitiationService::class);

        $result = $service->initiate($order, 'fake');

        $this->assertFalse($result->success);
        $this->assertNull($result->redirectUrl);
        $this->assertNull($result->payment);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unknown_gateway_identifier_fails_safely(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $response = $this->initiateViaHttp($order, 'zarinpal');

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_active_pending_payment_prevents_duplicate_initiation(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $this->assertTrue($this->initiateViaHttp($order)->isRedirect());

        $response = $this->initiateViaHttp($order);

        $response->assertSessionHasErrors('payment');
        $this->assertSame(1, $order->payments()->where('method', PaymentMethod::GATEWAY->value)->count());
    }

    public function test_successful_payment_prevents_reinitiation(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->callbackViaHttp($payment)->assertJson(['status' => 'success']);

        $response = $this->initiateViaHttp($order);

        $response->assertSessionHasErrors('payment');
        $this->assertSame(1, $order->payments()->where('method', PaymentMethod::GATEWAY->value)->count());
    }

    public function test_manual_pending_review_blocks_gateway_initiation(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => $order->total_price,
        ]);

        $response = $this->initiateViaHttp($order);

        $response->assertSessionHasErrors('payment');
        $this->assertSame(0, $order->payments()->where('method', PaymentMethod::GATEWAY->value)->count());
    }

    public function test_successful_callback_marks_payment_success_and_order_paid(): void
    {
        $fake = $this->registerFakeGateway();
        $order = $this->createOrder(total: 225000);

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);

        $response = $this->callbackViaHttp($payment, ['status' => 'OK']);

        $response->assertOk();
        $response->assertExactJson(['status' => 'success']);
        $this->assertSame(1, $fake->verifyCalls);

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(225000, (int) $payment->paid_amount);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('TXN-'.$payment->transaction_id, $payment->metadata['provider_transaction_id'] ?? null);
        $this->assertSame(225000, (int) ($payment->metadata['verified_amount'] ?? 0));

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    public function test_failed_verification_marks_payment_failed_and_order_unpaid(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnVerify = true;
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->callbackViaHttp($payment, ['status' => 'NOK']);

        $response->assertOk();
        $response->assertExactJson(['status' => 'failed']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('provider_verification_failed', $payment->metadata['reason'] ?? null);
        $this->assertNull($payment->paid_amount);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
    }

    public function test_callback_provider_exception_marks_payment_failed_and_returns_controlled_response(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->throwOnVerify = true;
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->callbackViaHttp($payment, ['status' => 'OK']);

        $response->assertOk();
        $response->assertExactJson(['status' => 'failed']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('provider_exception', $payment->metadata['reason'] ?? null);
        $this->assertNull($payment->paid_amount);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
    }

    public function test_amount_mismatch_cannot_produce_success(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->verificationAmountOverride = 999999;
        $order = $this->createOrder(total: 150000);

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->callbackViaHttp($payment);

        $response->assertExactJson(['status' => 'failed']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('amount_mismatch', $payment->metadata['reason'] ?? null);
        $this->assertSame(999999, (int) ($payment->metadata['returned_amount'] ?? 0));
        $this->assertNull($payment->paid_amount);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::PENDING, $order->status);
    }

    public function test_callback_does_not_trust_client_success_status(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnVerify = true;
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->callbackViaHttp($payment, ['status' => 'OK']);

        $response->assertExactJson(['status' => 'failed']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
    }

    public function test_callback_amount_is_ignored_not_authoritative(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder(total: 200000);

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->callbackViaHttp($payment, ['amount' => 100, 'status' => 'OK']);

        $response->assertExactJson(['status' => 'success']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame(200000, (int) $payment->paid_amount);
        $this->assertSame(200000, (int) ($payment->metadata['verified_amount'] ?? 0));
    }

    public function test_repeated_callback_has_no_duplicate_effects(): void
    {
        $fake = $this->registerFakeGateway();
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $this->callbackViaHttp($payment)->assertExactJson(['status' => 'success']);
        $paidAt = $payment->fresh()->paid_at;

        $response = $this->callbackViaHttp($payment);

        $response->assertExactJson(['status' => 'success']);
        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame($paidAt?->format('Y-m-d H:i:s'), $payment->paid_at?->format('Y-m-d H:i:s'));
        $this->assertSame(1, $fake->verifyCalls);
        $this->assertSame(1, $order->payments()->count());
    }

    public function test_callback_after_failure_does_not_become_success_without_new_attempt(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnVerify = true;
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->callbackViaHttp($payment)->assertExactJson(['status' => 'failed']);

        $fake->failOnVerify = false;

        $response = $this->callbackViaHttp($payment);

        $response->assertExactJson(['status' => 'failed']);
        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame(1, $fake->verifyCalls);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_retry_after_failure_creates_new_payment_and_can_succeed(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnVerify = true;
        $order = $this->createOrder();

        $this->initiateViaHttp($order);
        $first = $this->latestGatewayPayment($order);
        $this->assertNotNull($first);
        $this->callbackViaHttp($first)->assertExactJson(['status' => 'failed']);

        $fake->failOnVerify = false;

        $this->initiateViaHttp($order)->assertRedirect();
        $second = $this->latestGatewayPayment($order);
        $this->assertNotNull($second);
        $this->assertNotSame($first->id, $second->id);

        $this->callbackViaHttp($second)->assertExactJson(['status' => 'success']);

        $this->assertSame(PaymentStatus::FAILED, $first->fresh()->status);
        $this->assertSame(PaymentStatus::SUCCESS, $second->fresh()->status);
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_missing_reference_callback_fails_safely(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $response = $this->postJson(route('checkout.payment.callback', $payment->gateway), []);

        $response->assertStatus(422);
        $response->assertJson(['status' => 'missing_reference']);
        $this->assertSame(PaymentStatus::PENDING, $payment->fresh()->status);
    }

    public function test_unknown_payment_callback_fails_safely(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $response = $this->postJson(route('checkout.payment.callback', 'fake'), [
            'reference' => 'REF-NOT-EXISTING',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'unknown_payment']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unknown_gateway_callback_fails_safely(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();

        $response = $this->postJson(route('checkout.payment.callback', 'zarinpal'), [
            'reference' => 'REF-ANY',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'unknown_gateway']);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_cross_order_manipulation_is_blocked(): void
    {
        $this->registerFakeGateway();
        $orderA = $this->createOrder(total: 100000);
        $orderB = $this->createOrder(total: 200000);

        $this->initiateViaHttp($orderA);
        $paymentA = $this->latestGatewayPayment($orderA);
        $this->assertNotNull($paymentA);

        $this->initiateViaHttp($orderB);
        $paymentB = $this->latestGatewayPayment($orderB);
        $this->assertNotNull($paymentB);

        $this->callbackViaHttp($paymentA)->assertExactJson(['status' => 'success']);

        $paymentA->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $paymentA->status);

        $paymentB->refresh();
        $this->assertSame(PaymentStatus::PENDING, $paymentB->status);
        $orderB->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $orderB->payment_status);

        $orderA->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $orderA->payment_status);
    }

    public function test_callback_works_without_any_authentication(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);

        $this->assertFalse(auth()->check());
        $this->callbackViaHttp($payment)->assertExactJson(['status' => 'success']);
    }

    public function test_owned_order_rejects_other_user_gateway_initiation(): void
    {
        $this->registerFakeGateway();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = new Order([
            'customer_name' => 'Owner',
            'customer_phone' => '09123456789',
        ]);
        $order->user_id = $owner->id;
        $order->total_price = 150000;
        $order->save();

        $this->actingAs($intruder)
            ->post(route('checkout.payment.gateway.initiate', $order->token), ['gateway' => 'fake'])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_pending_gateway_payment_redirects_payment_page_to_success(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $this->initiateViaHttp($order);

        $response = $this->get(route('checkout.payment', $order->token));

        $response->assertRedirect(route('checkout.success', $order->token));
    }

    public function test_gateway_option_hidden_when_no_gateway_configured(): void
    {
        $this->createActiveSetting();
        $order = $this->createOrder();

        $response = $this->get(route('checkout.payment', $order->token));

        $response->assertOk();
        $response->assertDontSee('پرداخت آنلاین');
        $response->assertDontSee('درگاه آنلاین');
    }

    public function test_gateway_option_shown_when_gateway_configured(): void
    {
        $this->registerFakeGateway();
        $this->createActiveSetting();
        $order = $this->createOrder();

        $response = $this->get(route('checkout.payment', $order->token));

        $response->assertOk();
        $response->assertSee('پرداخت آنلاین');
        $response->assertSee('درگاه آنلاین');
    }

    public function test_return_page_shows_server_side_success_state(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->callbackViaHttp($payment);

        $response = $this->get(route('checkout.payment.return', $order->token));

        $response->assertOk();
        $response->assertSee('پرداخت با موفقیت انجام شد');
        $response->assertDontSee('پرداخت ناموفق بود');
    }

    public function test_return_page_shows_server_side_failed_state(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnVerify = true;
        $order = $this->createOrder();
        $this->initiateViaHttp($order);
        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->callbackViaHttp($payment);

        $response = $this->get(route('checkout.payment.return', $order->token));

        $response->assertOk();
        $response->assertSee('پرداخت ناموفق بود');
    }

    private function createActiveSetting(): ManualPaymentSetting
    {
        return ManualPaymentSetting::create([
            'card_number' => '6037991234567890',
            'iban' => 'IR012345678901234567890123',
            'account_name' => 'Shop Account',
            'instruction_message' => 'مبلغ را دقیقاً به این کارت واریز کنید.',
            'success_message' => 'رسید شما دریافت شد و پس از بررسی تأیید خواهد شد.',
            'is_active' => true,
        ]);
    }
}
