<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_reliable_business_metrics(): void
    {
        User::factory()->count(2)->create(['role' => 'customer']);

        $pendingOrder = $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, 100000);
        $confirmedOrder = $this->createOrder(OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID, 200000);
        $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, 50000);

        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول استاندارد',
            'slug' => 'standard-product-'.uniqid(),
            'base_price' => 90000,
            'is_active' => true,
        ]);

        Payment::create([
            'order_id' => $confirmedOrder->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 200000,
            'paid_amount' => 200000,
        ]);

        Payment::create([
            'order_id' => $pendingOrder->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => PaymentStatus::PENDING_REVIEW->value,
            'amount' => 100000,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('totalUsers', 3)
            ->assertSet('totalOrders', 3)
            ->assertSet('pendingOrders', 2)
            ->assertSet('totalProducts', 1)
            ->assertSet('pendingReviewPayments', 1)
            ->assertSet('successfulPayments', 1)
            ->assertSet('totalRevenue', 200000);
    }

    public function test_revenue_sums_paid_amount_not_requested_amount(): void
    {
        $order = $this->createOrder(OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID, 300000);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 300000,
            'paid_amount' => 280000,
        ]);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('totalRevenue', 280000);
    }

    public function test_failed_and_cancelled_payments_do_not_count_as_revenue(): void
    {
        $failedOrder = $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, 100000);

        $this->createPaymentFor($failedOrder, PaymentStatus::FAILED, 100000);
        $this->createPaymentFor($failedOrder, PaymentStatus::CANCELLED, 100000);

        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('successfulPayments', 0)
            ->assertSet('totalRevenue', 0)
            ->assertSet('pendingReviewPayments', 0);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createOrder(OrderStatusEnum $status, PaymentStatusEnum $paymentStatus, int $total): Order
    {
        $order = new Order([
            'customer_name' => 'مشتری داشبورد',
            'customer_phone' => '09123456789',
        ]);
        $order->status = $status;
        $order->payment_status = $paymentStatus;
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createPaymentFor(Order $order, PaymentStatus $status, int $amount): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER->value,
            'status' => $status->value,
            'amount' => $amount,
        ]);
    }

    /**
     * REP-01: the weekly revenue chart used to render a hardcoded height array
     * ([45, 75, 30, 80, 80, 70, 85]) under a caption promising "automatic weekly
     * updates based on financial data". It must now be derived from real
     * successful payments minus completed refunds.
     */
    public function test_weekly_revenue_chart_is_derived_from_real_payments(): void
    {
        $order = $this->createOrder(OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID, 400000);

        Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 400000,
            'paid_amount' => 400000,
            'paid_at' => now(),
        ]);

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);

        $component->assertSet('weeklyRevenue', fn (array $series): bool => count($series) === 7);

        $series = $component->get('weeklyRevenue');

        $this->assertSame(
            ['شنبه', '۱شنبه', '۲شنبه', '۳شنبه', '۴شنبه', '۵شنبه', 'جمعه'],
            array_column($series, 'label'),
        );

        // Exactly one day in the current Jalali week holds the payment.
        $this->assertSame(
            400000,
            array_sum(array_column($series, 'value')),
        );

        $this->assertSame(
            1,
            count(array_filter($series, fn (array $day): bool => $day['value'] > 0)),
            'Only the paid day may carry revenue.',
        );

        $this->assertSame(
            100,
            max(array_column($series, 'height')),
            'The busiest day fills the bar.',
        );

        $this->assertSame(
            4,
            min(array_column($series, 'height')),
            'A zero-revenue day keeps a visible 4% floor instead of a fake height.',
        );
    }

    public function test_weekly_revenue_chart_has_no_hardcoded_heights(): void
    {
        $view = (string) file_get_contents(resource_path('views/livewire/admin/dashboard.blade.php'));

        $this->assertStringNotContainsString('$heights', $view);
        $this->assertStringNotContainsString('[45, 75, 30, 80, 80, 70, 85]', $view);
        $this->assertStringNotContainsString('به‌روزرسانی هفتگی خودکار بر اساس داده‌های مالی', $view);
    }

    public function test_weekly_revenue_chart_is_empty_without_payments(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertSet('weeklyRevenue', fn (array $series): bool => count($series) === 7
                && array_sum(array_column($series, 'value')) === 0
                && max(array_column($series, 'height')) === 4);
    }
}
