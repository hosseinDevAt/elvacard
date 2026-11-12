<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatusEnum;
use App\Livewire\Admin\ArticleCategoryManager;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleCategorySlugManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 1. Admin can create category with a manually entered English slug.
     */
    public function test_admin_can_create_category_with_manually_entered_slug(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'فناوری و نوآوری کارت‌ها')
            ->set('slug', 'card-technology-innovation')
            ->call('save')
            ->assertHasNoErrors();

        $category = ArticleCategory::query()->where('slug', 'card-technology-innovation')->first();
        $this->assertNotNull($category);
        $this->assertSame('فناوری و نوآوری کارت‌ها', $category->name);
        $this->assertSame('card-technology-innovation', $category->slug);
    }

    /**
     * 2. Empty slug on creation generates automatic fallback slug.
     */
    public function test_empty_slug_on_creation_uses_automatic_fallback(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'راهنمای امنیتی کارت')
            ->set('slug', '')
            ->call('save')
            ->assertHasNoErrors();

        $category = ArticleCategory::query()->where('name', 'راهنمای امنیتی کارت')->first();
        $this->assertNotNull($category);
        $this->assertNotEmpty($category->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]+$/', $category->slug);
    }

    /**
     * 3. Persian name must not overwrite manual English slug.
     */
    public function test_persian_name_does_not_overwrite_manual_english_slug(): void
    {
        $persianName = 'اخبار و رویدادهای ویژه';
        $transliterated = Str::slug($persianName);
        $chosenSlug = 'special-news-and-events';

        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', $persianName)
            ->set('slug', $chosenSlug)
            ->call('save')
            ->assertHasNoErrors();

        $category = ArticleCategory::query()->where('name', $persianName)->firstOrFail();
        $this->assertSame($chosenSlug, $category->slug);
        $this->assertNotSame($transliterated, $category->slug);
    }

    /**
     * 4. Editing category name preserves existing slug.
     */
    public function test_editing_category_name_preserves_existing_slug(): void
    {
        $category = ArticleCategory::create([
            'name' => 'عنوان قدیمی دسته',
            'slug' => 'preserved-category-slug',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->call('edit', $category->id)
            ->assertSet('slug', 'preserved-category-slug')
            ->set('name', 'عنوان ویرایش شده و جدید')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $this->assertSame('عنوان ویرایش شده و جدید', $category->name);
        $this->assertSame('preserved-category-slug', $category->slug);
    }

    /**
     * 5. Attempting to change an existing category slug is rejected.
     */
    public function test_changing_existing_category_slug_is_rejected(): void
    {
        $category = ArticleCategory::create([
            'name' => 'دسته تغییرناپذیر',
            'slug' => 'immutable-category-slug',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->call('edit', $category->id)
            ->set('slug', 'attempted-new-slug')
            ->call('save')
            ->assertHasErrors(['slug']);

        $category->refresh();
        $this->assertSame('immutable-category-slug', $category->slug);
    }

    /**
     * 6. Duplicate slug is rejected on creation.
     */
    public function test_duplicate_slug_is_rejected(): void
    {
        ArticleCategory::create([
            'name' => 'دسته اول',
            'slug' => 'shared-unique-slug',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'دسته دوم')
            ->set('slug', 'shared-unique-slug')
            ->call('save')
            ->assertHasErrors(['slug']);

        $this->assertSame(1, ArticleCategory::where('slug', 'shared-unique-slug')->count());
    }

    /**
     * 7. Invalid characters are rejected.
     */
    public function test_invalid_characters_are_rejected(): void
    {
        // Persian characters
        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'تست کاراکتر')
            ->set('slug', 'نامک-فارسی-دسته')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Spaces and special characters
        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'تست کاراکتر')
            ->set('slug', 'invalid category slug with spaces!')
            ->call('save')
            ->assertHasErrors(['slug']);

        // Symbols
        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->set('name', 'تست کاراکتر')
            ->set('slug', 'invalid@category#slug')
            ->call('save')
            ->assertHasErrors(['slug']);
    }

    /**
     * 8. Existing category relationships and article associations are preserved.
     */
    public function test_existing_category_relationships_and_article_associations_are_preserved(): void
    {
        $category = ArticleCategory::create([
            'name' => 'دسته اصلی مقالات',
            'slug' => 'main-articles-category',
        ]);

        $article = Article::create([
            'article_category_id' => $category->id,
            'title' => 'مقاله مرتبط با دسته',
            'slug' => 'related-article-slug',
            'content' => 'محتوای مقاله',
            'status' => ArticleStatusEnum::PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleCategoryManager::class)
            ->call('edit', $category->id)
            ->set('name', 'دسته اصلی مقالات به‌روزشده')
            ->call('save')
            ->assertHasNoErrors();

        $category->refresh();
        $article->refresh();

        $this->assertSame($category->id, $article->article_category_id);
        $this->assertSame('main-articles-category', $category->slug);
        $this->assertSame(1, $category->articles()->count());
    }
}
