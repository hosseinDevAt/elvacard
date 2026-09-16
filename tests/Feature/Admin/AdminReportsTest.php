<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\Reports;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Support\Dates\DateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function jalali(CarbonInterface|string $value): string
    {
        $dates = app(DateService::class);

        return $dates->ascii($dates->jDate(Carbon::parse($value)));
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function product(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول تستی',
            'slug' => 'report-product-'.uniqid(),
            'base_price' => 100000,
            'is_active' => true,
        ]);
    }

    private function createOrder(?User $user = null, array $overrides = []): Order
    {
        $order = new Order([
            'customer_name' => 'مشتری گزارش',
            'customer_phone' => '09123456789',
        ]);
        $order->user_id = $user?->id;
        $order->total_price = (int) ($overrides['total_price'] ?? 100000);
        $order->status = $overrides['status'] ?? OrderStatusEnum::PENDING;

        if (isset($overrides['created_at'])) {
            $order->created_at = $overrides['created_at'];
            $order->updated_at = $overrides['created_at'];
        }

        $order->save();

        return $order;
    }

    private function createPayment(Order $order, PaymentStatus $status, int $amount, ?string $method = null, ?string $paidAt = null): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => $method ?? PaymentMethod::GATEWAY->value,
            'status' => $status->value,
            'amount' => $amount,
            'paid_amount' => $status === PaymentStatus::SUCCESS ? $amount : null,
            'paid_at' => $paidAt ?? ($status === PaymentStatus::SUCCESS ? now() : null),
        ]);
    }

    private function createOrderItem(Order $order, ?Product $product = null, int $quantity = 1, int $finalPrice = 100000, string $name = 'کارت فلزی'): OrderItem
    {
        $product ??= $this->product();

        return OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $name,
            'unit_price_snapshot' => $finalPrice,
            'quantity' => $quantity,
            'final_price' => $finalPrice * $quantity,
            'customization_json' => [],
        ]);
    }

    // --- Authorization ---

    public function test_guest_is_redirected_from_reports(): void
    {
        $this->get(route('admin.reports'))->assertRedirect(route('login'));
    }

    public function test_customer_is_denied_reports(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.reports'))
            ->assertForbidden();
    }

    public function test_admin_can_access_reports(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertOk();
    }

    // --- Revenue definition ---

    public function test_successful_payment_counts_as_revenue(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 200000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 200000);
    }

    public function test_pending_payment_not_counted_as_revenue(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::PENDING, 150000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 0);
    }

    public function test_failed_payment_not_counted_as_revenue(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::FAILED, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 0);
    }

    public function test_cancelled_payment_not_counted_as_revenue(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::CANCELLED, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 0);
    }

    public function test_revenue_uses_paid_amount_not_amount(): void
    {
        $order = $this->createOrder(overrides: ['total_price' => 300000]);
        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 300000,
            'paid_amount' => 280000,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 280000);
    }

    public function test_no_double_counting_with_multiple_payments(): void
    {
        $order = $this->createOrder(overrides: ['total_price' => 200000]);

        $this->createPayment($order, PaymentStatus::FAILED, 200000);
        $this->createPayment($order, PaymentStatus::SUCCESS, 200000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 200000);
    }

    public function test_successful_payment_without_paid_at_not_in_revenue(): void
    {
        $order = $this->createOrder();
        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 100000,
            'paid_amount' => 100000,
            'paid_at' => null,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.revenue', 0);
    }

    // --- Order status ---

    public function test_order_status_breakdown_is_correct(): void
    {
        $this->createOrder(overrides: ['status' => OrderStatusEnum::PENDING]);
        $this->createOrder(overrides: ['status' => OrderStatusEnum::CONFIRMED]);
        $this->createOrder(overrides: ['status' => OrderStatusEnum::COMPLETED]);
        $this->createOrder(overrides: ['status' => OrderStatusEnum::CANCELLED]);
        $this->createOrder(overrides: ['status' => OrderStatusEnum::CANCELLED]);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('orderStatusBreakdown.pending.count', 1)
            ->assertSet('orderStatusBreakdown.confirmed.count', 1)
            ->assertSet('orderStatusBreakdown.completed.count', 1)
            ->assertSet('orderStatusBreakdown.cancelled.count', 2)
            ->assertSet('orderStatusBreakdown.processing.count', 0);
    }

    public function test_order_counted_only_once(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000);
        $this->createPayment($order, PaymentStatus::FAILED, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.totalOrders', 1)
            ->assertSet('summary.successfulPayments', 1);
    }

    // --- Date filters ---

    public function test_valid_date_range_returns_data(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 50000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->call('applyFilter')
            ->assertHasNoErrors()
            ->assertSet('summary.revenue', 50000);
    }

    public function test_from_after_to_is_rejected(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1405/12/01')
            ->set('toDate', '1405/01/01')
            ->call('applyFilter')
            ->assertHasErrors(['toDate']);
    }

    public function test_revenue_filter_uses_paid_at(): void
    {
        $order1 = $this->createOrder();
        $this->createPayment($order1, PaymentStatus::SUCCESS, 100000, paidAt: '2026-03-01 12:00:00');

        $order2 = $this->createOrder();
        $this->createPayment($order2, PaymentStatus::SUCCESS, 200000, paidAt: '2026-06-15 12:00:00');

        $filterDate = $this->jalali('2026-06-15 12:00:00');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', $filterDate)
            ->set('toDate', $filterDate)
            ->call('applyFilter')
            ->assertSet('summary.revenue', 200000);
    }

    public function test_order_count_filter_uses_created_at(): void
    {
        $order1 = $this->createOrder(overrides: ['created_at' => '2026-03-15 12:00:00']);
        $order2 = $this->createOrder(overrides: ['created_at' => '2026-06-15 12:00:00']);

        $filterDate = $this->jalali('2026-06-15 12:00:00');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', $filterDate)
            ->set('toDate', $filterDate)
            ->call('applyFilter')
            ->assertSet('summary.totalOrders', 1);
    }

    public function test_empty_date_range_returns_zeros(): void
    {
        $order = $this->createOrder(overrides: ['created_at' => '2026-01-15 12:00:00']);
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000, paidAt: '2026-01-15 12:00:00');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1400/01/01')
            ->set('toDate', '1400/01/01')
            ->call('applyFilter')
            ->assertSet('summary.revenue', 0)
            ->assertSet('summary.totalOrders', 0);
    }

    public function test_day_boundary_inclusive(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000, paidAt: '2026-06-01 00:00:00');

        $filterDate = $this->jalali('2026-06-01 00:00:00');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', $filterDate)
            ->set('toDate', $filterDate)
            ->call('applyFilter')
            ->assertSet('summary.revenue', 100000);
    }

    public function test_clear_filter_resets_to_defaults(): void
    {
        $dates = app(DateService::class);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1405/01/01')
            ->set('toDate', '1405/01/31')
            ->call('clearFilter')
            ->assertSet('fromDate', $dates->ascii($dates->jDate($dates->now()->subDays(29))))
            ->assertSet('toDate', $dates->ascii($dates->jDate($dates->now())));
    }

    public function test_max_range_exceeding_one_year_rejected(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1400/01/01')
            ->set('toDate', '1403/01/02')
            ->call('applyFilter')
            ->assertHasErrors(['toDate']);
    }

    public function test_max_range_exactly366_days_accepted(): void
    {
        // 1403 is a leap Jalali year: Farvardin 1 .. Esfand 30 = 366 inclusive days
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1403/01/01')
            ->set('toDate', '1403/12/30')
            ->call('applyFilter')
            ->assertHasNoErrors(['toDate']);
    }

    public function test_max_range_367_days_rejected(): void
    {
        // 1403 is leap (366 days) + Farvardin 1 of 1404 = 367 inclusive days
        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', '1403/01/01')
            ->set('toDate', '1404/01/01')
            ->call('applyFilter')
            ->assertHasErrors(['toDate']);
    }

    // --- Top products ---

    public function test_top_products_aggregate_quantity_and_amount(): void
    {
        $order = $this->createOrder();
        $this->createOrderItem($order, quantity: 3, finalPrice: 200000, name: 'کارت فلزی');
        $this->createPayment($order, PaymentStatus::SUCCESS, 600000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('topProducts.0.product_name', 'کارت فلزی')
            ->assertSet('topProducts.0.total_qty', 3)
            ->assertSet('topProducts.0.total_amount', 600000)
            ->assertSet('topProducts.0.order_count', 1);
    }

    public function test_top_products_exclude_cancelled_orders(): void
    {
        $order = $this->createOrder(overrides: ['status' => OrderStatusEnum::CANCELLED]);
        $this->createOrderItem($order, quantity: 5, finalPrice: 100000, name: 'محصول لغو شده');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('topProducts', []);
    }

    public function test_top_products_exclude_unpaid_orders(): void
    {
        $order = $this->createOrder();
        $this->createOrderItem($order, quantity: 2, finalPrice: 100000, name: 'محصول بدون پرداخت');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('topProducts', []);
    }

    public function test_top_products_uses_snapshot_name_not_live_name(): void
    {
        $order = $this->createOrder();
        $this->createOrderItem($order, name: 'نام اصلی محصول');
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('topProducts.0.product_name', 'نام اصلی محصول');
    }

    public function test_top_products_show_purchaseability_metadata(): void
    {
        $order = $this->createOrder();
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول با وضعیت',
            'slug' => 'product-status-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ]);
        $this->createOrderItem($order, $product, name: 'محصول با وضعیت');
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('topProducts.0.is_active', false);
    }

    public function test_top_products_group_by_product_across_orders(): void
    {
        $product = $this->product();

        $first = $this->createOrder();
        $this->createOrderItem($first, $product, quantity: 2, finalPrice: 100000);
        $this->createPayment($first, PaymentStatus::SUCCESS, 200000);

        $second = $this->createOrder();
        $this->createOrderItem($second, $product, quantity: 3, finalPrice: 100000);
        $this->createPayment($second, PaymentStatus::SUCCESS, 300000);

        $component = Livewire::actingAs($this->admin())->test(Reports::class);

        $this->assertCount(1, $component->get('topProducts'));

        $component->assertSet('topProducts.0.total_qty', 5)
            ->assertSet('topProducts.0.total_amount', 500000)
            ->assertSet('topProducts.0.order_count', 2);
    }

    // --- Payment method breakdown ---

    public function test_manual_transfer_breakdown(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 50000, method: PaymentMethod::MANUAL_TRANSFER->value);
        $this->createPayment($order, PaymentStatus::PENDING_REVIEW, 60000, method: PaymentMethod::MANUAL_TRANSFER->value);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('paymentMethodBreakdown.manual_transfer.success_revenue', 50000)
            ->assertSet('paymentMethodBreakdown.manual_transfer.success_count', 1)
            ->assertSet('paymentMethodBreakdown.manual_transfer.pending_review_count', 1);
    }

    public function test_gateway_breakdown(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 70000, method: PaymentMethod::GATEWAY->value);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('paymentMethodBreakdown.gateway.success_revenue', 70000)
            ->assertSet('paymentMethodBreakdown.gateway.success_count', 1);
    }

    public function test_breakdown_revenue_only_from_success(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::PENDING, 100000, method: PaymentMethod::GATEWAY->value);
        $this->createPayment($order, PaymentStatus::FAILED, 100000, method: PaymentMethod::GATEWAY->value);
        $this->createPayment($order, PaymentStatus::CANCELLED, 100000, method: PaymentMethod::GATEWAY->value);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('paymentMethodBreakdown.gateway.success_revenue', 0)
            ->assertSet('paymentMethodBreakdown.gateway.success_count', 0);
    }

    // --- Active customers ---

    public function test_active_customers_count_distinct_registered_with_orders(): void
    {
        $user1 = User::factory()->create(['role' => 'customer']);
        $user2 = User::factory()->create(['role' => 'customer']);

        $this->createOrder($user1);
        $this->createOrder($user2);
        $this->createOrder(); // guest order

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.activeCustomerCount', 2);
    }

    public function test_guest_orders_excluded_from_active_customer_count(): void
    {
        $this->createOrder(); // guest
        $this->createOrder(); // guest

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('summary.activeCustomerCount', 0);
    }

    // --- Revenue trend ---

    public function test_trend_daily_buckets_for_short_range(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 100000, paidAt: '2026-06-01 12:00:00');

        $base = Carbon::parse('2026-06-01 12:00:00');

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', $this->jalali($base))
            ->set('toDate', $this->jalali($base->copy()->addDays(2)))
            ->call('applyFilter')
            ->assertSet('revenueTrend.0.period', $this->jalali($base))
            ->assertSet('revenueTrend.0.revenue', 100000)
            ->assertSet('revenueTrend.1.period', $this->jalali($base->copy()->addDay()))
            ->assertSet('revenueTrend.1.revenue', 0)
            ->assertSet('revenueTrend.2.period', $this->jalali($base->copy()->addDays(2)))
            ->assertSet('revenueTrend.2.revenue', 0);
    }

    public function test_trend_monthly_buckets_for_long_range(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::SUCCESS, 50000, paidAt: '2026-03-15 12:00:00');

        $base = Carbon::parse('2026-03-15 12:00:00');
        $dates = app(DateService::class);
        $monthKey = $dates->ascii($dates->jYearMonth($base));
        $nextMonthKey = $dates->ascii($dates->jYearMonth($base->copy()->addDays(35)));

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->set('fromDate', $this->jalali($base->copy()->startOfMonth()))
            ->set('toDate', $this->jalali($base->copy()->addDays(45)))
            ->call('applyFilter')
            ->assertSet('revenueTrend.0.period', $monthKey)
            ->assertSet('revenueTrend.0.revenue', 50000)
            ->assertSet('revenueTrend.1.period', $nextMonthKey)
            ->assertSet('revenueTrend.1.revenue', 0)
            ->assertSet('revenueTrend.2.revenue', 0);
    }

    public function test_trend_excludes_non_success_payments(): void
    {
        $order = $this->createOrder();
        $this->createPayment($order, PaymentStatus::FAILED, 100000, paidAt: '2026-06-01 12:00:00');
        $this->createPayment($order, PaymentStatus::PENDING, 100000);

        Livewire::actingAs($this->admin())
            ->test(Reports::class)
            ->assertSet('revenueTrend.0.revenue', 0)
            ->assertSet('revenueTrend.0.count', 0);
    }

    // --- Sidebar link ---

    public function test_reports_link_in_sidebar(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('href="'.route('admin.reports').'"', false)
            ->assertSee('گزارش', false);
    }

    public function test_reports_link_is_spa_navigable(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        $this->assertStringContainsString(
            'href="'.route('admin.reports').'" wire:navigate',
            $response->getContent(),
        );
    }
}
