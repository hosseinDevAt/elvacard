<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
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

    public function test_admin_can_access_all_store_pages(): void
    {
        foreach (['admin.products', 'admin.colors', 'admin.designs'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_all_content_pages(): void
    {
        foreach (['admin.pages', 'admin.articles', 'admin.article-categories', 'admin.faq', 'admin.announcements'] as $route) {
            $this->actingAs($this->admin())
                ->get(route($route))
                ->assertStatus(200);
        }
    }

    public function test_admin_can_access_all_appearance_pages(): void
    {
        foreach (['admin.appearance', 'admin.menus', 'admin.menu-items', 'admin.homepage-sections'] as $route) {
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
            ->assertSee('محتوا')
            ->assertSee('ظاهر سایت')
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
            'admin.dashboard', 'admin.products', 'admin.colors', 'admin.designs',
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

    public function test_standalone_workflow_links_are_removed_from_sidebar(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'));

        $content = $response->getContent();

        // These pages are no longer standalone sidebar items.
        foreach (['admin.product-colors', 'admin.cate-designs', 'admin.design-images', 'admin.design-color-compatibilities'] as $route) {
            $this->assertStringNotContainsString(
                'href="'.route($route).'"',
                $content,
                "{$route} should not be a standalone sidebar link."
            );
        }
    }

    public function test_store_group_is_open_on_products_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.products'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('data-open-groups="store"', $content);
    }

    public function test_content_group_is_open_on_pages_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.pages'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('data-open-groups="content"', $content);
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
}
