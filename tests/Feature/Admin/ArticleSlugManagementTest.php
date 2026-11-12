<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatusEnum;
use App\Livewire\Admin\ArticleManager;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleSlugManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCategory(string $name = 'اخبار و مقالات'): ArticleCategory
    {
        return ArticleCategory::create([
            'name' => $name,
            'slug' => 'news-and-articles-' . uniqid(),
        ]);
    }

    /**
     * 1. Admin can create an article with a manually supplied English slug.
     */
    public function test_admin_can_create_article_with_manually_entered_slug(): void
    {
        $category = $this->createCategory();

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'راهنمای جامع کارت بانکی فلزی')
            ->set('slug', 'ultimate-metal-bank-card-guide')
            ->set('articleCategoryId', $category->id)
            ->set('content', 'محتوای مقاله جامع در مورد کارت بانکی فلزی')
            ->set('status', ArticleStatusEnum::PUBLISHED->value)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->where('slug', 'ultimate-metal-bank-card-guide')->first();
        $this->assertNotNull($article);
        $this->assertSame('راهنمای جامع کارت بانکی فلزی', $article->title);
        $this->assertSame('ultimate-metal-bank-card-guide', $article->slug);
    }

    /**
     * 2. Creating an article with an empty slug generates an automatic fallback slug.
     */
    public function test_create_with_empty_slug_uses_automatic_fallback(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'آموزش استفاده از کارت سوخت هوشمند')
            ->set('slug', '')
            ->set('content', 'محتوای آموزشی کارت سوخت')
            ->set('status', ArticleStatusEnum::PUBLISHED->value)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', 'آموزش استفاده از کارت سوخت هوشمند')->first();
        $this->assertNotNull($article);
        $this->assertNotEmpty($article->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $article->slug);
    }

    /**
     * 3. Persian title must not overwrite a manually supplied English slug.
     */
    public function test_persian_title_does_not_overwrite_manual_english_slug(): void
    {
        $persianTitle = 'معرفی طرح‌های برتر کارت بانکی و سوخت';
        $transliterated = Str::slug($persianTitle);
        $chosenSlug = 'featured-card-designs-showcase';

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', $persianTitle)
            ->set('slug', $chosenSlug)
            ->set('content', 'محتوای طرح‌های برتر')
            ->set('status', ArticleStatusEnum::PUBLISHED->value)
            ->call('save')
            ->assertHasNoErrors();

        $article = Article::query()->where('title', $persianTitle)->firstOrFail();
        $this->assertSame($chosenSlug, $article->slug);
        $this->assertNotSame($transliterated, $article->slug);
    }

    /**
     * 4. Editing article title preserves the existing slug.
     */
    public function test_editing_title_preserves_slug(): void
    {
        $article = Article::create([
            'title' => 'عنوان قدیمی مقاله',
            'slug' => 'preserved-article-slug',
            'content' => 'محتوای تستی',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->assertSet('slug', 'preserved-article-slug')
            ->set('title', 'عنوان جدید و بهینه‌شده برای سئو')
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $this->assertSame('عنوان جدید و بهینه‌شده برای سئو', $article->title);
        $this->assertSame('preserved-article-slug', $article->slug);
    }

    /**
     * 5. Editing unrelated fields preserves the existing slug.
     */
    public function test_editing_unrelated_fields_preserves_slug(): void
    {
        $category = $this->createCategory();
        $article = Article::create([
            'title' => 'مقاله تست فیلدهای متفرقه',
            'slug' => 'unrelated-fields-preserved-slug',
            'content' => 'محتوای اولیه',
            'excerpt' => 'خلاصه اولیه',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('content', 'محتوای کاملاً جدید و طولانی‌تر')
            ->set('excerpt', 'خلاصه به‌روزشده')
            ->set('articleCategoryId', $category->id)
            ->set('metaTitle', 'عنوان متای جدید')
            ->set('status', ArticleStatusEnum::DRAFT->value)
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $this->assertSame('unrelated-fields-preserved-slug', $article->slug);
        $this->assertSame('محتوای کاملاً جدید و طولانی‌تر', $article->content);
        $this->assertSame($category->id, $article->article_category_id);
    }

    /**
     * 6. Intentional slug rename creates ArticleSlugHistory entry and returns 301 redirect.
     */
    public function test_intentional_slug_rename_creates_history_entry_and_301_redirect(): void
    {
        $article = Article::create([
            'title' => 'مقاله تغییر نامک',
            'slug' => 'initial-first-slug',
            'content' => 'محتوا جهت تست ریدایرکت ۳۰۱',
            'status' => ArticleStatusEnum::PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('slug', 'renamed-second-slug')
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $this->assertSame('renamed-second-slug', $article->slug);

        $this->assertDatabaseHas('article_slug_histories', [
            'slug' => 'initial-first-slug',
            'article_id' => $article->id,
        ]);

        // Old slug should 301 redirect to new slug
        $this->get(route('articles.show', 'initial-first-slug'))
            ->assertStatus(301)
            ->assertRedirect(route('articles.show', 'renamed-second-slug'));

        // New slug returns 200 OK
        $this->get(route('articles.show', 'renamed-second-slug'))
            ->assertOk()
            ->assertSee('مقاله تغییر نامک');
    }

    /**
     * 7. Repeated saves without slug changes do not create duplicate history records.
     */
    public function test_repeated_saves_without_slug_changes_do_not_create_duplicate_history(): void
    {
        $article = Article::create([
            'title' => 'مقاله پایدار',
            'slug' => 'stable-slug-no-history',
            'content' => 'محتوای ثابت',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('title', 'عنوان اول')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('content', 'محتوای دوم')
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('status', ArticleStatusEnum::DRAFT->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(0, ArticleSlugHistory::count());
    }

    /**
     * 8. Duplicate current slug is rejected.
     */
    public function test_duplicate_current_slug_is_rejected(): void
    {
        Article::create([
            'title' => 'مقاله اول',
            'slug' => 'active-shared-slug',
            'content' => 'محتوای اول',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله دوم')
            ->set('slug', 'active-shared-slug')
            ->set('content' , 'محتوای دوم')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, Article::where('slug', 'active-shared-slug')->count());
    }

    /**
     * 9. Previously used slug in history cannot be claimed by another article.
     */
    public function test_previously_used_slug_in_history_cannot_be_claimed_by_another_article(): void
    {
        $article = Article::create([
            'title' => 'مقاله اصلی',
            'slug' => 'history-reserved-slug',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('edit', $article->id)
            ->set('slug', 'active-new-slug')
            ->call('save')
            ->assertHasNoErrors();

        // Try to create another article using the old slug
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'مقاله رقیب')
            ->set('slug', 'history-reserved-slug')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    /**
     * 10. Deleted article slug remains reserved forever and cannot be claimed.
     */
    public function test_deleted_article_reserved_slug_cannot_be_claimed(): void
    {
        $article = Article::create([
            'title' => 'مقاله قابل حذف',
            'slug' => 'permanently-dead-slug',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('delete', $article->id);

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        $this->assertDatabaseHas('article_slug_histories', ['slug' => 'permanently-dead-slug', 'article_id' => null]);

        // Attempt to create a new article with the deleted slug
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'تلاش برای ثبت نامک مرده')
            ->set('slug', 'permanently-dead-slug')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    /**
     * 11. Invalid characters in slug are rejected.
     */
    public function test_invalid_characters_in_slug_are_rejected(): void
    {
        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'تست کاراکتر فارسی')
            ->set('slug', 'نامک-فارسی-مقاله')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Spaces and special characters
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'تست کاراکتر نامعتبر')
            ->set('slug', 'invalid article slug with spaces!')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Symbols
        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->set('title', 'تست نماد')
            ->set('slug', 'invalid@article#slug')
            ->set('content', 'محتوا')
            ->call('save')
            ->assertHasErrors(['slug']);
    }
}
