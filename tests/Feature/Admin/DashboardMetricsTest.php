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
}
