<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    private const GROUP_ORDER = ['store', 'custom-design', 'basic-data', 'payments', 'content', 'appearance'];

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_can_access_all_store_pages(): void
    {
        foreach (['admin.products', 'admin.product-categories'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_all_custom_design_pages(): void
    {
        foreach (['admin.product-colors', 'admin.designs', 'admin.design-images', 'admin.design-color-compatibilities'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_all_basic_data_pages(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.colors'))
            ->assertStatus(200);
    }

    public function test_admin_can_access_all_content_pages(): void
    {
        foreach (['admin.pages', 'admin.homepage-sections', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements', 'admin.menus', 'admin.menu-items'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_all_appearance_pages(): void
    {
        foreach (['admin.appearance', 'admin.site-settings'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_misc_pages(): void
    {
        foreach (['admin.dashboard', 'admin.orders', 'admin.payments', 'admin.users', 'admin.site-settings', 'admin.manual-payment'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_sidebar_renders_grouped_navigation(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('فروشگاه')
            ->assertSee('شخصی‌سازی کارت')
            ->assertSee('اطلاعات پایه')
            ->assertSee('پرداخت')
            ->assertSee('محتوا')
            ->assertSee('تنظیمات و ظاهر')
            ->assertSee(route('admin.products'))
            ->assertSee(route('admin.designs'))
            ->assertSee(route('admin.orders'))
            ->assertSee(route('admin.users'))
            ->assertSee(route('admin.site-settings'));
    }

    public function test_sidebar_links_are_spa_navigable(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        foreach ([
            'admin.dashboard', 'admin.products', 'admin.product-categories', 'admin.colors', 'admin.designs',
            'admin.product-colors', 'admin.design-images', 'admin.design-color-compatibilities',
            'admin.pages', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements',
            'admin.appearance', 'admin.menus', 'admin.menu-items', 'admin.homepage-sections',
            'admin.orders', 'admin.payments', 'admin.users', 'admin.site-settings', 'admin.manual-payment',
        ] as $route) {
            $this->assertStringContainsString(
                'href="'.route($route).'" wire:navigate',
                $response->getContent(),
                "Sidebar link for {$route} should carry wire:navigate."
            );
        }
    }

    public function test_dashboard_cards_are_spa_navigable(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        foreach (['admin.products', 'admin.colors', 'admin.designs', 'admin.pages', 'admin.orders', 'admin.payments'] as $route) {
            $this->assertStringContainsString(
                'href="'.route($route).'" wire:navigate',
                $response->getContent(),
                "Dashboard card for {$route} should carry wire:navigate."
            );
        }
    }

    public function test_store_group_contains_only_store_management(): void
    {
        $store = $this->groupBlock('store');

        foreach (['admin.products', 'admin.product-categories'] as $route) {
            $this->assertStringContainsString(
                route($route),
                $store,
                "Store group should link {$route}."
            );
        }

        foreach (['admin.colors', 'admin.designs', 'admin.product-colors', 'admin.design-images', 'admin.design-color-compatibilities'] as $route) {
            $this->assertStringNotContainsString(
                route($route),
                $store,
                "Store group should not link {$route}."
            );
        }
    }

    public function test_custom_design_group_contains_design_managers(): void
    {
        $customDesign = $this->groupBlock('custom-design');

        foreach (['admin.product-colors', 'admin.designs', 'admin.design-images', 'admin.design-color-compatibilities'] as $route) {
            $this->assertStringContainsString(
                route($route),
                $customDesign,
                "Custom Design group should link {$route}."
            );
        }
    }

    public function test_basic_data_group_contains_colors(): void
    {
        $basicData = $this->groupBlock('basic-data');

        $this->assertStringContainsString(
            route('admin.colors'),
            $basicData,
            'Basic Data group should link admin.colors.'
        );
    }

    public function test_custom_design_managers_are_reachable_from_sidebar(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        foreach (['admin.product-colors', 'admin.designs', 'admin.design-images', 'admin.design-color-compatibilities'] as $route) {
            $this->assertStringContainsString(
                'href="'.route($route).'" wire:navigate',
                $response->getContent(),
                "{$route} should be reachable through visible Admin navigation."
            );
        }
    }

    public function test_cate_design_manager_is_not_a_sidebar_link(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        $this->assertStringNotContainsString(
            'href="'.route('admin.cate-designs').'"',
            $response->getContent(),
            'admin.cate-designs should not be a sidebar link.'
        );
    }

    public function test_store_group_is_open_on_products_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.products'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('data-open-groups="store"', $content);
    }

    public function test_custom_design_group_is_open_on_product_colors_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.product-colors'));

        $response->assertOk();
        $this->assertStringContainsString('data-open-groups="custom-design"', $response->getContent());
    }

    public function test_basic_data_group_is_open_on_colors_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.colors'));

        $response->assertOk();
        $this->assertStringContainsString('data-open-groups="basic-data"', $response->getContent());
    }

    public function test_content_group_is_open_on_pages_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.pages'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('data-open-groups="content"', $content);
    }

    public function test_payments_group_is_open_on_payments_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.payments'));

        $response->assertOk();
        $this->assertStringContainsString('data-open-groups="payments"', $response->getContent());
    }

    public function test_payments_group_is_open_on_manual_payment_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.manual-payment'));

        $response->assertOk();
        $this->assertStringContainsString('data-open-groups="payments"', $response->getContent());
    }

    public function test_payments_group_contains_payments_and_manual_payment(): void
    {
        $payments = $this->groupBlock('payments');

        foreach (['admin.payments', 'admin.manual-payment'] as $route) {
            $this->assertStringContainsString(
                route($route),
                $payments,
                "Payments group should link {$route}."
            );
        }
    }

    public function test_content_group_contains_homepage_and_menus(): void
    {
        $content = $this->groupBlock('content');

        foreach (['admin.pages', 'admin.homepage-sections', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements', 'admin.menus', 'admin.menu-items'] as $route) {
            $this->assertStringContainsString(
                route($route),
                $content,
                "Content group should link {$route}."
            );
        }

        foreach (['admin.site-settings', 'admin.appearance'] as $route) {
            $this->assertStringNotContainsString(
                route($route),
                $content,
                "Content group should not link {$route}."
            );
        }
    }

    public function test_appearance_group_contains_settings_and_brand(): void
    {
        $appearance = $this->groupBlock('appearance');

        foreach (['admin.site-settings', 'admin.appearance'] as $route) {
            $this->assertStringContainsString(
                route($route),
                $appearance,
                "Appearance group should link {$route}."
            );
        }
    }

    public function test_sidebar_shows_admin_brand_and_profile(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('ElvaCard')
            ->assertSee($admin->name)
            ->assertSee('مدیر')
            ->assertSee('بازگشت به سایت')
            ->assertSee('خروج');
    }

    public function test_product_manager_links_card_products_to_card_pricing(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت میانبر قیمت',
            'slug' => 'pricing-shortcut-bank-'.uniqid(),
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertOk()
            ->assertSee(route('admin.product-colors', ['product' => $product->id]), false);
    }

    public function test_product_manager_does_not_link_store_products_to_card_pricing(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول بدون میانبر',
            'slug' => 'pricing-shortcut-store-'.uniqid(),
            'base_price' => 100000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertOk()
            ->assertDontSee(route('admin.product-colors', ['product' => $product->id]), false);
    }

    public function test_customer_is_denied_admin_group_pages(): void
    {
        foreach (['admin.dashboard', 'admin.products', 'admin.pages'] as $route) {
            $this->actingAs($this->customer())
                ->get(route($route))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_from_admin_group_pages(): void
    {
        $this->get(route('admin.products'))
            ->assertRedirect(route('login'));
    }

    private function groupBlock(string $groupId): string
    {
        $content = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->getContent();

        $navStart = strpos($content, '<nav');
        $navEnd = strpos($content, '</nav>', $navStart);

        $this->assertNotFalse($navStart, 'Sidebar nav should be present.');
        $this->assertNotFalse($navEnd, 'Sidebar nav should be closed.');

        $sidebar = substr($content, $navStart, $navEnd - $navStart);

        $startMarker = 'aria-controls="group-'.$groupId.'"';
        $start = strpos($sidebar, $startMarker);

        $this->assertNotFalse($start, "Sidebar group {$groupId} should exist.");

        $searchFrom = $start + strlen($startMarker);
        $end = strlen($sidebar);

        foreach (self::GROUP_ORDER as $other) {
            if ($other === $groupId) {
                continue;
            }

            $position = strpos($sidebar, 'aria-controls="group-'.$other.'"', $searchFrom);

            if ($position !== false && $position < $end) {
                $end = $position;
            }
        }

        return substr($sidebar, $start, $end - $start);
    }
}
