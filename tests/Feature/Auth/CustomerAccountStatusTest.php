<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpPurpose;
use App\Enums\ProductTypeEnum;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Support\Traits\UsesRecordingSms;
use Tests\TestCase;

class CustomerAccountStatusTest extends TestCase
{
    use RefreshDatabase;
    use UsesRecordingSms;

    private function createCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    public function test_active_customer_can_log_in(): void
    {
        $user = $this->createCustomer();

        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_customer_cannot_log_in(): void
    {
        $user = $this->createCustomer(['is_active' => false]);

        Livewire::test(Login::class)
            ->set('phone', $user->phone)
            ->set('password', 'password')
            ->call('login')
            ->assertSee('شماره تلفن یا رمز عبور صحیح نیست.');

        $this->assertGuest();
    }

    public function test_inactive_customer_login_response_is_not_enumerating(): void
    {
        $inactive = $this->createCustomer(['is_active' => false, 'phone' => '09123331111']);
        $wrongPassword = $this->createCustomer(['phone' => '09123332222']);

        $inactiveHtml = Livewire::test(Login::class)
            ->set('phone', $inactive->phone)
            ->set('password', 'password')
            ->call('login')
            ->html();

        $wrongPasswordHtml = Livewire::test(Login::class)
            ->set('phone', $wrongPassword->phone)
            ->set('password', 'definitely-wrong-password')
            ->call('login')
            ->html();

        $this->assertGuest();

        $this->assertStringContainsString('شماره تلفن یا رمز عبور صحیح نیست.', $inactiveHtml);
        $this->assertStringContainsString('شماره تلفن یا رمز عبور صحیح نیست.', $wrongPasswordHtml);
        $this->assertStringNotContainsString('مسدود', $inactiveHtml);
        $this->assertStringNotContainsString('فعال', $inactiveHtml);
    }

    public function test_inactive_customer_cannot_obtain_a_password_reset_otp(): void
    {
        $recording = $this->useRecordingSms();

        $user = $this->createCustomer(['is_active' => false]);

        Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertNull($recording->lastCode());
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_inactive_customer_cannot_complete_a_password_reset(): void
    {
        $recording = $this->useRecordingSms();

        $user = $this->createCustomer(['is_active' => false]);
        $oldHash = $user->getAuthPassword();

        // Even if a stale reset code is somehow held, the reset must still be
        // refused server-side for an inactive customer.
        app(OtpService::class)->issue($user->phone, OtpPurpose::PASSWORD_RESET);

        $component = Livewire::test(ForgotPassword::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertSet('step', 2);

        $component
            ->set('code', $recording->lastCode())
            ->call('verifyCode')
            ->assertSet('step', 3);

        $component
            ->set('password', 'new-password')
            ->set('passwordConfirmation', 'new-password')
            ->call('completeReset')
            ->assertHasErrors('phone');

        $this->assertGuest();
        $this->assertSame($oldHash, $user->refresh()->getAuthPassword());
    }

    public function test_inactive_existing_phone_cannot_be_registered_again(): void
    {
        $recording = $this->useRecordingSms();

        $user = $this->createCustomer(['is_active' => false, 'phone' => '09123334444']);

        Livewire::test(Register::class)
            ->set('phone', $user->phone)
            ->call('requestOtp')
            ->assertHasErrors('phone');

        $this->assertNull($recording->lastCode());
        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_register_still_allows_a_new_phone(): void
    {
        $recording = $this->useRecordingSms();

        Livewire::test(Register::class)
            ->set('phone', '09123335555')
            ->call('requestOtp')
            ->assertSet('step', 2);

        $this->assertNotNull($recording->lastCode());
        $this->assertDatabaseCount('otp_codes', 1);
    }

    public function test_inactive_customer_session_is_terminated_on_next_request(): void
    {
        $user = $this->createCustomer(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('account.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_customer_cannot_submit_checkout(): void
    {
        $user = $this->createCustomer(['is_active' => false, 'phone' => '09123336666']);
        $this->addCommerceProductToCart();

        $token = 'SUBG'.Str::random(36);

        $this->actingAs($user)
            ->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload([
                'customer_phone' => $user->phone,
                'submission_token' => $token,
            ]))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_remains_unaffected(): void
    {
        $this->addCommerceProductToCart();

        $token = 'SUBG'.Str::random(36);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload([
                'submission_token' => $token,
            ]))
            ->assertRedirect(route('checkout.payment', ['order' => Order::query()->latest('id')->value('token')]));

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('مشتری تست', $order->customer_name);
    }

    public function test_admin_login_is_not_affected_by_customer_status(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'phone' => '09123337777',
        ]);

        Livewire::test(Login::class)
            ->set('phone', $admin->phone)
            ->set('password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard'))->assertStatus(200);
        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    private function addCommerceProductToCart(): void
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
    }

    private function checkoutPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، خیابان آزادی',
            'shipping_postal_code' => '1234567890',
            'shipping_plaque' => null,
            'shipping_description' => null,
            'notes' => null,
        ], $overrides);
    }
}
