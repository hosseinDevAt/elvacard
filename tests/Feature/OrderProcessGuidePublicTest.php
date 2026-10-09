<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderProcessGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessGuidePublicTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        OrderProcessGuide::resetTableExistenceCache();
        parent::tearDown();
    }

    public function test_guest_can_access_order_process_page(): void
    {
        $response = $this->get(route('order-process.index'));

        $response->assertOk()
            ->assertSee('راهنمای مراحل سفارش')
            ->assertSee('کارت بانکی فلزی')
            ->assertSee('کارت سوخت');
    }

    public function test_draft_guide_shows_safe_preparation_notice(): void
    {
        $guide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_BANK);
        $guide->update([
            'is_published' => false,
        ]);
        $guide->stages()->create([
            'title' => 'مرحله مخفی پیش‌نویس',
            'description' => 'توضیحات نباید دیده شود',
            'is_active' => true,
        ]);

        $response = $this->get(route('order-process.index', ['workflow' => 'bank_card']));

        $response->assertOk()
            ->assertSee('راهنمای این بخش در حال آماده‌سازی و به‌روزرسانی است')
            ->assertDontSee('مرحله مخفی پیش‌نویس');
    }

    public function test_published_guide_displays_active_stages(): void
    {
        $guide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_BANK);
        $guide->update([
            'title' => 'فرآیند گام‌به‌گام کارت فلزی',
            'description' => 'مقدمه کامل فرآیند تولید',
            'is_published' => true,
        ]);

        $stage1 = $guide->stages()->create([
            'title' => 'مرحله یک: انتخاب طرح و رنگ',
            'description' => 'توضیحات کامل گام اول',
            'duration_hint' => '۵ دقیقه',
            'icon' => 'palette',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $stage2 = $guide->stages()->create([
            'title' => 'مرحله دو: تولید غیرفعال',
            'description' => 'این مرحله غیرفعال است',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->get(route('order-process.index', ['workflow' => 'bank_card']));

        $response->assertOk()
            ->assertSee('فرآیند گام‌به‌گام کارت فلزی')
            ->assertSee('مقدمه کامل فرآیند تولید')
            ->assertSee('مرحله یک: انتخاب طرح و رنگ')
            ->assertSee('توضیحات کامل گام اول')
            ->assertSee('۵ دقیقه')
            ->assertDontSee('مرحله دو: تولید غیرفعال');
    }

    public function test_public_guide_links_to_bank_and_fuel_designers(): void
    {
        $response = $this->get(route('order-process.index'));

        $response->assertOk()
            ->assertSee(route('custom-card.bank'))
            ->assertSee(route('custom-card.fuel'));
    }

    public function test_homepage_includes_order_process_preview_section(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('مراحل ساخت و تحویل کارت شما چگونه است؟')
            ->assertSee('aria-label="راهنمای مراحل سفارش"', false)
            ->assertSee(route('order-process.index'));
    }

    public function test_footer_contains_order_process_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee(route('order-process.index'))
            ->assertSee('مراحل سفارش');
    }

    public function test_homepage_and_public_guide_do_not_throw_500_when_tables_do_not_exist(): void
    {
        OrderProcessGuide::$tablesExistOverride = false;

        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk()
            ->assertDontSee('مراحل ساخت و تحویل کارت شما چگونه است؟');

        $guideResponse = $this->get(route('order-process.index'));
        $guideResponse->assertOk()
            ->assertSee('راهنمای این بخش در حال آماده‌سازی و به‌روزرسانی است');

        OrderProcessGuide::resetTableExistenceCache();
    }

    public function test_public_guide_does_not_mutate_orders_table(): void
    {
        $this->assertEquals(0, Order::count());

        $this->get(route('order-process.index'))->assertOk();
        $this->get(route('order-process.index', ['workflow' => 'fuel_card']))->assertOk();

        $this->assertEquals(0, Order::count());
    }
}
