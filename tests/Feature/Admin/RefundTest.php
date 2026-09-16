<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Exceptions\RefundException;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Admin\PaymentManager;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\ManualRefundService;
use App\Services\OrderStateMachine;
use App\Services\PaymentGatewayManager;
use App\Services\RefundConstraintService;
use App\Services\RefundCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * Refund lifecycle integrity.
 *
 * A refund may only exist against a SUCCESS payment, may never exceed the
 * refundable amount (paid − reserved refunds), and at most one refund may be
 * in flight at a time. Fully refunding the successful payment marks the
 * order REFUNDED, which unlocks cancellation for the admin.
 */
class RefundTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 150000): Order
    {
        $order = new Order([
            'customer_name' => 'Refund Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createSuccessfulPayment(Order $order, PaymentMethod $method = PaymentMethod::GATEWAY, int $amount = 150000): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => $method->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => $amount,
            'paid_amount' => $amount,
            'paid_at' => now(),
            'gateway' => $method === PaymentMethod::GATEWAY ? 'fake' : null,
            'transaction_id' => $method === PaymentMethod::GATEWAY ? 'REF-ORIGINAL' : null,
            'metadata' => $method === PaymentMethod::GATEWAY ? ['provider_transaction_id' => 'TXN-REF-ORIGINAL'] : [],
        ]);
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

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function customer(): User
    {
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();

        return $customer;
    }

    // --- Constraint service ---

    public function test_non_success_payment_is_not_refundable(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::PENDING->value,
            'amount' => 150000,
        ]);

        $this->expectException(RefundConstraintViolationException::class);
        app(RefundConstraintService::class)->assertRefundable($payment, 100);
    }

    public function test_refund_cannot_exceed_refundable_amount(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);

        $service = app(RefundConstraintService::class);

        $this->assertSame(150000, $service->refundableAmount($payment));

        $payment->refresh();
        $this->expectException(RefundConstraintViolationException::class);
        $service->assertRefundable($payment, 150001);
    }

    public function test_pending_refund_reserves_refundable_balance(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::PENDING->value,
        ]);
        $payment->refresh();

        $service = app(RefundConstraintService::class);
        $this->assertSame(100000, $service->refundableAmount($payment));
        $this->assertFalse($service->isFullyRefunded($payment));
    }

    public function test_in_flight_refund_blocks_second_refund(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::PENDING->value,
        ]);
        $payment->refresh();

        $this->expectException(RefundConstraintViolationException::class);
        app(RefundConstraintService::class)->assertRefundable($payment, 40000);
    }

    public function test_refunded_amount_is_computed_from_completed_refunds_only(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);
        $payment->refresh();

        $service = app(RefundConstraintService::class);
        $this->assertSame(100000, $service->refundableAmount($payment));
        $this->assertFalse($service->isFullyRefunded($payment));
    }

    public function test_payment_is_fully_refunded_when_balance_reaches_zero(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 150000,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);
        $payment->refresh();

        $service = app(RefundConstraintService::class);
        $this->assertSame(0, $service->refundableAmount($payment));
        $this->assertTrue($service->isFullyRefunded($payment));
    }

    // --- Gateway refund (RefundCore) ---

    public function test_gateway_refund_success_marks_refund_completed_and_order_refunded(): void
    {
        $fake = $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        $service = app(RefundCore::class);
        $refund = $service->processRefund($payment, 150000, 'مشتری انصراف داد');

        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
        $this->assertSame(150000, (int) $refund->amount);
        $this->assertSame('مشتری انصراف داد', $refund->reason);
        $this->assertNotNull($refund->refunded_at);
        $this->assertSame('RFN-REF-ORIGINAL', $refund->metadata['provider_refund_id'] ?? null);

        $this->assertSame(1, $fake->refundCalls);
        $this->assertSame($order->id, $fake->lastRefundRequest->orderId);
        $this->assertSame('REF-ORIGINAL', $fake->lastRefundRequest->paymentTransactionId);
        $this->assertSame(150000, $fake->lastRefundRequest->amount);
        $this->assertSame('TXN-REF-ORIGINAL', $fake->lastRefundRequest->paymentProviderTransactionId);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_partial_gateway_refund_keeps_order_paid(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        app(RefundCore::class)->processRefund($payment, 50000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_gateway_refund_failure_marks_refund_failed_and_order_stays_paid(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->failOnRefund = true;
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 150000);

        $this->assertSame(RefundStatus::FAILED, $refund->status);
        $this->assertSame('provider_refund_failed', $refund->metadata['reason'] ?? null);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_gateway_refund_provider_timeout_marks_refund_review(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 150000);

        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame('provider_unknown', $refund->metadata['reason'] ?? null);
        $this->assertArrayNotHasKey('integrity_violation', $refund->metadata ?? []);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_gateway_refund_with_unknown_gateway_fails_gracefully(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        // Gateway 'fake' is not registered (empty registry).
        $refund = app(RefundCore::class)->processRefund($payment, 150000);

        $this->assertSame(RefundStatus::FAILED, $refund->status);
        $this->assertSame('unknown_gateway', $refund->metadata['reason'] ?? null);
        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_refund_core_rejects_manual_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        $this->expectException(RefundException::class);
        app(RefundCore::class)->processRefund($payment, 150000);
    }

    public function test_refund_core_rejects_in_flight_concurrent_refund(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::PENDING->value,
        ]);
        $payment->refresh();

        $this->expectException(RefundConstraintViolationException::class);
        app(RefundCore::class)->processRefund($payment, 40000);
    }

    public function test_open_review_blocks_new_refund(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => RefundStatus::REVIEW->value,
        ]);
        $payment->refresh();

        $this->expectException(RefundConstraintViolationException::class);
        app(RefundCore::class)->processRefund($payment, 40000);
    }

    // --- Manual (bank transfer) refund ---

    public function test_manual_refund_completes_immediately_and_marks_order_refunded(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        $this->actingAs($this->admin());

        $refund = app(ManualRefundService::class)->refund($payment, 150000, 'بازگشت نقدی');

        $this->assertSame(RefundStatus::COMPLETED, $refund->status);
        $this->assertSame(150000, (int) $refund->amount);
        $this->assertSame('بازگشت نقدی', $refund->reason);
        $this->assertNotNull($refund->refunded_at);
        $this->assertArrayHasKey('reviewed_by', $refund->metadata);
        $this->assertArrayHasKey('reviewed_at', $refund->metadata);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_partial_manual_refund_keeps_order_paid(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        app(ManualRefundService::class)->refund($payment, 50000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_manual_refund_rejects_gateway_payment(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::GATEWAY);
        $this->markOrderPaid($order);

        $this->expectException(RefundException::class);
        app(ManualRefundService::class)->refund($payment, 150000);
    }

    public function test_manual_refund_rejects_amount_over_refundable(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        $this->expectException(RefundConstraintViolationException::class);
        app(ManualRefundService::class)->refund($payment, 250000);
    }

    // --- Admin UI ---

    public function test_admin_can_refund_payment_through_order_manager(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('refundPayment', $payment->id, 150000)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => 150000,
            'status' => RefundStatus::COMPLETED->value,
        ]);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_admin_can_refund_manual_payment_through_payment_manager(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(PaymentManager::class)
            ->call('refundPayment', $payment->id, 150000)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => 150000,
            'status' => RefundStatus::COMPLETED->value,
        ]);
    }

    public function test_customer_cannot_refund_payment_through_order_manager(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->customer())
            ->test(OrderManager::class)
            ->assertStatus(403);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_refund_payment_through_payment_manager(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, PaymentMethod::MANUAL_TRANSFER);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->customer())
            ->test(PaymentManager::class)
            ->assertStatus(403);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_refund_payment_of_another_order_through_ui_is_blocked(): void
    {
        $this->registerFakeGateway();
        $orderA = $this->createOrder();
        $orderB = $this->createOrder();
        $paymentB = $this->createSuccessfulPayment($orderB);
        $this->markOrderPaid($orderB);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $orderA->id)
            ->call('refundPayment', $paymentB->id, 150000);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_refund_through_ui_cannot_exceed_refundable_amount(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('refundPayment', $payment->id, 999999);

        $this->assertDatabaseCount('refunds', 0);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    // --- Full refund unlocks cancellation ---

    public function test_fully_refunded_gateway_order_can_be_cancelled_via_ui(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->call('refundPayment', $payment->id, 150000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
    }

    public function test_refund_does_not_reverse_order_status_by_itself(): void
    {
        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        app(RefundCore::class)->processRefund($payment, 150000);

        $order->refresh();
        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->status);
        // Cancellation remains a separate admin decision.
        $this->assertTrue(app(OrderStateMachine::class)->canTransition($order, OrderStatusEnum::CANCELLED));
    }
}
