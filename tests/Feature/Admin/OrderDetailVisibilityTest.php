<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Livewire\Admin\OrderManager;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderDetailVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createOrder(?User $user = null, array $shipping = []): Order
    {
        $order = new Order(array_merge([
            'customer_name' => 'مشتری سفارش',
            'customer_phone' => '09123456789',
        ], $shipping));
        $order->user_id = $user?->id;
        $order->total_price = 150000;
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

    public function test_admin_order_detail_shows_all_payment_attempts_including_gateway(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'zarinpal',
            'transaction_id' => 'TXN-ORDER-GW',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        $this->createPayment($order, PaymentMethod::MANUAL_TRANSFER, PaymentStatus::PENDING_REVIEW, [
            'tracking_code' => 'TRACK-ORDER-MANUAL',
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('TXN-ORDER-GW')
            ->assertSee('TRACK-ORDER-MANUAL')
            ->assertSee('درگاه آنلاین')
            ->assertSee('کارت به کارت')
            ->assertSee('zarinpal')
            ->assertSee('پرداخت #')
            ->assertSee('تایید پرداخت')
            ->assertSee('رد پرداخت');
    }

    public function test_gateway_payment_detail_does_not_offer_manual_review(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::SUCCESS, [
            'gateway' => 'zarinpal',
            'transaction_id' => 'TXN-SUCCESS-ONLY',
            'paid_amount' => $order->total_price,
            'paid_at' => now(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('TXN-SUCCESS-ONLY')
            ->assertSee('درگاه آنلاین')
            ->assertDontSee('تایید پرداخت')
            ->assertDontSee('رد پرداخت');
    }

    public function test_admin_order_detail_shows_failure_reason_metadata(): void
    {
        $order = $this->createOrder();

        $this->createPayment($order, PaymentMethod::GATEWAY, PaymentStatus::FAILED, [
            'gateway' => 'idpay',
            'transaction_id' => 'TXN-FAILED-REASON',
            'metadata' => ['reason' => 'amount_mismatch', 'detail' => 'expected 150000 got 140000'],
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('TXN-FAILED-REASON')
            ->assertSee('مغایرت مبلغ')
            ->assertSee('expected 150000 got 140000');
    }

    public function test_admin_order_detail_shows_shipping_information(): void
    {
        $order = $this->createOrder(shipping: [
            'shipping_address' => 'تهران، خیابان ولیعصر، کوچه بهار',
            'shipping_plaque' => '۱۲۳',
            'shipping_postal_code' => '1234567890',
            'shipping_description' => 'تماس بگیرید',
            'notes' => 'کارت فوری لازم است',
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('تهران، خیابان ولیعصر، کوچه بهار')
            ->assertSee('۱۲۳')
            ->assertSee('1234567890')
            ->assertSee('تماس بگیرید')
            ->assertSee('کارت فوری لازم است');
    }

    public function test_admin_order_detail_marks_registered_vs_guest_customer(): void
    {
        $registered = $this->createOrder($this->admin());
        $guest = $this->createOrder();

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $registered->id)
            ->assertSee('کاربر ثبت‌نام‌شده');

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $guest->id)
            ->assertSee('سفارش مهمان');
    }

    public function test_admin_order_detail_shows_customer_profile_data(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'name' => 'صاحب سفارش',
            'phone' => '09999999999',
        ]);
        $order = $this->createOrder($customer);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('صاحب سفارش')
            ->assertSee('09999999999')
            ->assertSee(OrderStatusEnum::PENDING->faLabel())
            ->assertSee(PaymentStatusEnum::UNPAID->faLabel());
    }
}
