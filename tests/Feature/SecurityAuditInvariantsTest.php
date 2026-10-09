<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityAuditInvariantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_design_preview_strictly_rejects_path_traversal_payloads(): void
    {
        // 1. Directory traversal tokens must be rejected with 404 by the route regex
        $traversalPayloads = [
            '../../etc/passwd',
            '..\\..\\windows\\win.ini',
            '....//....//config',
            '%2e%2e%2f%2e%2e%2f',
            'token_with_invalid_chars!@#',
            'short', // Less than 40 chars
            str_repeat('a', 65), // Greater than 64 chars
        ];

        foreach ($traversalPayloads as $payload) {
            $response = $this->get('/custom-design/preview/' . urlencode($payload));
            $response->assertNotFound();
        }
    }

    public function test_storage_put_endpoint_rejects_unsigned_anonymous_file_upload(): void
    {
        $response = $this->call('PUT', '/storage/unauthorized_file.php', [], [], [], [], '<?php echo 1;');
        // Must reject unsigned upload (403 or 404 depending on environment)
        $this->assertContains($response->getStatusCode(), [403, 404]);
    }

    public function test_order_payment_page_enforces_user_ownership_when_user_id_is_bound(): void
    {
        $userA = User::factory()->create(['role' => 'customer']);
        $userB = User::factory()->create(['role' => 'customer']);

        $token = Str::random(40);
        $order = new Order();
        $order->token = $token;
        $order->user_id = $userA->id;
        $order->customer_name = 'User A';
        $order->customer_phone = '09121112233';
        $order->shipping_address = 'Tehran';
        $order->shipping_plaque = '1';
        $order->total_price = 500000;
        $order->status = \App\Enums\OrderStatusEnum::PENDING->value;
        $order->payment_status = \App\Enums\PaymentStatusEnum::UNPAID->value;
        $order->save();

        // Acting as User B attempting to view User A's order payment page
        $response = $this->actingAs($userB)->get(route('checkout.payment', $order->token));
        $response->assertForbidden();

        // Acting as User A is allowed
        $responseUserA = $this->actingAs($userA)->get(route('checkout.payment', $order->token));
        $this->assertNotEquals(403, $responseUserA->getStatusCode());
    }

    public function test_order_tracking_endpoint_blocks_brute_force_via_rate_limiting(): void
    {
        // Route is protected by throttle:5,1
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('order-tracking.check'), [
                'reference' => '99999',
                'customer_phone_last4' => '1234',
            ]);
        }

        // 6th request should hit rate limiter (429 Too Many Requests)
        $response = $this->post(route('order-tracking.check'), [
            'reference' => '99999',
            'customer_phone_last4' => '1234',
        ]);

        $response->assertStatus(429);
    }

    public function test_admin_dashboard_rejects_unauthenticated_and_customer_users(): void
    {
        // 1. Unauthenticated guest
        $guestResponse = $this->get(route('admin.dashboard'));
        $guestResponse->assertRedirect(route('login'));

        // 2. Authenticated customer
        $customer = User::factory()->create(['role' => 'customer']);
        $customerResponse = $this->actingAs($customer)->get(route('admin.dashboard'));
        $customerResponse->assertForbidden();
    }

    public function test_security_headers_are_present_on_public_web_responses(): void
    {
        $response = $this->get('/');
        $response->assertOk();

        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_security_headers_are_present_on_authenticated_customer_and_admin_responses(): void
    {
        // 1. Authenticated customer
        $customer = User::factory()->create(['role' => 'customer']);
        $customerResponse = $this->actingAs($customer)->get(route('cart.index'));
        $customerResponse->assertOk();
        $this->assertSame('nosniff', $customerResponse->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $customerResponse->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $customerResponse->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $customerResponse->headers->get('Permissions-Policy'));

        // 2. Authenticated admin
        $admin = User::factory()->create(['role' => 'admin']);
        $adminResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminResponse->assertOk();
        $this->assertSame('nosniff', $adminResponse->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $adminResponse->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $adminResponse->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $adminResponse->headers->get('Permissions-Policy'));
    }

    public function test_security_headers_are_retained_on_redirect_and_json_responses(): void
    {
        // 1. Redirect response (unauthenticated guest accessing admin)
        $redirectResponse = $this->get(route('admin.dashboard'));
        $redirectResponse->assertRedirect(route('login'));
        $this->assertSame('nosniff', $redirectResponse->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $redirectResponse->headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $redirectResponse->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $redirectResponse->headers->get('Permissions-Policy'));

        // 2. JSON response (AJAX/JSON request)
        $jsonResponse = $this->getJson('/');
        $jsonResponse->assertOk();
        $this->assertSame('nosniff', $jsonResponse->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $jsonResponse->headers->get('X-Frame-Options'));
    }
}
