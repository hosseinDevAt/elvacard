<?php

namespace Tests\Feature;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\OrderLifecycleConstraintException;
use App\Livewire\Admin\OrderManager;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class OrderLifecycleIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function adminStateMachine(): OrderStateMachine
    {
        return app(OrderStateMachine::class);
    }

    private function createOrder(
        OrderStatusEnum $status,
        PaymentStatusEnum $paymentStatus,
        int $userId,
    ): Order {
        $order = new Order;

        foreach ([
            'user_id' => $userId,
            'customer_name' => 'مشتری',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، خیابان ولیعصر',
            'shipping_postal_code' => '1234567890',
            'total_price' => 100000,
            'token' => 'tok-'.Str::random(32),
            'reference' => 'ORD-2026-'.str_pad((string) Order::count(), 6, '0', STR_PAD_LEFT),
            'status' => $status,
            'payment_status' => $paymentStatus,
        ] as $key => $value) {
            $order->setAttribute($key, $value);
        }

        $order->save();

        return $order->fresh();
    }

    public function test_legal_pending_to_confirmed_succeeds(): void
    {
        $order = $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, $this->customer()->id);

        $this->adminStateMachine()->transition($order, OrderStatusEnum::CONFIRMED);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed']);
    }

    public function test_illegal_completed_to_processing_is_rejected(): void
    {
        $order = $this->createOrder(OrderStatusEnum::COMPLETED, PaymentStatusEnum::PAID, $this->customer()->id);

        $this->expectException(InvalidOrderTransitionException::class);

        $this->adminStateMachine()->transition($order, OrderStatusEnum::PROCESSING);
    }

    public function test_cancelled_to_confirmed_is_rejected(): void
    {
        $order = $this->createOrder(OrderStatusEnum::CANCELLED, PaymentStatusEnum::UNPAID, $this->customer()->id);

        $this->expectException(InvalidOrderTransitionException::class);

        $this->adminStateMachine()->transition($order, OrderStatusEnum::CONFIRMED);
    }

    public function test_paid_confirmed_order_cannot_be_cancelled(): void
    {
        $order = $this->createOrder(OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID, $this->customer()->id);

        $stateMachine = $this->adminStateMachine();

        $this->assertFalse($stateMachine->canTransition($order, OrderStatusEnum::CANCELLED));
        $this->assertSame([OrderStatusEnum::PROCESSING], $stateMachine->allowedTargets($order));

        try {
            $stateMachine->transition($order, OrderStatusEnum::CANCELLED);
            $this->fail('Paid order cancellation must be rejected.');
        } catch (OrderLifecycleConstraintException $e) {
            $this->assertSame('این سفارش پرداخت شده است؛ لغو آن نیازمند فرایند بازگشت وجه است.', $e->getMessage());
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed', 'payment_status' => 'paid']);
    }

    public function test_unpaid_processing_order_cannot_be_completed(): void
    {
        $order = $this->createOrder(OrderStatusEnum::PROCESSING, PaymentStatusEnum::UNPAID, $this->customer()->id);

        $stateMachine = $this->adminStateMachine();

        $this->assertFalse($stateMachine->canTransition($order, OrderStatusEnum::COMPLETED));

        try {
            $stateMachine->transition($order, OrderStatusEnum::COMPLETED);
            $this->fail('Unpaid order completion must be rejected.');
        } catch (OrderLifecycleConstraintException $e) {
            $this->assertSame('سفارش پرداخت‌نشده قابل تکمیل نیست.', $e->getMessage());
        }

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing', 'payment_status' => 'unpaid']);
    }

    public function test_admin_cannot_bypass_paid_cancellation_through_the_ui(): void
    {
        $order = $this->createOrder(OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID, $this->customer()->id);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->call('updateStatus', $order->id, 'cancelled');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'confirmed', 'payment_status' => 'paid']);
    }

    public function test_non_admin_cannot_update_status(): void
    {
        $order = $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, $this->customer()->id);

        Livewire::actingAs($this->customer())
            ->test(OrderManager::class)
            ->call('updateStatus', $order->id, 'confirmed');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_concurrent_status_updates_cannot_create_an_invalid_final_state(): void
    {
        $order = $this->createOrder(OrderStatusEnum::PROCESSING, PaymentStatusEnum::UNPAID, $this->customer()->id);

        // Two racing updates both validate against the freshly reloaded, locked
        // order row. On SQLite the row lock is inert, so the race is simulated
        // sequentially: the completion attempt is rejected because the order is
        // unpaid, and the cancellation that follows still yields a legal state.
        try {
            $this->adminStateMachine()->transition($order, OrderStatusEnum::COMPLETED);
            $this->fail('Completing an unpaid order must be rejected during the race.');
        } catch (OrderLifecycleConstraintException) {
        }

        $this->adminStateMachine()->transition($order, OrderStatusEnum::CANCELLED);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
    }

    public function test_transition_reloads_current_status_instead_of_trusting_stale_input(): void
    {
        $order = $this->createOrder(OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID, $this->customer()->id);

        // Simulate a competing writer: the order is cancelled after the caller
        // already holds a stale PENDING instance.
        $stale = $order->fresh();
        Order::where('id', $order->id)->update(['status' => OrderStatusEnum::CANCELLED->value]);

        $this->expectException(InvalidOrderTransitionException::class);

        $this->adminStateMachine()->transition($stale, OrderStatusEnum::CONFIRMED);
    }
}
