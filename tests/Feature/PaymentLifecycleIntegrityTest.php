<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentConstraintViolationException;
use App\Exceptions\PaymentRetryException;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Services\GatewayInitiationService;
use App\Services\ManualPaymentCreationService;
use App\Services\ManualPaymentReviewService;
use App\Services\PaymentConstraintService;
use App\Services\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Order state and payment state must never contradict each other.
 *
 * Forbidden end states:
 *  - cancelled + paid
 *  - completed + paid
 *  - multiple successful payments for one order
 *  - multiple active payments for one order after a race
 */
class PaymentLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Lifecycle Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
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

    private function registerFakeGateway(): FakePaymentGateway
    {
        $fake = new FakePaymentGateway;

        $this->app->singleton(FakePaymentGateway::class, fn (): FakePaymentGateway => $fake);
        $this->app->instance(PaymentGatewayManager::class, new PaymentGatewayManager($this->app, [
            'fake' => FakePaymentGateway::class,
        ]));

        return $fake;
    }

    private function initiateViaHttp(Order $order): TestResponse
    {
        $this->registerFakeGateway();

        return $this->post(route('checkout.payment.gateway.initiate', $order->token), [
            'gateway' => 'fake',
        ]);
    }

    private function callbackViaHttp(Payment $payment, array $data = []): TestResponse
    {
        return $this->postJson(route('checkout.payment.callback', $payment->gateway), array_merge([
            'reference' => $payment->transaction_id,
        ], $data));
    }

    private function latestGatewayPayment(Order $order): ?Payment
    {
        return $order->payments()
            ->where('method', PaymentMethod::GATEWAY->value)
            ->latest('id')
            ->first();
    }

    private function createFailedGatewayPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::FAILED->value,
            'amount' => (int) $order->total_price,
            'gateway' => 'fake',
            'transaction_id' => 'REF-DEAD-GATEWAY',
            'metadata' => ['reason' => 'provider_verification_failed'],
        ]);
    }

    private function createPendingReviewPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => (int) $order->total_price,
            'receipt_path' => 'payment_receipts/attempt.png',
            'metadata' => ['note' => 'رسید ارسال شد'],
        ]);
    }

    private function submitReceipt(Order $order): TestResponse
    {
        return $this->post(route('checkout.payment.store', $order), [
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
            'tracking_number' => 'TRACK-LIFECYCLE',
            'note' => 'رسید لایف‌سایکل',
        ]);
    }

    public function test_cancelled_order_cannot_initiate_gateway_payment(): void
    {
        $order = $this->createOrder();
        $order->status = OrderStatusEnum::CANCELLED;
        $order->save();

        $this->initiateViaHttp($order)
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);

        $result = app(GatewayInitiationService::class)->initiate($order, 'fake');
        $this->assertFalse($result->success);

        $this->assertDatabaseCount('payments', 0);

        $this->expectException(PaymentConstraintViolationException::class);
        app(PaymentConstraintService::class)->assertPayable($order);
    }

    public function test_completed_order_cannot_initiate_gateway_payment(): void
    {
        $order = $this->createOrder();
        $order->status = OrderStatusEnum::COMPLETED;
        $order->save();

        $this->initiateViaHttp($order)
            ->assertRedirect()
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);

        $this->expectException(PaymentConstraintViolationException::class);
        app(PaymentConstraintService::class)->assertPayable($order);
    }

    public function test_cancelled_order_cannot_submit_manual_receipt(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $order->status = OrderStatusEnum::CANCELLED;
        $order->save();
        $this->createActiveSetting();

        $this->submitReceipt($order)->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);
        Storage::disk('local')->assertDirectoryEmpty('payment_receipts');
    }

    public function test_completed_order_cannot_submit_manual_receipt(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $order->status = OrderStatusEnum::COMPLETED;
        $order->save();
        $this->createActiveSetting();

        $this->submitReceipt($order)->assertSessionHasErrors('payment');

        $this->assertDatabaseCount('payments', 0);
        Storage::disk('local')->assertDirectoryEmpty('payment_receipts');
    }

    public function test_cancelled_pending_gateway_payment_cannot_become_success(): void
    {
        $order = $this->createOrder();
        $this->initiateViaHttp($order)->assertRedirect();

        $payment = $this->latestGatewayPayment($order);
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::PENDING, $payment->status);

        $order->status = OrderStatusEnum::CANCELLED;
        $order->save();

        $this->callbackViaHttp($payment)->assertExactJson(['status' => 'failed']);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertNull($payment->paid_amount);

        $order->refresh();
        $this->assertSame(OrderStatusEnum::CANCELLED, $order->status);
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_manual_approval_is_blocked_when_order_is_already_paid(): void
    {
        $order = $this->createOrder();
        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => (int) $order->total_price,
            'paid_amount' => (int) $order->total_price,
            'paid_at' => now(),
        ]);
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->save();

        $secondPayment = $this->createPendingReviewPayment($order);

        $this->expectException(PaymentConstraintViolationException::class);
        app(ManualPaymentReviewService::class)->approve($secondPayment);

        $secondPayment->refresh();
        $this->assertSame(PaymentStatus::PENDING_REVIEW, $secondPayment->status);
    }

    public function test_concurrent_manual_submissions_create_only_one_active_payment(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();

        $this->submitReceipt($order)->assertRedirect(route('checkout.success', $order->token));
        $this->submitReceipt($order)->assertRedirect(route('checkout.success', $order->token));

        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);

        // Service-level race guard: a fresh submission racing the "winner"
        // must be rejected while the first active payment still exists.
        $this->assertDatabaseCount('payments', 1);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentCreationService::class)->createPayment($order, [
            'receipt_path' => 'payment_receipts/race.png',
        ]);
    }

    public function test_failed_gateway_payment_does_not_block_first_manual_submission(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createActiveSetting();
        $this->createFailedGatewayPayment($order);

        $response = $this->submitReceipt($order);

        $response->assertRedirect(route('checkout.success', $order->token));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_checkout_token_duplicate_returns_existing_order(): void
    {
        $token = Str::random(40);

        $order = new Order([
            'customer_name' => 'Duplicate Buyer',
            'customer_phone' => '09123456789',
        ]);
        $order->token = $token;
        $order->total_price = 150000;
        $order->save();

        $payload = [
            'customer_name' => 'Duplicate Buyer',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، خیابان آزمایش',
            'shipping_postal_code' => '1234567890',
            'submission_token' => $token,
        ];

        $response = $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $payload);

        $response->assertRedirect(route('checkout.payment', $order->token));
        $response->assertSessionHas('info');
        $this->assertDatabaseCount('orders', 1);
    }
}
