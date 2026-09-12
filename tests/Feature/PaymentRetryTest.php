<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentRetryException;
use App\Models\ManualPaymentSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ManualPaymentRetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentRetryTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Retry Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();

        return $customer;
    }

    private function createCustomerOrder(User $customer, int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Retry Customer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->user_id = $customer->id;
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

    private function createFailedPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::FAILED->value,
            'amount' => (int) $order->total_price,
            'tracking_code' => 'OLD-TRACK',
            'receipt_path' => 'payment_receipts/old.png',
            'metadata' => ['note' => 'رسید قبلی'],
        ]);
    }

    private function createPendingReviewPayment(Order $order): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => (int) $order->total_price,
            'tracking_code' => 'ACTIVE-TRACK',
            'receipt_path' => 'payment_receipts/active.png',
            'metadata' => ['note' => 'رسید فعال'],
        ]);
    }

    private function createSuccessPayment(Order $order): Payment
    {
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => (int) $order->total_price,
            'paid_amount' => (int) $order->total_price,
            'tracking_code' => 'SUCCESS-TRACK',
            'receipt_path' => 'payment_receipts/success.png',
            'metadata' => ['note' => 'پرداخت موفق'],
            'paid_at' => now(),
        ]);

        $order->payment_status = PaymentStatusEnum::PAID;
        $order->save();

        return $payment;
    }

    private function retryPayload(array $overrides = []): array
    {
        return array_merge([
            'receipt_image' => UploadedFile::fake()->image('receipt.png', 100, 100),
            'tracking_number' => 'NEW-TRACK-999',
            'note' => 'رسید جدید ارسال شد',
        ], $overrides);
    }

    public function test_customer_can_retry_rejected_manual_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $failedPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $response = $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response->assertRedirect(route('checkout.success', $order->token));

        $failedPayment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $failedPayment->status);

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('id', '!=', $failedPayment->id)
            ->first();

        $this->assertNotNull($newPayment);
        $this->assertSame(PaymentStatus::PENDING_REVIEW, $newPayment->status);
    }

    public function test_retry_creates_a_new_payment_and_previous_remains_failed(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $failedPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $this->assertDatabaseCount('payments', 2);

        $this->assertDatabaseHas('payments', [
            'id' => $failedPayment->id,
            'status' => PaymentStatus::FAILED->value,
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'tracking_code' => 'NEW-TRACK-999',
        ]);
    }

    public function test_new_payment_gets_correct_amount_from_order(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer, total: 225000);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertSame(225000, (int) $newPayment->amount);
    }

    public function test_browser_amount_manipulation_is_ignored(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer, total: 225000);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload([
                'amount' => 1000,
            ]));

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertSame(225000, (int) $newPayment->amount);
    }

    public function test_retry_blocked_when_active_pending_review_payment_exists(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createPendingReviewPayment($order);
        $this->createActiveSetting();

        $response = $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response->assertRedirect(route('checkout.success', $order->token));
        $this->assertDatabaseCount('payments', 2);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($order, [
            'receipt_path' => 'payment_receipts/blocked.png',
        ]);
    }

    public function test_retry_blocked_when_order_already_paid(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createSuccessPayment($order);

        $response = $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response->assertRedirect(route('checkout.success', $order->token));
        $this->assertDatabaseCount('payments', 1);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($order, [
            'receipt_path' => 'payment_receipts/blocked.png',
        ]);
    }

    public function test_retry_blocked_for_another_customers_order(): void
    {
        Storage::fake('local');
        $customerA = $this->createCustomer();
        $customerB = $this->createCustomer();
        $orderA = $this->createCustomerOrder($customerA);
        $this->createFailedPayment($orderA);
        $this->createActiveSetting();

        $this->actingAs($customerB)
            ->post(route('checkout.payment.store', $orderA), $this->retryPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 1);

        $this->actingAs($customerB)
            ->get(route('checkout.payment', $orderA))
            ->assertForbidden();
    }

    public function test_guest_cannot_retry_customer_order(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->post(route('checkout.payment.store', $order), $this->retryPayload())
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_guest_cannot_retry_guest_order(): void
    {
        Storage::fake('local');
        $order = $this->createOrder();
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $response = $this->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);

        $page = $this->get(route('checkout.payment', $order));
        $page->assertOk();
        $page->assertDontSee('name="receipt_image"');
        $page->assertSee('پرداخت مجدد برای سفارش مهمان امکان‌پذیر نیست');
    }

    public function test_retry_blocked_when_manual_payment_setting_is_inactive(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);

        $response = $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_new_receipt_belongs_to_new_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $oldPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::FAILED->value)
            ->first();

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertNotSame($oldPayment->receipt_path, $newPayment->receipt_path);
        $this->assertNotNull($newPayment->receipt_path);
        Storage::disk('local')->assertExists($newPayment->receipt_path);
    }

    public function test_old_receipt_remains_intact(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $oldPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $oldPayment->refresh();
        $this->assertSame('payment_receipts/old.png', $oldPayment->receipt_path);

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertNotSame($oldPayment->receipt_path, $newPayment->receipt_path);
    }

    public function test_new_tracking_code_belongs_to_new_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $oldPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload([
                'tracking_number' => 'NEW-TRACK-1000',
            ]));

        $oldPayment->refresh();
        $this->assertSame('OLD-TRACK', $oldPayment->tracking_code);

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertSame('NEW-TRACK-1000', $newPayment->tracking_code);
    }

    public function test_new_customer_note_belongs_to_new_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $oldPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload([
                'note' => 'نظر جدید از سمت مشتری',
            ]));

        $oldPayment->refresh();
        $this->assertSame(['note' => 'رسید قبلی'], $oldPayment->metadata);

        $newPayment = Payment::query()
            ->where('order_id', $order->id)
            ->where('status', PaymentStatus::PENDING_REVIEW->value)
            ->first();

        $this->assertSame(['note' => 'نظر جدید از سمت مشتری'], $newPayment->metadata);
    }

    public function test_duplicate_post_does_not_create_multiple_active_payments(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $response = $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload([
                'tracking_number' => 'SECOND-SUBMIT',
            ]));

        $response->assertRedirect(route('checkout.success', $order->token));

        $this->assertDatabaseCount('payments', 2);
        $this->assertSame(
            1,
            Payment::query()
                ->where('order_id', $order->id)
                ->where('status', PaymentStatus::PENDING_REVIEW->value)
                ->count()
        );
    }

    public function test_failed_payment_cannot_be_resurrected(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $failedPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $failedPayment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $failedPayment->status);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($order, [
            'receipt_path' => 'payment_receipts/attempt.png',
        ]);
    }

    public function test_success_payment_cannot_become_failed_or_retry(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $successPayment = $this->createSuccessPayment($order);

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload())
            ->assertRedirect(route('checkout.success', $order->token));

        $successPayment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $successPayment->status);
        $this->assertDatabaseCount('payments', 1);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($order, [
            'receipt_path' => 'payment_receipts/attempt.png',
        ]);
    }

    public function test_cross_order_payment_manipulation_blocked(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $orderA = $this->createCustomerOrder($customer);
        $orderB = $this->createCustomerOrder($customer);
        $this->createFailedPayment($orderB);
        $this->createActiveSetting();

        // orderA has a failed payment from a DIFFERENT order context; retrying
        // via orderA is not possible because orderA has no failed payment.
        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($orderA, [
            'receipt_path' => 'payment_receipts/attempt.png',
        ]);

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_cancelled_order_behavior_verified(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $order->status = \App\Enums\OrderStatusEnum::CANCELLED;
        $order->save();
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_completed_order_behavior_verified(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $order->status = \App\Enums\OrderStatusEnum::COMPLETED;
        $order->save();
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $this->actingAs($customer)
            ->post(route('checkout.payment.store', $order), $this->retryPayload());

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_concurrent_retry_blocked_when_active_payment_appears(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);

        // Simulate another request winning the race and creating an active payment
        $this->createPendingReviewPayment($order);

        $this->expectException(PaymentRetryException::class);
        app(ManualPaymentRetryService::class)->createRetryPayment($order, [
            'receipt_path' => 'payment_receipts/attempt.png',
        ]);

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_payment_page_shows_failed_notice_and_retry_form_for_customer(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $response = $this->actingAs($customer)
            ->get(route('checkout.payment', $order));

        $response->assertOk();
        $response->assertSee('پرداخت قبلی شما رد شده است');
        $response->assertSee('name="receipt_image"', false);
    }

    public function test_orders_show_page_shows_retry_link_for_failed_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createFailedPayment($order);
        $this->createActiveSetting();

        $response = $this->actingAs($customer)
            ->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('تاریخچه پرداخت');
        $response->assertSee('پرداخت مجدد');
        $response->assertSee(route('checkout.payment', $order->token));
    }

    public function test_orders_show_page_hides_retry_link_for_active_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $this->createPendingReviewPayment($order);

        $response = $this->actingAs($customer)
            ->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('پرداخت مجدد');
    }

    public function test_success_page_shows_retry_link_for_failed_payment(): void
    {
        Storage::fake('local');
        $customer = $this->createCustomer();
        $order = $this->createCustomerOrder($customer);
        $failedPayment = $this->createFailedPayment($order);
        $this->createActiveSetting();

        $response = $this->actingAs($customer)
            ->get(route('checkout.success', $order->token));

        $response->assertOk();
        $response->assertSee('پرداخت شما رد شده است');
        $response->assertSee('پرداخت مجدد');
        $this->assertSame(PaymentStatus::FAILED, $failedPayment->fresh()->status);
    }
}