<?php

namespace Tests\Feature;

use App\Livewire\Admin\PageManager;
use App\Models\Page;
use App\Models\PageSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PageSlugHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createViaManager(string $title, bool $active = true): Page
    {
        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', $title)
            ->set('content', 'محتوا')
            ->set('isActive', $active)
            ->call('save')
            ->assertHasNoErrors();

        return Page::query()->latest('id')->firstOrFail();
    }

    private function renameViaManager(Page $page, string $newTitle): void
    {
        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('editingId', $page->id)
            ->set('pageType', $page->page_type)
            ->set('title', $newTitle)
            ->set('content', $page->content)
            ->set('isActive', $page->is_active)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_rename_records_old_slug_and_301s_to_new_slug(): void
    {
        $page = $this->createViaManager('About Us');
        $oldSlug = $page->slug;

        $this->renameViaManager($page, 'Our Story');

        $fresh = $page->fresh();

        $this->assertNotSame($oldSlug, $fresh->slug);
        $this->assertSame('our-story', $fresh->slug);

        $this->assertDatabaseHas('page_slug_histories', [
            'slug' => $oldSlug,
            'page_id' => $page->id,
        ]);

        $this->get(route('pages.show', $oldSlug))
            ->assertStatus(301)
            ->assertRedirect(route('pages.show', $fresh->slug));

        $this->get(route('pages.show', $fresh->slug))
            ->assertOk()
            ->assertSee('Our Story');
    }

    public function test_saving_without_change_keeps_slug_and_writes_no_history(): void
    {
        $page = $this->createViaManager('Stable Page');

        $this->renameViaManager($page, 'Stable Page');

        $this->assertSame('stable-page', $page->fresh()->slug);
        $this->assertSame(0, PageSlugHistory::count());
    }

    public function test_historical_slug_cannot_be_claimed_by_another_page(): void
    {
        $a = $this->createViaManager('Foo Page');
        $oldSlug = $a->slug;

        $this->renameViaManager($a, 'Bar Page');
        $a->refresh();

        $b = $this->createViaManager('Foo Page');

        $this->assertNotSame($a->id, $b->id);
        $this->assertStringStartsWith($oldSlug, $b->slug);
        $this->assertNotSame($oldSlug, $b->slug, 'The historical slug must stay reserved.');
    }

    public function test_deleted_page_slug_returns_404_and_stays_reserved(): void
    {
        $a = $this->createViaManager('Foo Page');
        $oldSlug = $a->slug;

        $this->renameViaManager($a, 'Bar Page');
        $a->refresh();

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->call('delete', $a->id);

        $this->assertDatabaseMissing('pages', ['id' => $a->id]);

        $this->get(route('pages.show', $oldSlug))->assertNotFound();
        $this->get(route('pages.show', $a->slug))->assertNotFound();

        $this->assertDatabaseHas('page_slug_histories', ['slug' => $oldSlug, 'page_id' => null]);
        $this->assertDatabaseHas('page_slug_histories', ['slug' => $a->slug, 'page_id' => null]);

        $b = $this->createViaManager('Foo Page');
        $this->assertNotSame($oldSlug, $b->slug, 'A deleted page\'s slug is never released for reuse.');
    }

    public function test_unknown_slug_returns_404_without_a_redirect(): void
    {
        $response = $this->get(route('pages.show', 'definitely-not-a-slug'));

        $response->assertNotFound();
        $this->assertFalse($response->headers->has('Location'));
    }

    public function test_deactivated_page_historical_slug_returns_404(): void
    {
        $a = $this->createViaManager('Active Page');
        $oldSlug = $a->slug;

        $this->renameViaManager($a, 'Renamed Page');
        $a->refresh();

        Page::where('id', $a->id)->update(['is_active' => false]);

        $this->get(route('pages.show', $oldSlug))->assertNotFound();

        $this->assertFalse($this->get(route('pages.show', $oldSlug))->headers->has('Location'));
    }
}
