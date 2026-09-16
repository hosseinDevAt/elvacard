<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\PaymentGatewayManager;
use App\Services\RefundConstraintService;
use App\Services\RefundCore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * N-Onyx-26-b Phase 1: Gateway Refund Concurrency.
 *
 * MySQL-authoritative tests proving that reservation serializes on the
 * payment row lock and that the completion aggregate re-check never masks
 * an over-refund as "fully refunded."
 */
class RefundFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(int $total = 1000): Order
    {
        $order = new Order([
            'customer_name' => 'Integrity Buyer',
            'customer_phone' => '09123456789',
        ]);

        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createSuccessfulPayment(Order $order, ?int $amount = null): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => $amount ?? (int) $order->total_price,
            'paid_amount' => $amount ?? (int) $order->total_price,
            'paid_at' => now(),
            'gateway' => 'fake',
            'transaction_id' => 'REF-ORIG-'.uniqid(),
            'metadata' => ['provider_transaction_id' => 'TXN-ORIG-'.uniqid()],
        ]);
    }

    private function markOrderPaid(Order $order): void
    {
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->save();
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

    public function test_b_mysql_lock_contention_blocks_second_reservation(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL row-locking semantics.');
        }

        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        // Configure a second connection to the same MySQL database.
        $mysqlConfig = config('database.connections.mysql');
        config()->set('database.connections.mysql_contender', $mysqlConfig);
        DB::purge('mysql_contender');

        // --- Transaction A: hold the payment row lock ---
        DB::beginTransaction();
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$payment->id]);
        }

        // --- Transaction B (contender): attempt a refund — must block ---
        $originalConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('mysql_contender');
        DB::statement('SET SESSION innodb_lock_wait_timeout = 2');

        $service = app(RefundCore::class);
        $exception = null;

        try {
            $service->processRefund($payment, 700);
        } catch (\Throwable $e) {
            $exception = $e;
        }

        DB::setDefaultConnection($originalConnection);

        // Transaction A: rollback (release lock).
        DB::rollBack();

        // --- Assertions ---
        $this->assertNotNull($exception, 'Reservation must block while payment row lock is held.');
        $this->assertStringContainsString('Lock wait timeout', $exception->getMessage());

        // No refund row was created by transaction B (rolled back).
        $this->assertDatabaseCount('refunds', 0);
        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS, $payment->status);

        // Cleanup config.
        config()->offsetUnset('database.connections.mysql_contender');
        DB::purge('mysql_contender');
    }

    public function test_c_two_seventy_percent_refunds_on_thousand_cannot_over_refund(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL transaction-isolation semantics.');
        }

        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        $service = app(RefundCore::class);

        // First refund: 700 → reserves and completes synchronously (fake).
        $refund1 = $service->processRefund($payment, 700);
        $this->assertSame(RefundStatus::COMPLETED, $refund1->status);

        $payment->refresh();

        // Second refund: 700 → must be rejected (refundable now 300).
        $this->expectException(RefundConstraintViolationException::class);
        $service->processRefund($payment, 700);
    }

    public function test_c_two_seventy_percent_refunds_collectively_do_not_exceed_paid_amount(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL transaction-isolation semantics.');
        }

        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        $service = app(RefundCore::class);

        $refund1 = $service->processRefund($payment, 700);

        // Attempt a second refund that would cause over-refund.
        try {
            $service->processRefund($payment, 700);
            $this->fail('Second refund must be rejected.');
        } catch (RefundConstraintViolationException $e) {
            // Expected.
        }

        $refund1->refresh();
        $payment->refresh();

        $service = app(RefundConstraintService::class);
        $this->assertSame(700, $service->reservedAmount($payment));
        $this->assertSame(300, $service->refundableAmount($payment));
        $this->assertFalse($service->isFullyRefunded($payment));

        $this->assertDatabaseCount('refunds', 1);
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::COMPLETED->value,
        ]);
    }

    public function test_d_completion_aggregate_recheck_detects_invalid_full_refund(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL transaction-isolation semantics.');
        }

        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // Simulate an inconsistent state: COMPLETED 700 + PENDING 700 on a
        // 1000 payment (total reserved 1400, over-refunded).
        Refund::create(['payment_id' => $payment->id, 'amount' => 700, 'status' => RefundStatus::COMPLETED->value, 'refunded_at' => now()]);
        Refund::create(['payment_id' => $payment->id, 'amount' => 700, 'status' => RefundStatus::PENDING->value]);

        $payment->refresh();
        $service = app(RefundConstraintService::class);

        // Raw balance is negative — over-refund detected.
        $this->assertSame(-400, $service->rawRefundableBalance($payment));

        // Clamped refundable is zero (nothing more may be refunded).
        $this->assertSame(0, $service->refundableAmount($payment));

        // NOT fully refunded: the over-refund must never be masked as REFUNDED.
        $this->assertFalse($service->isFullyRefunded($payment));

        // A new refund on top is rejected outright.
        $this->expectException(RefundConstraintViolationException::class);
        app(RefundCore::class)->processRefund($payment, 1);
    }
}
