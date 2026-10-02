<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function createOrder(): Order
    {
        $order = new Order;
        foreach ([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09120000000',
            'shipping_address' => 'تهران، خیابان ولیعصر',
            'shipping_postal_code' => '1234567890',
            'total_price' => 100000,
            'token' => Str::random(40),
            'reference' => 'ORD-2026-000001',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::UNPAID,
        ] as $key => $value) {
            $order->setAttribute($key, $value);
        }

        $order->save();

        return $order;
    }

    public function test_customer_order_history_routes_are_no_longer_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->createOrder();

        $this->actingAs($admin)->get('/orders')->assertNotFound();
        $this->actingAs($admin)->get('/orders/'.$order->id)->assertNotFound();
        $this->get('/orders')->assertNotFound();
    }

    public function test_order_tracking_cannot_be_accessed_with_invalid_token(): void
    {
        $this->post(route('order-tracking.check'), ['token' => Str::random(40)])
            ->assertSessionHasErrors(['token']);
    }

    public function test_order_tracking_succeeds_with_valid_token(): void
    {
        $order = $this->createOrder();

        $this->post(route('order-tracking.check'), ['token' => $order->token])
            ->assertOk()
            ->assertSee($order->reference)
            ->assertDontSee($order->customer_phone);
    }
}
