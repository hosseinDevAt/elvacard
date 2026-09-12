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

    private function createOrderForUser(User $user): Order
    {
        $order = new Order();
        foreach ([
            'user_id' => $user->id,
            'customer_name' => $user->displayName(),
            'customer_phone' => $user->phone,
            'shipping_address' => 'تهران، خیابان ولیعصر',
            'shipping_postal_code' => '1234567890',
            'total_price' => 100000,
            'token' => 'tok-'.Str::random(32),
            'reference' => 'REF-'.Str::random(6),
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::UNPAID,
        ] as $key => $value) {
            $order->setAttribute($key, $value);
        }

        $order->save();

        return $order;
    }

    public function test_customer_can_view_their_own_order(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user);

        $response = $this
            ->actingAs($user)
            ->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee($order->reference);
        $response->assertSee($order->shipping_address);
        $response->assertSee($order->shipping_postal_code);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->createOrderForUser($owner);

        $response = $this
            ->actingAs($other)
            ->get(route('orders.show', $order));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_for_order_history(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user);

        $this->get(route('orders.show', $order))
            ->assertRedirect();
    }

    public function test_orders_index_only_shows_the_authed_users_orders(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owned = $this->createOrderForUser($owner);
        $foreign = $this->createOrderForUser($other);

        $response = $this
            ->actingAs($owner)
            ->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee($owned->reference);
        $response->assertDontSee($foreign->reference);
    }
}