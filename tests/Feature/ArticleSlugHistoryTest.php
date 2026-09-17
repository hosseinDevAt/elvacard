<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Livewire\Admin\ArticleManager;
use App\Models\Article;
use App\Models\ArticleSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleSlugHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createViaManager(string $title, string $status = ArticleStatusEnum::PUBLISHED->value): Article
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', $title)
            ->set('content', 'محتوا')
            ->set('status', $status)
            ->call('save')
            ->assertHasNoErrors();

        return Article::query()->latest('id')->firstOrFail();
    }

    private function renameViaManager(Article $article, string $newTitle, string $status = ArticleStatusEnum::PUBLISHED->value): void
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('editingId', $article->id)
            ->set('title', $newTitle)
            ->set('content', $article->content)
            ->set('status', $status)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_rename_records_old_slug_and_301s_to_new_slug(): void
    {
        $article = $this->createViaManager('Sample Post');
        $oldSlug = $article->slug;

        $this->renameViaManager($article, 'Updated Sample Post');

        $fresh = $article->fresh();

        $this->assertNotSame($oldSlug, $fresh->slug);
        $this->assertSame('updated-sample-post', $fresh->slug);

        $this->assertDatabaseHas('article_slug_histories', [
            'slug' => $oldSlug,
            'article_id' => $article->id,
        ]);

        $this->get(route('articles.show', $oldSlug))
            ->assertStatus(301)
            ->assertRedirect(route('articles.show', $fresh->slug));

        $this->get(route('articles.show', $fresh->slug))
            ->assertOk()
            ->assertSee('Updated Sample Post');
    }

    public function test_saving_without_change_keeps_slug_and_writes_no_history(): void
    {
        $article = $this->createViaManager('Stable Post');

        $this->renameViaManager($article, 'Stable Post');

        $this->assertSame('stable-post', $article->fresh()->slug);
        $this->assertSame(0, ArticleSlugHistory::count());
    }

    public function test_historical_slug_cannot_be_claimed_by_another_article(): void
    {
        $a = $this->createViaManager('Foo Post');
        $oldSlug = $a->slug;

        $this->renameViaManager($a, 'Bar Post');
        $a->refresh();

        $b = $this->createViaManager('Foo Post');

        $this->assertNotSame($a->id, $b->id);
        $this->assertNotSame($oldSlug, $b->slug, 'The historical slug must stay reserved.');
    }

    public function test_deleted_article_historical_slug_returns_404_and_stays_reserved(): void
    {
        $a = $this->createViaManager('Foo Post');
        $oldSlug = $a->slug;

        $this->renameViaManager($a, 'Bar Post');
        $a->refresh();

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('delete', $a->id);

        $this->assertDatabaseMissing('articles', ['id' => $a->id]);

        $this->get(route('articles.show', $oldSlug))->assertNotFound();
        $this->get(route('articles.show', $a->slug))->assertNotFound();

        $this->assertDatabaseHas('article_slug_histories', ['slug' => $oldSlug, 'article_id' => null]);
        $this->assertDatabaseHas('article_slug_histories', ['slug' => $a->slug, 'article_id' => null]);

        $b = $this->createViaManager('Foo Post');
        $this->assertNotSame($oldSlug, $b->slug, 'A deleted article\'s slug is never released for reuse.');
    }

    public function test_draft_article_historical_slug_never_redirects(): void
    {
        $article = $this->createViaManager('Public Post');
        $oldSlug = $article->slug;

        $this->renameViaManager($article, 'Renamed Post', ArticleStatusEnum::DRAFT->value);
        $article->refresh();

        $this->assertDatabaseHas('article_slug_histories', [
            'slug' => $oldSlug,
            'article_id' => $article->id,
        ]);

        $response = $this->get(route('articles.show', $oldSlug));

        // The publication gate must not be bypassed by slug history: a draft
        // article keeps every URL dead, including historical ones.
        $response->assertNotFound();
        $this->assertFalse($response->headers->has('Location'));
    }

    public function test_future_scheduled_article_historical_slug_never_redirects(): void
    {
        $article = $this->createViaManager('Scheduled Post');
        $oldSlug = $article->slug;

        $this->renameViaManager($article, 'Scheduled Post v2');
        $article->refresh();

        $this->assertDatabaseHas('article_slug_histories', [
            'slug' => $oldSlug,
            'article_id' => $article->id,
        ]);

        // Schedule the renamed article in the future: the publication gate must
        // keep BOTH the fresh and the historical URL dead until then.
        Article::where('id', $article->id)->update([
            'published_at' => now()->addDays(3),
        ]);

        $response = $this->get(route('articles.show', $oldSlug));

        $response->assertNotFound();
        $this->assertFalse($response->headers->has('Location'));
    }

    public function test_unknown_slug_returns_404_without_a_redirect(): void
    {
        $response = $this->get(route('articles.show', 'definitely-not-a-slug'));

        $response->assertNotFound();
        $this->assertFalse($response->headers->has('Location'));
    }
}
