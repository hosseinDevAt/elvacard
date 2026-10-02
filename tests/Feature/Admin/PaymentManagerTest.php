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

    public function test_payment_manager_does_not_offer_refund_or_reconcile_buttons(): void
    {
        $order = $this->createOrder();
        $payment = $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'fake',
            'transaction_id' => 'TXN-NORECON',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => $order->total_price,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'ui-reconcile-key'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->set('selectedPaymentId', $payment->id)
            ->assertDontSee('wire:click="refundPayment"')
            ->assertDontSee('wire:click="reconcileReviewRefund"');
    }

    public function test_customer_cannot_access_payment_manager(): void
    {
        Livewire::actingAs($this->customer())
            ->test(PaymentManager::class)
            ->assertStatus(403);
    }
}
