<?php

namespace Tests\Feature\Auth;

use App\Enums\ProductTypeEnum;
use App\Livewire\Auth\Login;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_admin_can_log_in(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('phone', $admin->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_inactive_admin_cannot_log_in(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => false,
        ]);

        Livewire::test(Login::class)
            ->set('phone', $admin->phone)
            ->set('password', 'password')
            ->call('login')
            ->assertSee('شماره تلفن یا رمز عبور صحیح نیست.');

        $this->assertGuest();
    }

    public function test_customer_cannot_log_in(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        Livewire::test(Login::class)
            ->set('phone', $customer->phone)
            ->set('password', 'password')
            ->call('login')
            ->assertSee('شماره تلفن یا رمز عبور صحیح نیست.');

        $this->assertGuest();
    }

    public function test_guest_checkout_remains_unaffected(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول تجاری تست وضعیت',
            'slug' => 'customer-status-commerce-'.Str::random(6),
            'base_price' => 100000,
            'is_active' => true,
        ]);

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $token = 'SUBG'.Str::random(36);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), [
                'customer_name' => 'مشتری تست',
                'customer_phone' => '09123456789',
                'shipping_address' => 'تهران، خیابان آزادی',
                'shipping_postal_code' => '1234567890',
                'shipping_plaque' => null,
                'shipping_description' => null,
                'notes' => null,
                'submission_token' => $token,
            ])
            ->assertRedirect(route('checkout.payment', ['order' => Order::query()->latest('id')->value('token')]));

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('مشتری تست', $order->customer_name);
    }
}
