<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCategoryPublicTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, string $slug): ArticleCategory
    {
        return ArticleCategory::create(['name' => $name, 'slug' => $slug]);
    }

    private function article(ArticleCategory $category, string $title, string $status = ArticleStatusEnum::PUBLISHED->value): Article
    {
        return Article::create([
            'article_category_id' => $category->id,
            'title' => $title,
            'slug' => 'article-'.uniqid(),
            'content' => 'محتوا',
            'excerpt' => 'خلاصه '.$title,
            'status' => $status,
            'published_at' => now()->subMinutes(random_int(1, 60)),
        ]);
    }

    public function test_category_page_lists_only_its_published_articles(): void
    {
        $finance = $this->category('امور مالی', 'finance');
        $travel = $this->category('سفر', 'travel');

        $this->article($finance, 'مدیریت هزینه سفر');
        $this->article($travel, 'سفر خارجی');
        $this->article($finance, 'پیش‌نویس', ArticleStatusEnum::DRAFT->value);

        $this->get(route('articles.category', $finance->slug))
            ->assertOk()
            ->assertSee('خلاصه مدیریت هزینه سفر')
            ->assertDontSee('سفر خارجی')
            ->assertDontSee('پیش‌نویس');
    }

    public function test_category_page_uses_deterministic_published_ordering_relative_to_article_list(): void
    {
        $finance = $this->category('امور مالی', 'finance');

        $older = $this->article($finance, 'مقاله قدیمی');
        $newer = $this->article($finance, 'مقاله جدید');

        Article::where('id', $older->id)->update(['published_at' => now()->subDays(5)]);
        Article::where('id', $newer->id)->update(['published_at' => now()->subDay()]);

        $response = $this->get(route('articles.category', $finance->slug))->assertOk();

        $this->assertTrue(
            strpos($response->getContent(), 'مقاله جدید') < strpos($response->getContent(), 'مقاله قدیمی'),
            'Newest published article must render first.'
        );
    }

    public function test_unknown_category_returns_404(): void
    {
        $this->get(route('articles.category', 'does-not-exist'))->assertNotFound();
    }

    public function test_article_show_links_to_its_category_and_back_via_category(): void
    {
        $finance = $this->category('امور مالی', 'finance');
        $article = $this->article($finance, 'بودجه‌بندی');

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('امور مالی')
            ->assertSee(route('articles.category', $finance->slug));
    }
}
