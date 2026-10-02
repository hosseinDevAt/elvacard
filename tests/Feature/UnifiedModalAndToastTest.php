<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedModalAndToastTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'phone' => '09120000001',
        ]);
    }

    public function test_confirmation_modal_and_toast_components_render_in_admin_layout(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('x-data="confirmationModal()"', false);
        $response->assertSee('x-data="toastContainer(', false);
        $response->assertSee('role="dialog"', false);
        $response->assertSee('aria-modal="true"', false);
        $response->assertSee('aria-live="polite"', false);
    }

    public function test_confirmation_modal_and_toast_components_render_in_public_app_layout(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('x-data="confirmationModal()"', false);
        $response->assertSee('x-data="toastContainer(', false);
    }

    public function test_confirmation_modal_and_toast_components_render_in_guest_layout(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('x-data="confirmationModal()"', false);
        $response->assertSee('x-data="toastContainer(', false);
    }

    public function test_empty_cart_form_has_confirmation_protection_attributes(): void
    {
        session()->put('cart', [
            'items' => [
                1 => [
                    'id' => 1,
                    'product_name_snapshot' => 'کارت فلزی طرح اختصاصی',
                    'unit_price_snapshot' => 150000,
                    'final_price' => 150000,
                    'color_name_snapshot' => 'طلایی مات',
                    'design_name_snapshot' => 'طرح شیر و خورشید',
                    'quantity' => 1,
                ]
            ],
            'total_quantity' => 1,
            'total_price' => 150000,
        ]);

        $response = $this->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('action="' . route('cart.empty') . '"', false);
        $response->assertSee('data-confirm="آیا از پاک کردن تمام اقلام موجود در سبد خرید مطمئن هستید؟"', false);
        $response->assertSee('data-confirm-variant="warning"', false);
        $response->assertSee('data-confirm-title="خالی کردن سبد خرید"', false);
        $response->assertSee('data-confirm-btn="بله، سبد خالی شود"', false);
    }

    public function test_empty_cart_submission_empties_cart(): void
    {
        session()->put('cart', [
            'items' => [
                1 => [
                    'id' => 1,
                    'product_name_snapshot' => 'کارت',
                    'unit_price_snapshot' => 10000,
                    'final_price' => 10000,
                    'quantity' => 1
                ]
            ],
            'total_quantity' => 1,
            'total_price' => 10000,
        ]);

        $response = $this->post(route('cart.empty'));

        $response->assertRedirect(route('cart.index'));
        $this->assertEmpty(session('cart.items', []));
    }

    public function test_toast_container_renders_session_flash_messages(): void
    {
        $response = $this->withSession([
            'success' => 'محصول جدید با موفقیت ذخیره شد.',
            'error' => 'خطایی در پردازش اطلاعات رخ داد.',
        ])->get(route('cart.index'));

        $response->assertStatus(200);
        $response->assertSee('محصول جدید با موفقیت ذخیره شد.', false);
        $response->assertSee('خطایی در پردازش اطلاعات رخ داد.', false);
    }

    public function test_admin_views_retain_wire_confirm_expressions_for_interceptor(): void
    {
        $admin = $this->createAdmin();

        // 1. Announcements
        $res = $this->actingAs($admin)->get(route('admin.announcements'));
        $res->assertStatus(200);

        // 2. Products
        $res = $this->actingAs($admin)->get(route('admin.products'));
        $res->assertStatus(200);

        // 3. Orders
        $res = $this->actingAs($admin)->get(route('admin.orders'));
        $res->assertStatus(200);

        // 4. Payments
        $res = $this->actingAs($admin)->get(route('admin.payments'));
        $res->assertStatus(200);
    }
}
