<?php

namespace Tests\Unit;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuCachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Menu::clearAllCache();
    }

    protected function tearDown(): void
    {
        Menu::clearAllCache();
        parent::tearDown();
    }

    public function test_menu_is_cached_and_executes_no_queries_on_subsequent_calls(): void
    {
        $menu = Menu::create(['name' => 'منوی سربرگ', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'آیتم اول',
            'item_type' => 'url',
            'custom_url' => '/first',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'آیتم دوم',
            'item_type' => 'url',
            'custom_url' => '/second',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Menu::clearAllCache();

        // First call populates cache
        $firstResult = Menu::getCachedForLocation('header');
        $this->assertNotNull($firstResult);
        $this->assertCount(2, $firstResult['items']);

        // Subsequent calls within the same request
        DB::flushQueryLog();
        DB::enableQueryLog();

        for ($i = 0; $i < 5; $i++) {
            $cached = Menu::getCachedForLocation('header');
            $this->assertSame($firstResult, $cached);
        }

        $queries = DB::getQueryLog();
        $this->assertCount(0, $queries, 'Subsequent calls in same request must hit in-memory runtime cache');

        // Subsequent call in a new request (runtime cache cleared, persistent cache intact)
        Menu::clearRuntimeCache();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $fromPersistent = Menu::getCachedForLocation('header');
        $this->assertSame($firstResult['name'], $fromPersistent['name']);
        $this->assertCount(2, $fromPersistent['items']);

        $queries = DB::getQueryLog();
        $menuQueries = array_filter($queries, fn ($q) => str_contains($q['query'], '`menus`') || str_contains($q['query'], '`menu_items`'));
        $this->assertCount(0, $menuQueries, 'Persistent cache hit must not query menus or menu_items tables');
    }

    public function test_inactive_items_are_excluded_and_sort_order_is_preserved(): void
    {
        $menu = Menu::create(['name' => 'منوی اصلی', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'آیتم سوم',
            'item_type' => 'url',
            'custom_url' => '/third',
            'sort_order' => 30,
            'is_active' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'آیتم اول',
            'item_type' => 'url',
            'custom_url' => '/first',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'آیتم غیرفعال',
            'item_type' => 'url',
            'custom_url' => '/inactive',
            'sort_order' => 5,
            'is_active' => false,
        ]);

        Menu::clearAllCache();

        $cached = Menu::getCachedForLocation('header');
        $this->assertNotNull($cached);
        $this->assertCount(2, $cached['items']);

        // Check sort order
        $this->assertSame('آیتم اول', $cached['items'][0]['title']);
        $this->assertSame('آیتم سوم', $cached['items'][1]['title']);
    }

    public function test_missing_menu_returns_null(): void
    {
        Menu::clearAllCache();
        $this->assertNull(Menu::getCachedForLocation('non_existent_location'));
    }

    public function test_menu_item_creation_invalidates_cache(): void
    {
        $menu = Menu::create(['name' => 'هدر', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'اولین آیتم',
            'item_type' => 'url',
            'custom_url' => '/first',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $cachedBefore = Menu::getCachedForLocation('header');
        $this->assertCount(1, $cachedBefore['items']);

        // Create second item
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'دومین آیتم',
            'item_type' => 'url',
            'custom_url' => '/second',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $cachedAfter = Menu::getCachedForLocation('header');
        $this->assertCount(2, $cachedAfter['items']);
        $this->assertSame('دومین آیتم', $cachedAfter['items'][1]['title']);
    }

    public function test_menu_item_update_invalidates_cache(): void
    {
        $menu = Menu::create(['name' => 'هدر', 'location' => 'header']);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'عنوان قدیمی',
            'item_type' => 'url',
            'custom_url' => '/old',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertSame('عنوان قدیمی', Menu::getCachedForLocation('header')['items'][0]['title']);

        // Update title
        $item->update(['title' => 'عنوان جدید']);
        $this->assertSame('عنوان جدید', Menu::getCachedForLocation('header')['items'][0]['title']);

        // Deactivate item
        $item->update(['is_active' => false]);
        $this->assertCount(0, Menu::getCachedForLocation('header')['items']);
    }

    public function test_menu_item_moving_to_another_menu_invalidates_both_caches(): void
    {
        $header = Menu::create(['name' => 'هدر', 'location' => 'header']);
        $footer = Menu::create(['name' => 'فوتر', 'location' => 'footer']);

        $item = MenuItem::create([
            'menu_id' => $header->id,
            'title' => 'لینک قابل جابجایی',
            'item_type' => 'url',
            'custom_url' => '/moveable',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertCount(1, Menu::getCachedForLocation('header')['items']);
        $this->assertCount(0, Menu::getCachedForLocation('footer')['items']);

        // Move item to footer
        $item->update(['menu_id' => $footer->id]);

        $this->assertCount(0, Menu::getCachedForLocation('header')['items']);
        $this->assertCount(1, Menu::getCachedForLocation('footer')['items']);
        $this->assertSame('لینک قابل جابجایی', Menu::getCachedForLocation('footer')['items'][0]['title']);
    }

    public function test_menu_item_deletion_invalidates_cache(): void
    {
        $menu = Menu::create(['name' => 'هدر', 'location' => 'header']);
        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'حذف‌شدنی',
            'item_type' => 'url',
            'custom_url' => '/delete-me',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertCount(1, Menu::getCachedForLocation('header')['items']);

        $item->delete();

        $this->assertCount(0, Menu::getCachedForLocation('header')['items']);
    }

    public function test_menu_location_change_invalidates_both_locations(): void
    {
        $menu = Menu::create(['name' => 'منوی متحرک', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'تست',
            'item_type' => 'url',
            'custom_url' => '/test',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertNotNull(Menu::getCachedForLocation('header'));
        $this->assertNull(Menu::getCachedForLocation('footer'));

        // Change menu location from header to footer
        $menu->update(['location' => 'footer']);

        $this->assertNull(Menu::getCachedForLocation('header'));
        $this->assertNotNull(Menu::getCachedForLocation('footer'));
        $this->assertSame('تست', Menu::getCachedForLocation('footer')['items'][0]['title']);
    }

    public function test_menu_deletion_invalidates_cache(): void
    {
        $menu = Menu::create(['name' => 'هدر حذف‌شدنی', 'location' => 'header']);
        MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'تست',
            'item_type' => 'url',
            'custom_url' => '/test',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->assertNotNull(Menu::getCachedForLocation('header'));

        $menu->delete();

        $this->assertNull(Menu::getCachedForLocation('header'));
    }
}
