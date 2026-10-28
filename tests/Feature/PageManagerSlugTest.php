<?php

namespace Tests\Feature;

use App\Enums\MenuItemTypeEnum;
use App\Livewire\Admin\PageManager;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageManagerSlugTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_creating_a_page_with_slug_contact_us_saves_that_exact_slug(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'تماس با ما')
            ->set('slug', 'contact_us')
            ->set('pageType', 'general')
            ->set('content', '<p>اطلاعات تماس</p>')
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::query()->where('slug', 'contact_us')->first();
        $this->assertNotNull($page);
        $this->assertSame('contact_us', $page->slug);
        $this->assertSame('تماس با ما', $page->title);
    }

    public function test_creating_a_page_with_an_empty_slug_generates_a_usable_slug_from_the_title(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'About Us')
            ->set('slug', '')
            ->set('pageType', 'general')
            ->set('content', '<p>درباره شرکت ما</p>')
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::query()->where('title', 'About Us')->first();
        $this->assertNotNull($page);
        $this->assertSame('about-us', $page->slug);
    }

    public function test_creating_a_page_with_persian_title_and_empty_slug_generates_usable_slug(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'شرایط و قوانین')
            ->set('slug', '')
            ->set('pageType', 'general')
            ->set('content', '<p>متن قوانین</p>')
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::query()->where('title', 'شرایط و قوانین')->first();
        $this->assertNotNull($page);
        $this->assertNotEmpty($page->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $page->slug);
    }

    public function test_updating_a_pages_title_without_changing_its_slug_preserves_the_old_slug(): void
    {
        $page = Page::create([
            'title' => 'عنوان قدیمی',
            'slug' => 'preserved_slug',
            'page_type' => 'general',
            'content' => 'محتوا',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->call('edit', $page->id)
            ->assertSet('slug', 'preserved_slug')
            ->set('title', 'عنوان جدید و متفاوت')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame('preserved_slug', $page->slug);
        $this->assertSame('عنوان جدید و متفاوت', $page->title);
        $this->assertDatabaseMissing('page_slug_histories', [
            'slug' => 'preserved_slug',
        ]);
    }

    public function test_updating_a_page_with_a_new_valid_slug_works_and_records_history(): void
    {
        $page = Page::create([
            'title' => 'تماس با ما',
            'slug' => 'contact-old',
            'page_type' => 'general',
            'content' => 'محتوای تماس',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->call('edit', $page->id)
            ->set('slug', 'contact_new_2026')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame('contact_new_2026', $page->slug);

        $this->assertDatabaseHas('page_slug_histories', [
            'slug' => 'contact-old',
            'page_id' => $page->id,
        ]);
    }

    public function test_duplicate_slug_is_rejected_with_validation_error(): void
    {
        Page::create([
            'title' => 'صفحه یک',
            'slug' => 'existing_slug',
            'page_type' => 'general',
            'content' => 'محتوای صفحه یک',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'صفحه دو')
            ->set('slug', 'existing_slug')
            ->set('pageType', 'general')
            ->set('content', 'محتوای صفحه دو')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, Page::query()->where('slug', 'existing_slug')->count());
    }

    public function test_reserved_slug_in_history_is_rejected_with_validation_error(): void
    {
        $otherPage = Page::create([
            'title' => 'صفحه دیگر',
            'slug' => 'other-active-slug',
            'page_type' => 'general',
            'content' => 'محتوا',
            'is_active' => true,
        ]);

        PageSlugHistory::create([
            'slug' => 'historical-reserved-slug',
            'page_id' => $otherPage->id,
        ]);

        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'صفحه جدید')
            ->set('slug', 'historical-reserved-slug')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_invalid_slug_characters_are_rejected(): void
    {
        // Slugs with spaces
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'صفحه تست')
            ->set('slug', 'contact us')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Slugs with Persian characters
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'صفحه تست')
            ->set('slug', 'تماس-با-ما')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Slugs with special symbols like @ or !
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'صفحه تست')
            ->set('slug', 'contact@us!')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_slug_normalizes_uppercase_and_whitespace(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'تست نرمال‌سازی')
            ->set('slug', '  Contact_Us_2026  ')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::query()->where('title', 'تست نرمال‌سازی')->first();
        $this->assertNotNull($page);
        $this->assertSame('contact_us_2026', $page->slug);
    }

    public function test_empty_slug_is_rejected_when_no_valid_fallback_can_be_generated(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', '???')
            ->set('slug', '')
            ->set('pageType', 'general')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    public function test_livewire_automatically_suggests_slug_from_title_when_empty(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('slug', '')
            ->set('title', 'Privacy Policy')
            ->assertSet('slug', 'privacy-policy');
    }

    public function test_livewire_does_not_override_manually_entered_slug_on_title_change(): void
    {
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->set('title', 'Initial Title')
            ->set('slug', 'custom_manual_slug')
            ->set('title', 'Changed Title')
            ->assertSet('slug', 'custom_manual_slug');
    }

    public function test_existing_page_public_route_and_historical_redirect_continue_to_work(): void
    {
        $page = Page::create([
            'title' => 'صفحه راهنما',
            'slug' => 'guide_page',
            'page_type' => 'general',
            'content' => 'محتوای راهنمای کامل',
            'is_active' => true,
        ]);

        // Public page URL works
        $response = $this->get(route('pages.show', 'guide_page'));
        $response->assertOk();
        $response->assertSee('صفحه راهنما');

        // Update slug
        Livewire::actingAs($this->admin)
            ->test(PageManager::class)
            ->call('edit', $page->id)
            ->set('slug', 'new_guide_page')
            ->call('save')
            ->assertHasNoErrors();

        // Old URL redirects with 301 to new URL
        $redirectResponse = $this->get(route('pages.show', 'guide_page'));
        $redirectResponse->assertStatus(301);
        $redirectResponse->assertRedirect(route('pages.show', 'new_guide_page'));

        // New URL works
        $newResponse = $this->get(route('pages.show', 'new_guide_page'));
        $newResponse->assertOk();
        $newResponse->assertSee('صفحه راهنما');
    }

    public function test_menu_item_linking_to_page_resolves_correct_url_with_custom_slug(): void
    {
        $page = Page::create([
            'title' => 'تماس با ما',
            'slug' => 'contact_us',
            'page_type' => 'general',
            'content' => 'محتوای تماس',
            'is_active' => true,
        ]);

        $menu = Menu::create([
            'name' => 'منوی اصلی',
            'location' => 'header',
        ]);

        $menuItem = MenuItem::create([
            'menu_id' => $menu->id,
            'title' => 'تماس با ما',
            'item_type' => MenuItemTypeEnum::PAGE,
            'target_id' => $page->id,
            'order' => 1,
            'is_active' => true,
        ]);

        $resolvedUrl = $menuItem->resolveUrl();
        $this->assertSame(route('pages.show', 'contact_us'), $resolvedUrl);
        $this->assertStringEndsWith('/pages/contact_us', $resolvedUrl);
    }
}
