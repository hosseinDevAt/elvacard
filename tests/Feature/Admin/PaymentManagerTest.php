<?php

namespace Tests\Feature\Admin;

use App\Contracts\Payments\RefundRetrieveResult;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Livewire\Admin\PaymentManager;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\PaymentGatewayManager;
use App\Services\RefundConstraintService;
use App\Services\RefundCore;
use App\Support\Dates\DateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\BasicFakePaymentGateway;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09123456789',
        ]);
        $order->user_id = $this->customer()->id;
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createPayment(Order $order, PaymentMethod $method, PaymentStatus $status, array $overrides = []): Payment
    {
        return Payment::create(array_merge([
            'order_id' => $order->id,
            'method' => $method->value,
            'status' => $status->value,
            'amount' => $order->total_price,
        ], $overrides));
    }

    private function markOrderPaid(Order $order): Order
    {
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->status = OrderStatusEnum::CONFIRMED;
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

    private function registerBasicGateway(): BasicFakePaymentGateway
    {
        $fake = new BasicFakePaymentGateway('fake');

        $this->app->singleton(BasicFakePaymentGateway::class, fn (): BasicFakePaymentGateway => $fake);
        $this->app->instance(PaymentGatewayManager::class, new PaymentGatewayManager($this->app, [
            'fake' => BasicFakePaymentGateway::class,
        ]));

        return $fake;
    }

    public function test_guest_is_rejected_when_mounting_payment_manager(): void
    {
        Livewire::test(PaymentManager::class)->assertStatus(403);
    }

    public function test_customer_is_rejected_when_mounting_payment_manager(): void
    {
        Livewire::actingAs($this->customer())
            ->test(PaymentManager::class)
            ->assertStatus(403);
    }

    public function test_admin_can_mount_payment_manager(): void
    {
        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->assertOk();
    }

    public function test_payment_list_renders_gateway_and_manual_attempts_with_reason_codes(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW, [
            'tracking_code' => 'TRACK-99',
        ]);

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::FAILED, [
            'gateway' => 'zarinpal',
            'transaction_id' => 'TXN-GATEWAY-1',
            'metadata' => ['reason' => 'provider_verification_failed', 'detail' => 'refunded already'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->assertSee('TRACK-99')
            ->assertSee('TXN-GATEWAY-1')
            ->assertSee('درگاه آنلاین')
            ->assertSee('کارت به کارت')
            ->assertSee('تأیید ناموفق درگاه')
            ->assertSee('refunded already');
    }

    public function test_status_filter_limits_results(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'transaction_id' => 'TXN-SUCCESS',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::FAILED, [
            'transaction_id' => 'TXN-FAILED',
            'metadata' => ['reason' => 'provider_verification_failed'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('statusFilter', PaymentStatus::FAILED->value)
            ->assertSee('TXN-FAILED')
            ->assertDontSee('TXN-SUCCESS');
    }

    public function test_method_filter_limits_results(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW, [
            'tracking_code' => 'TRACK-MANUAL',
        ]);

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'transaction_id' => 'TXN-GW',
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('methodFilter', PaymentMethod::MANUAL_TRANSFER->value)
            ->assertSee('TRACK-MANUAL')
            ->assertDontSee('TXN-GW');
    }

    public function test_reference_filter_limits_results(): void
    {
        $orderA = $this->createOrder();
        $orderB = $this->createOrder();

        $paymentA = $this->createPayment($orderA, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'transaction_id' => 'TXN-A',
        ]);
        $paymentB = $this->createPayment($orderB, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'transaction_id' => 'TXN-B',
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('reference', $orderA->reference)
            ->assertSee('TXN-A')
            ->assertDontSee('TXN-B')
            ->assertSee($orderA->reference);
    }

    public function test_date_range_filter_limits_results(): void
    {
        $order = $this->createOrder();

        $recent = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'transaction_id' => 'TXN-RECENT',
        ]);

        $old = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'transaction_id' => 'TXN-OLD',
        ]);
        $old->created_at = now()->subDays(5);
        $old->save();

        $dates = app(DateService::class);
        $fromJalali = $dates->ascii($dates->jDate($recent->created_at->copy()->subDay()));

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('fromDate', $fromJalali)
            ->assertSee('TXN-RECENT')
            ->assertDontSee('TXN-OLD');
    }

    public function test_admin_can_approve_manual_payment_from_payment_page(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('approvePayment', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);
        $this->assertSame($payment->amount, $payment->paid_amount);
        $this->assertNotNull($payment->paid_at);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    public function test_admin_can_reject_manual_payment_from_payment_page(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('rejectPayment', $payment->id);

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED, $payment->status);
        $this->assertSame('admin_rejected', $payment->metadata['reason'] ?? null);
        $this->assertNull($payment->paid_at);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_customer_cannot_approve_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW);

        Livewire::actingAs($this->customer())
            ->test(PaymentManager::class)
            ->assertStatus(403);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::PENDING_REVIEW->value,
        ]);
    }

    public function test_gateway_payment_cannot_be_approved(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::PENDING, [
            'gateway' => 'zarinpal',
            'transaction_id' => 'TXN-GW',
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('approvePayment', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertSame(PaymentStatus::PENDING, $payment->status);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::UNPAID, $order->payment_status);
    }

    public function test_receipt_actions_are_rendered_for_manual_payments(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW, [
            'receipt_path' => 'payment_receipts/receipt.png',
        ]);

        $url = route('admin.payments.receipt', ['order' => $order, 'payment' => $payment]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->assertSee($url);
    }

    // --- Payment detail + refund reconciliation ---

    public function test_admin_can_view_payment_detail_and_see_refund_rows(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-DETAIL',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertOk()
            ->assertSee('TXN-DETAIL')
            ->assertSee('50,000')
            ->assertSee(RefundStatus::COMPLETED->faLabel());
    }

    public function test_admin_can_reconcile_review_refund_through_payment_manager(): void
    {
        $admin = $this->admin();
        $fake = $this->registerFakeGateway();
        $fake->lookupResult = RefundRetrieveResult::success('RFN-UI-RECON');

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-RECON',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'ui-reconcile-key'],
        ]);

        Livewire::actingAs($admin)
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertHasNoErrors();

        $refund->refresh();
        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
        $this->assertSame('RFN-UI-RECON', $refund->metadata['provider_refund_id'] ?? null);
        $this->assertSame('provider_lookup', $refund->metadata['reconciled_via'] ?? null);
        $this->assertSame(1, $refund->metadata['reconciliation_attempts'] ?? null);
        $this->assertSame($admin->id, $refund->metadata['resolved_by'] ?? null);
        $this->assertArrayHasKey('resolved_at', $refund->metadata);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_reconcile_completed_refund_is_rejected(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-DONE',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertHasNoErrors();

        $refund->refresh();
        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
    }

    /**
     * F-02: a stale PENDING reservation must be reconcilable from the admin
     * surface too, otherwise the deadlock is still permanent in practice.
     */
    public function test_admin_can_reconcile_a_stale_pending_refund(): void
    {
        $admin = $this->admin();
        $fake = $this->registerFakeGateway();
        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-STALE-PENDING',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $stale = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::PENDING->value,
            'metadata' => ['idempotency_key' => 'ui-stale-pending-key'],
        ]);

        // The reconcile affordance is offered for the orphaned reservation.
        Livewire::actingAs($admin)
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertSee('reconcileReviewRefund('.$stale->id.')', false);

        Livewire::actingAs($admin)
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $stale->id)
            ->assertHasNoErrors();

        $stale->refresh();
        $this->assertSame(RefundStatus::FAILED, $stale->status);
        $this->assertTrue($stale->metadata['promoted_from_pending'] ?? false);
        $this->assertSame('confirmed_by_lookup', $stale->metadata['reason'] ?? null);
    }

    public function test_reconcile_with_unknown_outcome_keeps_refund_in_review(): void
    {
        $fake = $this->registerBasicGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-STILL',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $refund = app(RefundCore::class)->processRefund($payment, 100000);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertHasNoErrors();

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $refund->metadata['reconciliation_attempts'] ?? null);
        $this->assertSame(1, $fake->refundCalls, 'No blind second refund() call when outcome is unknown.');
    }

    public function test_reconcile_review_refund_from_unknown_payment_is_safe(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'unknown-gw',
            'transaction_id' => 'TXN-NOGW',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'no-gateway'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertHasNoErrors();

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
    }

    public function test_initial_refund_completed_shows_success_message(): void
    {
        $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F1A',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('refundPayment', $payment->id, $order->total_price)
            ->assertSee('بازگشت وجه با موفقیت انجام شد.');

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::COMPLETED->value,
        ]);
    }

    public function test_initial_refund_failed_shows_failure_message(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F1B',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('refundPayment', $payment->id, $order->total_price)
            ->assertDontSee('بازگشت وجه با موفقیت انجام شد.')
            ->assertSee('بازگشت وجه ناموفق بود.');

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::FAILED->value,
        ]);
    }

    public function test_initial_refund_review_shows_unresolved_message_not_success(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F1C',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('refundPayment', $payment->id, 100000)
            ->assertDontSee('بازگشت وجه با موفقیت انجام شد.')
            ->assertSee('نتیجه بازگشت وجه نامشخص است و برای بررسی مجدد ثبت شد.');

        $refund = Refund::where('payment_id', $payment->id)->first();
        $this->assertNotNull($refund);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        // REVIEW still reserves the refundable balance.
        $payment->refresh();
        $this->assertSame(50000, app(RefundConstraintService::class)->refundableAmount($payment));
    }

    public function test_reconcile_completed_shows_success_message(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->lookupResult = RefundRetrieveResult::success('RFN-F2A');

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F2A',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'f2a-key'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertSee('بازگشت وجه با موفقیت تأیید شد.');

        $refund->refresh();
        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
    }

    public function test_reconcile_failed_does_not_show_success_message(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F2B',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'f2b-key'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertDontSee('بازگشت وجه با موفقیت تأیید شد.')
            ->assertSee('بازگشت وجه توسط درگاه ناموفق تأیید شد.');

        $refund->refresh();
        $this->assertSame(RefundStatus::FAILED, $refund->status);
    }

    public function test_reconcile_review_does_not_show_success_message(): void
    {
        $fake = $this->registerBasicGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F2C',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 100000);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertDontSee('بازگشت وجه با موفقیت تأیید شد.')
            ->assertSee('نتیجه بازگشت وجه همچنان نامشخص است و نیاز به بررسی مجدد دارد.');

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $fake->refundCalls, 'No blind second refund() call when outcome is unknown.');
    }

    public function test_reconcile_idempotent_retry_success_shows_success_message(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F2D',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        // First attempt: timeout → REVIEW; the provider ledger records the eventual success.
        $refund = app(RefundCore::class)->processRefund($payment, 100000);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        // Reconcile: lookup unknown → safe idempotent retry of the same key → success.
        $fake->timeoutOnRefund = false;

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertSee('بازگشت وجه با موفقیت تأیید شد.');

        $refund->refresh();
        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
    }

    public function test_reconcile_idempotent_retry_failure_does_not_show_success_message(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-F2E',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'f2e-key'],
        ]);

        // Lookup unknown → idempotent retry → provider confirms failure.
        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $refund->id)
            ->assertDontSee('بازگشت وجه با موفقیت تأیید شد.')
            ->assertSee('بازگشت وجه توسط درگاه ناموفق تأیید شد.');

        $refund->refresh();
        $this->assertSame(RefundStatus::FAILED, $refund->status);
    }

    /**
     * The refund button must offer exactly what the server will accept. The
     * figure comes from RefundConstraintService, so a REVIEW refund that still
     * reserves balance lowers the offered amount instead of the view inventing
     * its own formula.
     */
    public function test_refundable_offer_matches_the_refund_constraint_service(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-OP02',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        // 150,000 paid, 60,000 already refunded, 25,000 stuck in REVIEW.
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 60000,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 25000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'op02-key'],
        ]);

        $expected = app(RefundConstraintService::class)->refundableAmount($payment);
        $this->assertSame(65000, $expected);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertViewHas('refundableByPaymentId', [$payment->id => $expected])
            ->assertSeeHtml('بازگشت وجه (65,000 تومان)');
    }

    public function test_fully_refunded_payment_offers_no_refund_button(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-OP02B',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertViewHas('refundableByPaymentId', [$payment->id => 0])
            ->assertDontSeeHtml('بازگشت وجه (');
    }

    /**
     * A REVIEW refund still reserves its balance, so a client that replays the
     * pre-reservation amount is rejected by the server instead of quietly
     * over-refunding.
     */
    public function test_stale_client_refund_amount_is_rejected_while_a_review_reserves_balance(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-STALE',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $review = app(RefundCore::class)->processRefund($payment, 100000);
        $this->assertSame(RefundStatus::REVIEW, $review->status);

        // The admin page already offers the reduced, service-derived figure,
        // yet a replayed pre-reservation amount is refused outright.
        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertViewHas('refundableByPaymentId', [$payment->id => 50000])
            ->call('refundPayment', $payment->id, 100000)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('refunds', 1);
        $review->refresh();
        $this->assertSame(RefundStatus::REVIEW, $review->status);
        $this->assertSame(1, $fake->refundCalls, 'A rejected request never reaches the provider.');

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_failed_reconciliation_releases_the_reservation_for_a_new_refund(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-RELEASE',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        $review = app(RefundCore::class)->processRefund($payment, 100000);
        $this->assertSame(RefundStatus::REVIEW, $review->status);
        $this->assertSame(50000, app(RefundConstraintService::class)->refundableAmount($payment->fresh()));

        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->call('reconcileReviewRefund', $review->id);

        $review->refresh();
        $this->assertSame(RefundStatus::FAILED, $review->status);

        // The released balance is refundable again, in full.
        $fake->timeoutOnRefund = false;
        $fake->lookupResult = null;

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertViewHas('refundableByPaymentId', [$payment->id => $order->total_price])
            ->call('refundPayment', $payment->id, $order->total_price);

        $this->assertDatabaseCount('refunds', 2);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_customer_cannot_invoke_reconcile_review_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-FAUTH',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'fauth-key'],
        ]);

        // The boot guard aborts EVERY hydrate (mount and update) for
        // non-admins, so the reconcile action can never execute for a customer.
        Livewire::actingAs($this->customer())
            ->test(PaymentManager::class)
            ->assertStatus(403);

        // No reconciliation side effects may occur for a non-admin.
        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertArrayNotHasKey('reconciliation_attempts', $refund->metadata);
    }
}
