<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\RefundStatus;
use App\Livewire\Admin\UserManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ReportingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class FinalConsistencyCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'phone' => '09121111111',
            'is_active' => true,
        ]);
    }

    private function createCustomer(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'phone' => '09122222222',
            'is_active' => true,
        ], $attrs));
    }

    public function test_user_manager_lists_and_counts_customers_only(): void
    {
        $admin = $this->createAdmin();
        $customer1 = $this->createCustomer(['phone' => '09123333333']);
        $customer2 = $this->createCustomer(['phone' => '09124444444']);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->assertSee($customer1->phone)
            ->assertSee($customer2->phone)
            ->assertDontSee($admin->phone);
    }

    public function test_user_manager_cannot_view_or_mutate_admin_user(): void
    {
        $admin = $this->createAdmin();
        $targetAdmin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09129999999',
            'is_active' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('viewUser', $targetAdmin->id)
            ->assertSet('selectedUserId', null)
            ->call('blockUser', $targetAdmin->id);

        $this->assertTrue((bool) $targetAdmin->fresh()->is_active);
        $this->assertNull($targetAdmin->fresh()->blocked_at);
    }

    public function test_otp_service_has_active_code_is_removed(): void
    {
        $this->assertFalse(
            method_exists(OtpService::class, 'hasActiveCode'),
            'OtpService::hasActiveCode should be removed'
        );
    }

    public function test_top_products_excludes_fully_refunded_and_cancelled_orders(): void
    {
        $product1 = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول معتبر',
            'slug' => 'valid-prod',
            'base_price' => 100000,
            'is_active' => true,
        ]);
        $product2 = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول مرجوعی',
            'slug' => 'refunded-prod',
            'base_price' => 50000,
            'is_active' => true,
        ]);
        $product3 = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول لغو شده',
            'slug' => 'cancelled-prod',
            'base_price' => 70000,
            'is_active' => true,
        ]);

        $from = Carbon::now()->subDays(5);
        $to = Carbon::now()->addDays(5);

        // 1. Valid paid order
        $validOrder = new Order([
            'customer_name' => 'علی',
            'customer_phone' => '09120000001',
        ]);
        $validOrder->status = OrderStatusEnum::PROCESSING;
        $validOrder->payment_status = PaymentStatusEnum::PAID;
        $validOrder->total_price = 100000;
        $validOrder->save();

        Payment::create([
            'order_id' => $validOrder->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 100000,
            'paid_amount' => 100000,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $validOrder->id,
            'product_id' => $product1->id,
            'product_name_snapshot' => $product1->name,
            'unit_price_snapshot' => 100000,
            'quantity' => 2,
            'final_price' => 200000,
            'customization_json' => [],
        ]);

        // 2. Fully refunded order
        $refundedOrder = new Order([
            'customer_name' => 'رضا',
            'customer_phone' => '09120000002',
        ]);
        $refundedOrder->status = OrderStatusEnum::COMPLETED;
        $refundedOrder->payment_status = PaymentStatusEnum::REFUNDED;
        $refundedOrder->total_price = 50000;
        $refundedOrder->save();

        Payment::create([
            'order_id' => $refundedOrder->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 50000,
            'paid_amount' => 50000,
            'paid_at' => now(),
        ]);
        OrderItem::create([
            'order_id' => $refundedOrder->id,
            'product_id' => $product2->id,
            'product_name_snapshot' => $product2->name,
            'unit_price_snapshot' => 50000,
            'quantity' => 1,
            'final_price' => 50000,
            'customization_json' => [],
        ]);

        // 3. Cancelled order
        $cancelledOrder = new Order([
            'customer_name' => 'سارا',
            'customer_phone' => '09120000003',
        ]);
        $cancelledOrder->status = OrderStatusEnum::CANCELLED;
        $cancelledOrder->payment_status = PaymentStatusEnum::FAILED;
        $cancelledOrder->total_price = 70000;
        $cancelledOrder->save();

        OrderItem::create([
            'order_id' => $cancelledOrder->id,
            'product_id' => $product3->id,
            'product_name_snapshot' => $product3->name,
            'unit_price_snapshot' => 70000,
            'quantity' => 1,
            'final_price' => 70000,
            'customization_json' => [],
        ]);

        $service = app(ReportingService::class);
        $top = $service->topProducts($from, $to);

        $productNames = array_column($top, 'product_name');

        $this->assertContains('محصول معتبر', $productNames);
        $this->assertNotContains('محصول مرجوعی', $productNames);
        $this->assertNotContains('محصول لغو شده', $productNames);
    }

    public function test_refund_period_accounting_uses_refunded_at(): void
    {
        $reporting = app(ReportingService::class);

        $month1Start = Carbon::parse('2026-01-01 00:00:00');
        $month1End = Carbon::parse('2026-01-31 23:59:59');

        $month2Start = Carbon::parse('2026-02-01 00:00:00');
        $month2End = Carbon::parse('2026-02-28 23:59:59');

        // Payment paid in Month 1
        $order = new Order([
            'customer_name' => 'مشتری',
            'customer_phone' => '09120000000',
        ]);
        $order->status = OrderStatusEnum::COMPLETED;
        $order->payment_status = PaymentStatusEnum::REFUNDED;
        $order->total_price = 300000;
        $order->created_at = Carbon::parse('2026-01-10 12:00:00');
        $order->save();

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::GATEWAY->value,
            'status' => PaymentStatus::SUCCESS->value,
            'amount' => 300000,
            'paid_amount' => 300000,
            'paid_at' => Carbon::parse('2026-01-10 12:05:00'),
        ]);

        // Refund executed in Month 2
        Refund::create([
            'payment_id' => $payment->id,
            'amount' => 300000,
            'status' => RefundStatus::COMPLETED,
            'refunded_at' => Carbon::parse('2026-02-05 15:00:00'),
        ]);

        // Month 1 report: Revenue should be positive (payment in window, no refund in window)
        $m1Summary = $reporting->summary($month1Start, $month1End);
        $this->assertEquals(300000, $m1Summary['revenue']);
        $this->assertEquals(0, $m1Summary['refunded']);

        // Month 2 report: Revenue should reflect cash outflow (-300000), refunded = 300000
        $m2Summary = $reporting->summary($month2Start, $month2End);
        $this->assertEquals(-300000, $m2Summary['revenue']);
        $this->assertEquals(300000, $m2Summary['refunded']);
    }

    public function test_gateway_callback_is_rate_limited_and_returns_404_for_unknown(): void
    {
        $response = $this->postJson('/checkout/payment/callback/zarinpal', [
            'reference' => 'REF12345',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['status' => 'unknown_gateway']);
    }

    public function test_stale_livewire_tmp_files_and_dev_database_are_clean(): void
    {
        // 1. Confirm leftover dev db schema is dropped (if running on mysql connection)
        if (config('database.default') === 'mysql' || DB::getDriverName() === 'mysql') {
            $databases = DB::select("SHOW DATABASES LIKE 'elva_card_shop_test_nyx24'");
            $this->assertEmpty($databases, 'Leftover dev database elva_card_shop_test_nyx24 must be dropped.');
        }

        // 2. Confirm no stale .png.json files exist in livewire-tmp
        $staleJsonFiles = File::glob(storage_path('app/public/livewire-tmp/*.png.json'));
        $this->assertEmpty($staleJsonFiles, 'No stale *.png.json files should remain in livewire-tmp.');
    }
}
