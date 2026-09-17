<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BlockedGuestCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function createCustomer(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'customer'], $overrides));
    }

    private function addCommerceProductToCart(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول تست مهمان',
            'slug' => 'guest-checkout-'.Str::random(6),
            'base_price' => 100000,
            'is_active' => true,
        ]);

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    private function checkoutPayload(string $phone, array $overrides = []): array
    {
        $token = 'SUB'.Str::random(37);

        return array_merge([
            'customer_name' => 'مشتری مهمان',
            'customer_phone' => $phone,
            'shipping_address' => 'تهران، خیابان آزادی',
            'shipping_postal_code' => '1234567890',
            'shipping_plaque' => null,
            'shipping_description' => null,
            'notes' => null,
            'submission_token' => $token,
        ], $overrides);
    }

    public function test_inactive_registered_phone_cannot_checkout_as_pure_guest(): void
    {
        $this->createCustomer([
            'is_active' => false,
            'phone' => '09123332222',
        ]);

        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload('09123332222', ['submission_token' => $token]))
            ->assertSessionHasErrors(['cart' => 'امکان ثبت سفارش با این شماره وجود ندارد.']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_inactive_registered_phone_with_plus98_is_rejected(): void
    {
        $this->createCustomer([
            'is_active' => false,
            'phone' => '09123332222',
        ]);

        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload('+989123332222', ['submission_token' => $token]))
            ->assertSessionHasErrors(['cart' => 'امکان ثبت سفارش با این شماره وجود ندارد.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public static function blockedPhoneFormatsProvider(): array
    {
        return [
            '0098 international prefix' => ['00989123332222'],
            'Persian digits' => ['۰۹۱۲۳۳۳۲۲۲۲'],
            'Arabic digits' => ['٠٩١٢٣٣٣٢٢٢٢'],
            'dashed separators' => ['0912-333-2222'],
            'spaced separators' => ['0912 333 2222'],
        ];
    }

    #[DataProvider('blockedPhoneFormatsProvider')]
    public function test_inactive_registered_phone_alternate_formats_are_rejected(string $phone): void
    {
        $this->createCustomer([
            'is_active' => false,
            'phone' => '09123332222',
        ]);

        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload($phone, ['submission_token' => $token]))
            ->assertSessionHasErrors(['cart' => 'امکان ثبت سفارش با این شماره وجود ندارد.']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_active_registered_phone_can_still_checkout_as_guest(): void
    {
        $this->createCustomer([
            'phone' => '09123337777',
        ]);

        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload('09123337777', ['submission_token' => $token]))
            ->assertRedirect(route('checkout.payment', ['order' => Order::query()->latest('id')->value('token')]));

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('09123337777', $order->customer_phone);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unregistered_guest_phone_can_checkout(): void
    {
        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload('09124445555', ['submission_token' => $token]))
            ->assertRedirect(route('checkout.payment', ['order' => Order::query()->latest('id')->value('token')]));

        $order = Order::query()->latest('id')->first();

        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertSame('09124445555', $order->customer_phone);
    }

    public function test_blocked_guest_checkout_response_does_not_enumerate_account(): void
    {
        $user = $this->createCustomer([
            'is_active' => false,
            'phone' => '09123338888',
            'blocked_reason' => 'نقض قوانین',
        ]);

        $this->addCommerceProductToCart();

        $token = 'SUB'.Str::random(37);

        $response = $this->withSession(['checkout_submission_token' => $token])
            ->post(route('checkout.store'), $this->checkoutPayload('09123338888', ['submission_token' => $token]));

        $response->assertSessionHasErrors(['cart' => 'امکان ثبت سفارش با این شماره وجود ندارد.']);

        $errors = $response->getSession()->get('errors');

        $this->assertInstanceOf(ViewErrorBag::class, $errors);

        $messages = $errors->getBag('default')->getMessages();
        $this->assertSame(['cart'], array_keys($messages));

        $cartErrors = array_values((array) $messages['cart']);
        $this->assertSame(['امکان ثبت سفارش با این شماره وجود ندارد.'], $cartErrors);

        $message = (string) ($cartErrors[0] ?? '');
        $this->assertStringNotContainsString('is_active', $message);
        $this->assertStringNotContainsString((string) $user->id, $message);
        $this->assertStringNotContainsString('نقض قوانین', $message);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
    }
}
