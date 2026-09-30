<?php

namespace Tests\Feature\Admin;

use App\Contracts\Payments\RefundRetrieveResult;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundStatus;
use App\Exceptions\RefundConstraintViolationException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\ManualRefundService;
use App\Services\PaymentGatewayManager;
use App\Services\RefundConstraintService;
use App\Services\RefundCore;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BasicFakePaymentGateway;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/**
 * N-Onyx-26-b: Refund Financial Integrity
 *
 * Phase 1: Gateway concurrency hardening (MySQL-authoritative).
 * Phase 2: Provider idempotency / reconciliation.
 * Minimum reporting test for REVIEW revenue semantics.
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

    private function registerBasicGateway(): BasicFakePaymentGateway
    {
        $fake = new BasicFakePaymentGateway('fake');

        $this->app->singleton(BasicFakePaymentGateway::class, fn (): BasicFakePaymentGateway => $fake);
        $this->app->instance(PaymentGatewayManager::class, new PaymentGatewayManager($this->app, [
            'fake' => BasicFakePaymentGateway::class,
        ]));

        return $fake;
    }

    // =========================================================================
    // PHASE 1 — Gateway Refund Concurrency
    // =========================================================================

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
        $paymentOnDefault = Payment::where('id', $payment->id)->first();
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

    public function test_d_completion_aggregate_recheck_blocks_invalid_full_refund(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL transaction-isolation semantics.');
        }

        $this->registerFakeGateway();
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // Simulate an inconsistent state: COMPLETED 700 + REVIEW 700 on a
        // 1000 payment (total reserved = 1400, over-refunded).
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);

        $openReview = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'test-review-key'],
        ]);

        // Provider lookup confirms the REVIEW refund succeeded.
        $fake = FakePaymentGateway::class;
        $fakeInstance = new $fake;
        $fakeInstance->lookupResult = RefundRetrieveResult::success('RFN-LOOKUP-123');

        $this->app->singleton($fake, fn () => $fakeInstance);
        $this->app->instance(PaymentGatewayManager::class, new PaymentGatewayManager($this->app, [
            'fake' => $fake,
        ]));

        // RetryReview triggers completeRefund, which detects negative raw balance.
        $service = app(RefundCore::class);
        $result = $service->retryReviewRefund($openReview->id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertTrue(($result->metadata['integrity_violation'] ?? false));
        $this->assertSame(-400, $result->metadata['raw_balance_after'] ?? null);

        // Order MUST NOT be marked REFUNDED — raw balance is invalid.
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);

        // isFullyRefunded returns false when raw balance is negative.
        $payment->refresh();
        $this->assertFalse(app(RefundConstraintService::class)->isFullyRefunded($payment));
    }

    // =========================================================================
    // PHASE 2 — Provider Idempotency / Reconciliation
    // =========================================================================

    public function test_a_provider_timeout_produces_review_not_failed(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 700);

        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame('provider_unknown', $refund->metadata['reason'] ?? null);

        // REVIEW reserves balance — refundable drops.
        $payment->refresh();
        $this->assertSame(300, app(RefundConstraintService::class)->refundableAmount($payment));
    }

    public function test_b_review_reserves_refundable_balance(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'review-key'],
        ]);

        $payment->refresh();
        $service = app(RefundConstraintService::class);

        $this->assertSame(700, $service->reservedAmount($payment));
        $this->assertSame(300, $service->refundableAmount($payment));
        $this->assertSame(300, $service->rawRefundableBalance($payment));
    }

    public function test_b_pending_review_completed_all_reserve_balance(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        Refund::create(['payment_id' => $payment->id, 'amount' => 200, 'status' => RefundStatus::PENDING->value]);
        Refund::create(['payment_id' => $payment->id, 'amount' => 300, 'status' => RefundStatus::REVIEW->value, 'metadata' => ['idempotency_key' => 'r1']]);
        Refund::create(['payment_id' => $payment->id, 'amount' => 200, 'status' => RefundStatus::COMPLETED->value, 'refunded_at' => now()]);

        $payment->refresh();
        $this->assertSame(300, app(RefundConstraintService::class)->refundableAmount($payment));
    }

    public function test_c_same_logical_retry_reuses_idempotency_key(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $service = app(RefundCore::class);

        // First attempt: timeout → REVIEW (full 1000 refund so a later
        // completion legitimately marks the order REFUNDED).
        $refund = $service->processRefund($payment, 1000);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $fake->refundCalls);
        $this->assertSame(1, $fake->processedRefunds);

        $firstKey = $refund->metadata['idempotency_key'];
        $this->assertNotEmpty($firstKey);

        // Retry: lookup unknown → idempotent same-key re-invoke → dedupe → COMPLETED.
        $fake->lookupResult = null; // unknown
        $result = $service->retryReviewRefund($refund->id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame(2, $fake->refundCalls);
        // Only one REAL provider-side refund occurred; the second was deduped.
        $this->assertSame(1, $fake->processedRefunds);
        $this->assertTrue($fake->lastRefundWasDedupe);

        // Both calls carried the exact same idempotency key.
        $this->assertCount(2, $fake->observedRefundIdempotencyKeys);
        $this->assertSame($firstKey, $fake->observedRefundIdempotencyKeys[0]);
        $this->assertSame($firstKey, $fake->observedRefundIdempotencyKeys[1]);

        // Ledger carries the eventual success; providerRefundId consistent.
        $this->assertStringContainsString('RFN-IDEM-'.$firstKey, $result->metadata['provider_refund_id'] ?? '');

        // Order fully refunded.
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_d_lookup_success_completes_without_second_refund_call(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // Create a REVIEW refund (simulating a prior timeout).
        $openReview = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 1000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'lookup-test-key'],
        ]);

        // Configure fake: lookup returns success with a provider refund ID.
        $fake->lookupResult = RefundRetrieveResult::success('RFN-LOOKUP-SUCCESS');

        $service = app(RefundCore::class);
        $result = $service->retryReviewRefund($openReview->id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame('RFN-LOOKUP-SUCCESS', $result->metadata['provider_refund_id'] ?? null);
        $this->assertSame('provider_lookup', $result->metadata['reconciled_via'] ?? null);

        // No refund() call was made — resolved entirely via lookup.
        $this->assertSame(0, $fake->refundCalls);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_e_lookup_confirmed_failure_releases_reservation(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $openReview = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'lookup-fail-key'],
        ]);

        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        $service = app(RefundCore::class);
        $result = $service->retryReviewRefund($openReview->id);

        $this->assertSame(RefundStatus::FAILED, $result->status);
        $this->assertSame('confirmed_by_lookup', $result->metadata['reason'] ?? null);
        $this->assertSame(0, $fake->refundCalls);

        // Reservation released: refundable back to full.
        $payment->refresh();
        $this->assertSame(1000, app(RefundConstraintService::class)->refundableAmount($payment));
    }

    public function test_f_unknown_lookup_no_idempotent_remains_review(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->supportsIdempotentRetry = false;

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $openReview = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 700,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'unknown-no-retry'],
        ]);

        // lookup returns UNKNOWN (default).
        $fake->lookupResult = null;

        $service = app(RefundCore::class);

        $this->expectException(RefundConstraintViolationException::class);
        $service->retryReviewRefund($openReview->id);

        // Refund stays REVIEW — no blind second refund() call.
        $openReview->refresh();
        $this->assertSame(RefundStatus::REVIEW, $openReview->status);
        $this->assertSame(0, $fake->refundCalls);
    }

    public function test_f_basic_provider_remains_review(): void
    {
        $fake = $this->registerBasicGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // First attempt: timeout → REVIEW.
        $refund = app(RefundCore::class)->processRefund($payment, 700);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $fake->refundCalls);

        // Retry: no lookup, no idempotent → remain REVIEW.
        $this->expectException(RefundConstraintViolationException::class);
        app(RefundCore::class)->retryReviewRefund($refund->id);

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $fake->refundCalls); // No blind second call.
    }

    public function test_g_confirmed_failure_retry_safe_with_new_key(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $service = app(RefundCore::class);

        // First attempt: confirmed failure.
        $fake->failOnRefund = true;
        $failedRefund = $service->processRefund($payment, 700);

        $this->assertSame(RefundStatus::FAILED, $failedRefund->status);
        $this->assertSame(1, $fake->refundCalls);
        $this->assertSame(0, $fake->processedRefunds);

        $failedKey = $failedRefund->metadata['idempotency_key'] ?? '';

        // Reservation released: refundable is back to full.
        $payment->refresh();
        $this->assertSame(1000, app(RefundConstraintService::class)->refundableAmount($payment));

        // Second attempt (new logical refund, new key): success.
        $fake->failOnRefund = false;
        $successRefund = $service->processRefund($payment, 700);

        $this->assertSame(RefundStatus::COMPLETED, $successRefund->status);
        $this->assertSame(2, $fake->refundCalls);
        $this->assertSame(1, $fake->processedRefunds);

        $successKey = $successRefund->metadata['idempotency_key'] ?? '';
        $this->assertNotEmpty($successKey);
        $this->assertNotSame($failedKey, $successKey, 'New attempt must use a new idempotency key.');
        // The failed key's ledger entry is a confirmed failure — never an
        // eventual_outcome placeholder (which only arises from a timeout).
        $this->assertArrayNotHasKey('eventual_outcome', $fake->ledger[$failedKey]->metadata);
    }

    // =========================================================================
    // REPORTING COMPATIBILITY
    // =========================================================================

    public function test_review_does_not_reduce_recognized_revenue(): void
    {
        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // REVIEW: should NOT reduce revenue.
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 400,
            'status' => RefundStatus::REVIEW->value,
            'refunded_at' => now(),
            'metadata' => ['idempotency_key' => 'review-report'],
        ]);

        // COMPLETED: should reduce revenue.
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 300,
            'status' => RefundStatus::COMPLETED->value,
            'refunded_at' => now(),
        ]);

        $from = now()->subDay();
        $to = now()->addDay();

        $service = app(ReportingService::class);
        $summary = $service->summary($from, $to);

        $this->assertSame(1000 - 300, $summary['revenue']);
        $this->assertSame(300, $summary['refunded']);
        $this->assertSame(1, $summary['totalRefunds']);
    }

    // =========================================================================
    // ORDER STATE DERIVATION (OP-06)
    // =========================================================================

    /**
     * Order state is a property of the order, not of one payment. The schema
     * does not forbid a historical/imported order from carrying more than one
     * successful payment, so refunding one of them to zero must never report the
     * whole order as refunded while the other payment is still collected.
     */
    public function test_order_stays_paid_until_every_successful_payment_is_refunded(): void
    {
        $this->registerFakeGateway();

        $order = $this->createOrder(2000);
        $this->markOrderPaid($order);

        $first = $this->createSuccessfulPayment($order, 1000);
        $second = $this->createSuccessfulPayment($order, 1000);

        $service = app(RefundCore::class);

        $service->processRefund($first, 1000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(0, app(RefundConstraintService::class)->refundableAmount($first->fresh()));
        $this->assertSame(1000, app(RefundConstraintService::class)->refundableAmount($second->fresh()));

        $service->processRefund($second, 1000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_manual_refund_also_requires_every_successful_payment_to_be_refunded(): void
    {
        $this->registerFakeGateway();
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $order = $this->createOrder(2000);
        $this->markOrderPaid($order);

        $manual = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 1000,
            'paid_amount' => 1000,
            'paid_at' => now(),
            'tracking_code' => 'MANUAL-OP06',
        ]);

        $gateway = $this->createSuccessfulPayment($order, 1000);

        app(ManualRefundService::class)->refund($manual, 1000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);

        app(RefundCore::class)->processRefund($gateway, 1000);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    public function test_partially_refunded_order_is_never_marked_refunded(): void
    {
        $this->registerFakeGateway();

        $order = $this->createOrder(1000);
        $payment = $this->createSuccessfulPayment($order);
        $this->markOrderPaid($order);

        app(RefundCore::class)->processRefund($payment, 400);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertFalse(app(RefundConstraintService::class)->isOrderFullyRefunded($order->fresh()));
    }

    // =========================================================================
    // RECONCILIATION AUDIT TRAIL
    // =========================================================================

    public function test_gateway_refund_records_created_by_in_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);
        $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 700);

        $this->assertSame($admin->id, $refund->metadata['created_by'] ?? null);
        $this->assertArrayHasKey('created_at', $refund->metadata);
        $this->assertArrayHasKey('idempotency_key', $refund->metadata);
    }

    public function test_reconciliation_records_attempts_and_resolver_on_resolution(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $fake = $this->registerFakeGateway();

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $openReview = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 1000,
            'status' => RefundStatus::REVIEW->value,
            'metadata' => ['idempotency_key' => 'attempt-audit-key'],
        ]);

        // First reconcile: lookup unknown → idempotent retry of the same key →
        // the pre-recorded provider outcome is returned and the refund completes.
        $fake->lookupResult = RefundRetrieveResult::success('RFN-AUDIT-RECON');

        $result = app(RefundCore::class)->retryReviewRefund($openReview->id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame(1, $result->metadata['reconciliation_attempts'] ?? null);
        $this->assertSame($admin->id, $result->metadata['resolved_by'] ?? null);
        $this->assertArrayHasKey('resolved_at', $result->metadata);
        $this->assertSame('provider_lookup', $result->metadata['reconciled_via'] ?? null);
    }

    public function test_reconciliation_attempts_increment_across_failed_attempts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $fake = $this->registerBasicGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder();
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $refund = app(RefundCore::class)->processRefund($payment, 700);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        // First reconcile: no lookup, no idempotent retry → stays REVIEW.
        try {
            app(RefundCore::class)->retryReviewRefund($refund->id);
            $this->fail('Expected RefundConstraintViolationException.');
        } catch (RefundConstraintViolationException $e) {
            // Expected.
        }

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(1, $refund->metadata['reconciliation_attempts'] ?? null);

        // Second reconcile: still unverifiable → attempt counter increments again.
        try {
            app(RefundCore::class)->retryReviewRefund($refund->id);
            $this->fail('Expected RefundConstraintViolationException.');
        } catch (RefundConstraintViolationException $e) {
            // Expected.
        }

        $refund->refresh();
        $this->assertSame(RefundStatus::REVIEW, $refund->status);
        $this->assertSame(2, $refund->metadata['reconciliation_attempts'] ?? null);
        $this->assertSame(1, $fake->refundCalls, 'Never re-invokes the provider without idempotency support.');
    }

    // =========================================================================
    // N-Onyx-44-Fix — REFUND LIFECYCLE INTEGRITY (F-01 / F-02)
    // =========================================================================

    /**
     * F-01: an unresolved reservation is not a settled refund.
     *
     * Payment A holds a full-amount REVIEW refund and payment B's full refund
     * settles. The order must stay PAID: the money on A is still unconfirmed,
     * so REFUNDED would misreport the order exactly like the dashboard and the
     * financial reports, which only recognise COMPLETED refunds, do not.
     */
    public function test_f01_full_review_on_one_payment_never_marks_a_fully_refunded_sibling_order(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder(2000);
        $this->markOrderPaid($order);

        $unresolved = $this->createSuccessfulPayment($order, 1000);
        $settled = $this->createSuccessfulPayment($order, 1000);

        $service = app(RefundCore::class);

        // Payment A: the provider times out, so the refund lands in REVIEW.
        $reviewRefund = $service->processRefund($unresolved, 1000);
        $this->assertSame(RefundStatus::REVIEW, $reviewRefund->status);

        // Payment B: the same gateway now settles cleanly.
        $fake->timeoutOnRefund = false;
        $settledRefund = $service->processRefund($settled, 1000);
        $this->assertSame(RefundStatus::COMPLETED, $settledRefund->status);

        // The review reservation still consumes A's balance...
        $this->assertSame(0, app(RefundConstraintService::class)->refundableAmount($unresolved->fresh()));

        // ...but it is not settled money, so the order must not be REFUNDED.
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertFalse(app(RefundConstraintService::class)->isOrderFullyRefunded($order->fresh()));
    }

    /**
     * F-01: the order reaches REFUNDED only once every reservation settles.
     *
     * Same setup as the previous test, then payment A's REVIEW is reconciled
     * to COMPLETED through provider lookup. Only then is the whole order
     * genuinely returned, so REFUNDED becomes correct.
     */
    public function test_f01_order_becomes_refunded_only_after_every_review_settles(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder(2000);
        $this->markOrderPaid($order);

        $unresolved = $this->createSuccessfulPayment($order, 1000);
        $settled = $this->createSuccessfulPayment($order, 1000);

        $service = app(RefundCore::class);

        $reviewRefund = $service->processRefund($unresolved, 1000);
        $this->assertSame(RefundStatus::REVIEW, $reviewRefund->status);

        $fake->timeoutOnRefund = false;
        $this->assertSame(RefundStatus::COMPLETED, $service->processRefund($settled, 1000)->status);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);

        // The provider confirms A's refund really did go through.
        $fake->lookupResult = RefundRetrieveResult::success('RFN-F01-SETTLED');

        $resolved = $service->retryReviewRefund($reviewRefund->id);
        $this->assertSame(RefundStatus::COMPLETED, $resolved->status);
        $this->assertSame('provider_lookup', $resolved->metadata['reconciled_via'] ?? null);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
        $this->assertTrue(app(RefundConstraintService::class)->isOrderFullyRefunded($order->fresh()));
    }

    /**
     * F-01: a single full REVIEW on a lone payment must not report REFUNDED.
     */
    public function test_f01_single_full_review_never_reports_the_order_refunded(): void
    {
        $fake = $this->registerFakeGateway();
        $fake->timeoutOnRefund = true;

        $order = $this->createOrder(1000);
        $this->markOrderPaid($order);

        $payment = $this->createSuccessfulPayment($order, 1000);

        $refund = app(RefundCore::class)->processRefund($payment, 1000);
        $this->assertSame(RefundStatus::REVIEW, $refund->status);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertFalse(app(RefundConstraintService::class)->isOrderFullyRefunded($order->fresh()));
    }

    /**
     * F-02: a PENDING row left behind by a crash is reconcilable.
     *
     * A process that dies between the reservation commit and the provider call
     * leaves a PENDING row. Reconciliation must accept it, promote it to
     * REVIEW, and then resolve it like any other unresolved refund.
     */
    public function test_f02_stale_pending_reservation_is_reconcilable(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder(1000);
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        // Simulate the crash: the reservation committed, the provider was
        // never called, so the row is still PENDING.
        $stale = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 1000,
            'status' => RefundStatus::PENDING->value,
            'metadata' => ['idempotency_key' => 'stale-pending-key'],
        ]);

        $this->assertSame(0, app(RefundConstraintService::class)->refundableAmount($payment->fresh()));

        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        $result = app(RefundCore::class)->retryReviewRefund($stale->id);

        $this->assertSame(RefundStatus::FAILED, $result->status);
        $this->assertTrue($result->metadata['promoted_from_pending'] ?? false);
        $this->assertSame('stale_pending_reservation_reconciled', $result->metadata['promotion_reason'] ?? null);
        $this->assertSame(1, $result->metadata['reconciliation_attempts'] ?? null);

        // The order was never over-claimed, and the money is free again.
        $order->refresh();
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame(1000, app(RefundConstraintService::class)->refundableAmount($payment->fresh()));
    }

    /**
     * F-02: a stale PENDING does not permanently lock the payment.
     *
     * While PENDING the payment must stay blocked — the same money may already
     * have moved. Once the PENDING row is reconciled to a terminal state, a
     * fresh refund must be possible; otherwise the payment is dead forever.
     */
    public function test_f02_pending_does_not_permanently_lock_the_payment(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder(1000);
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 1000,
            'status' => RefundStatus::PENDING->value,
            'metadata' => ['idempotency_key' => 'stale-lock-key'],
        ]);

        $service = app(RefundCore::class);

        // Still blocked while the reservation is unresolved.
        try {
            $service->processRefund($payment->fresh(), 1000);
            $this->fail('Expected RefundConstraintViolationException while a PENDING reservation is open.');
        } catch (RefundConstraintViolationException) {
            // Expected.
        }

        $this->assertSame(RefundStatus::PENDING, Refund::query()->firstOrFail()->status);

        // Reconcile the orphan out of the way.
        $fake->lookupResult = RefundRetrieveResult::confirmedFailure('Provider confirms refund never occurred.');

        $stale = Refund::query()->firstOrFail();
        $this->assertSame(RefundStatus::FAILED, $service->retryReviewRefund($stale->id)->status);

        // The payment is usable again — no permanent lock.
        $fake->lookupResult = null;
        $fresh = $service->processRefund($payment->fresh(), 1000);

        $this->assertSame(RefundStatus::COMPLETED, $fresh->status);
        $this->assertSame(1000, $fresh->amount);

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    /**
     * F-02: a stale PENDING can also be settled, not only released.
     */
    public function test_f02_stale_pending_can_be_completed_through_reconciliation(): void
    {
        $fake = $this->registerFakeGateway();

        $order = $this->createOrder(1000);
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        $stale = Refund::create([
            'payment_id' => $payment->id,
            'amount' => 1000,
            'status' => RefundStatus::PENDING->value,
            'metadata' => ['idempotency_key' => 'stale-settle-key'],
        ]);

        $fake->lookupResult = RefundRetrieveResult::success('RFN-F02-SETTLED');

        $result = app(RefundCore::class)->retryReviewRefund($stale->id);

        $this->assertSame(RefundStatus::COMPLETED, $result->status);
        $this->assertSame(0, $fake->refundCalls, 'Resolved by lookup, never a blind second provider call.');

        $order->refresh();
        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);
    }

    /**
     * F-02: terminal refunds stay terminal — reconciliation is still refused.
     */
    public function test_f02_terminal_refunds_remain_unreconcilable(): void
    {
        $this->registerFakeGateway();

        $order = $this->createOrder(1000);
        $payment = $this->createSuccessfulPayment($order, 1000);
        $this->markOrderPaid($order);

        foreach ([RefundStatus::COMPLETED, RefundStatus::FAILED] as $status) {
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'amount' => 100,
                'status' => $status->value,
                'metadata' => ['idempotency_key' => 'terminal-'.$status->value],
            ]);

            try {
                app(RefundCore::class)->retryReviewRefund($refund->id);
                $this->fail("Expected RefundConstraintViolationException for {$status->value}.");
            } catch (RefundConstraintViolationException) {
                // Expected.
            }

            $this->assertSame($status, $refund->fresh()->status);
        }
    }
}
