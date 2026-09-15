<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Livewire\Admin\UserManager;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAccountBlockingAndGuestOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createCustomerUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'name' => 'کاربر هدف',
            'phone' => '09111111111',
            'address' => 'تهران، خیابان آزادی',
        ], $overrides));
    }

    private function createRegisteredOrderFor(User $user, int $total): Order
    {
        $order = new Order([
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
        ]);
        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    private function createGuestOrder(string $phone, int $total): Order
    {
        $order = new Order([
            'customer_name' => 'مهمان',
            'customer_phone' => $phone,
        ]);
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    public function test_admin_can_block_a_customer_and_store_the_reason(): void
    {
        $user = $this->createCustomerUser();

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->set('blockReason', 'بدرفتاری با پشتیبانی')
            ->call('blockUser', $user->id)
            ->assertSee('حساب این کاربر مسدود است.')
            ->assertSee('بدرفتاری با پشتیبانی');

        $user->refresh();

        $this->assertFalse((bool) $user->is_active);
        $this->assertNotNull($user->blocked_at);
        $this->assertSame('بدرفتاری با پشتیبانی', $user->blocked_reason);
    }

    public function test_admin_can_unblock_a_customer_and_clears_the_reason(): void
    {
        $user = $this->createCustomerUser([
            'is_active' => false,
            'blocked_at' => now()->subDay(),
            'blocked_reason' => 'موقت',
        ]);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->call('unblockUser', $user->id);

        $user->refresh();

        $this->assertTrue((bool) $user->is_active);
        $this->assertNull($user->blocked_at);
        $this->assertNull($user->blocked_reason);
    }

    public function test_block_reason_is_limited_to_255_characters(): void
    {
        $user = $this->createCustomerUser();

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->set('blockReason', str_repeat('ز', 300))
            ->call('blockUser', $user->id)
            ->assertHasErrors('blockReason');

        $this->assertTrue((bool) $user->refresh()->is_active);
        $this->assertNull($user->blocked_at);
    }

    public function test_blocking_does_not_modify_orders_payments_or_items(): void
    {
        $user = $this->createCustomerUser();
        $order = $this->createRegisteredOrderFor($user, 150000);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->call('blockUser', $user->id);

        $this->assertSame($user->id, $order->refresh()->user_id);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(PaymentStatusEnum::PAID->value, $order->payment_status->value);
        $this->assertTrue((bool) $user->refresh()->is_active === false);
    }

    public function test_blocking_only_applies_to_customer_role(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $admin->id)
            ->call('blockUser', $admin->id)
            ->assertSee('کاربر یافت نشد');

        $this->assertTrue((bool) $admin->refresh()->is_active);
        $this->assertNull($admin->blocked_at);
    }

    public function test_customer_and_guest_cannot_block_users(): void
    {
        $user = $this->createCustomerUser();

        Livewire::test(UserManager::class)->assertStatus(403);

        Livewire::actingAs($this->createCustomerUser(['phone' => '09111112222']))
            ->test(UserManager::class)
            ->assertStatus(403);

        $this->assertTrue((bool) $user->refresh()->is_active);
    }

    public function test_blocked_state_is_visible_in_the_user_list(): void
    {
        $this->createCustomerUser([
            'name' => 'کاربر فعال',
            'phone' => '09111110001',
        ]);

        $this->createCustomerUser([
            'name' => 'کاربر مسدودشده',
            'phone' => '09111110002',
            'is_active' => false,
            'blocked_at' => now(),
            'blocked_reason' => 'تست',
        ]);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->assertSee('فعال')
            ->assertSee('مسدود')
            ->assertSee('کاربر فعال')
            ->assertSee('کاربر مسدودشده');
    }

    public function test_guest_orders_are_listed_for_the_matching_phone_without_being_attached(): void
    {
        $user = $this->createCustomerUser();

        $guestOrder = $this->createGuestOrder('09111111111', 80000);
        $unrelatedOrder = $this->createGuestOrder('09199999999', 50000);
        $registeredOrder = $this->createRegisteredOrderFor($user, 120000);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->assertSee('سفارشات مهمان با این شماره تماس')
            ->assertSee('مهمان')
            ->assertSee($guestOrder->reference)
            ->assertDontSee($unrelatedOrder->reference);

        // The guest orders are never attached to the customer account.
        $this->assertNull($guestOrder->refresh()->user_id);
        $this->assertSame($user->id, $registeredOrder->refresh()->user_id);
    }

    public function test_guest_orders_are_matched_with_normalized_phone_numbers(): void
    {
        $user = $this->createCustomerUser(['phone' => '09123456789']);

        $legacyFormat = $this->createGuestOrder('9123456789', 30000);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->assertSee($legacyFormat->reference)
            ->assertSee('مهمان');
    }

    public function test_guest_orders_are_not_listed_when_no_phone_match_exists(): void
    {
        $user = $this->createCustomerUser(['phone' => '09123456789']);

        $unrelatedOrder = $this->createGuestOrder('09199999999', 40000);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->assertSee('سفارش مهمانی با این شماره تماس یافت نشد')
            ->assertDontSee($unrelatedOrder->reference);
    }
}
