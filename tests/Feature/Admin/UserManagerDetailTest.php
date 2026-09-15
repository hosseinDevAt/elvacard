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

class UserManagerDetailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createCustomerUser(): User
    {
        return User::factory()->create([
            'role' => 'customer',
            'name' => 'کاربر هدف',
            'phone' => '09111111111',
            'address' => 'تهران، خیابان آزادی',
        ]);
    }

    private function createOrderFor(User $user, int $total, PaymentStatusEnum $paymentStatus): Order
    {
        $order = new Order([
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
        ]);
        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = $paymentStatus;
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    public function test_admin_can_view_customer_detail_with_history_and_statistics(): void
    {
        $user = $this->createCustomerUser();
        $paid = $this->createOrderFor($user, 150000, PaymentStatusEnum::PAID);
        $unpaid = $this->createOrderFor($user, 50000, PaymentStatusEnum::UNPAID);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->assertSet('selectedUserId', $user->id)
            ->assertSee('کاربر هدف')
            ->assertSee('09111111111')
            ->assertSee('تهران، خیابان آزادی')
            ->assertSee($paid->reference)
            ->assertSee($unpaid->reference)
            ->assertSee('سابقه سفارشات کاربر')
            ->assertSee('150,000')
            ->assertSee('پرداخت‌شده');
    }

    public function test_purchase_statistics_are_calculation_based(): void
    {
        $user = $this->createCustomerUser();
        $this->createOrderFor($user, 100000, PaymentStatusEnum::PAID);
        $this->createOrderFor($user, 200000, PaymentStatusEnum::PAID);
        $this->createOrderFor($user, 90000, PaymentStatusEnum::UNPAID);

        $component = Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id);

        // Revenue only counts paid orders (100000 + 200000 = 300000).
        $component->assertSee('مجموع خرید')
            ->assertSee('کل سفارشات')
            ->assertSee('سفارشات پرداخت‌شده')
            ->assertSee('300,000')
            ->assertSee('100,000')
            ->assertSee('200,000')
            ->assertSee('90,000');
    }

    public function test_no_role_edit_controls_are_rendered(): void
    {
        $user = $this->createCustomerUser();
        $this->createOrderFor($user, 100000, PaymentStatusEnum::PAID);

        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', $user->id)
            ->assertDontSee('تغییر نقش')
            ->assertDontSee('role')
            ->assertDontSee('blocked');
    }

    public function test_viewing_missing_user_flashes_error(): void
    {
        Livewire::actingAs($this->admin())
            ->test(UserManager::class)
            ->call('viewUser', 9999)
            ->assertSet('selectedUserId', null)
            ->assertSee('کاربر یافت نشد');
    }

    public function test_customer_and_guest_cannot_mount_user_manager(): void
    {
        Livewire::test(UserManager::class)->assertStatus(403);

        Livewire::actingAs($this->createCustomerUser())
            ->test(UserManager::class)
            ->assertStatus(403);
    }
}
