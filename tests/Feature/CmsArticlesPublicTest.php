<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsArticlesPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_index_lists_only_published_articles(): void
    {
        $published = Article::create([
            'title' => 'مقاله منتشرشده',
            'slug' => 'published-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        Article::create([
            'title' => 'مقاله پیش‌نویس',
            'slug' => 'draft-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::DRAFT->value,
        ]);

        Article::create([
            'title' => 'مقاله آینده',
            'slug' => 'future-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('مقاله منتشرشده')
            ->assertDontSee('مقاله پیش‌نویس')
            ->assertDontSee('مقاله آینده');
    }

    public function test_article_show_renders_published_article(): void
    {
        $article = Article::create([
            'title' => 'مقاله قابل‌مشاهده',
            'slug' => 'visible-post',
            'content' => 'محتوای کامل مقاله',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('مقاله قابل‌مشاهده');
    }

    public function test_draft_and_future_articles_return_404(): void
    {
        $draft = Article::create([
            'title' => 'پیش‌نویس',
            'slug' => 'draft-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::DRAFT->value,
        ]);

        $future = Article::create([
            'title' => 'آینده',
            'slug' => 'future-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->addDay(),
        ]);

        $this->get(route('articles.show', $draft->slug))->assertNotFound();
        $this->get(route('articles.show', $future->slug))->assertNotFound();
    }
}
