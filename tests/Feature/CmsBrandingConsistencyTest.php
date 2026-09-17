<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\FaqItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsBrandingConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function setSiteName(string $name): void
    {
        SiteSetting::updateOrCreate(
            ['key' => 'site_name'],
            ['value' => $name, 'type' => 'string', 'group' => 'identity', 'is_public' => true],
        );
    }

    public function test_custom_site_name_is_used_across_cms_pages_and_layout(): void
    {
        $this->setSiteName('الواکارت آزمایشی');

        $page = Page::create([
            'page_type' => 'general',
            'title' => 'درباره ما',
            'slug' => 'about-us',
            'content' => 'محتوا',
            'is_active' => true,
        ]);

        Article::create([
            'title' => 'جدیدترین مطلب',
            'slug' => 'newest-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        FaqItem::create(['question' => 'سوال؟', 'answer' => 'جواب', 'sort_order' => 1, 'is_active' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('الواکارت آزمایشی');

        $this->get(route('articles.index'))
            ->assertOk()
            ->assertSee('مقالات - الواکارت آزمایشی');

        $this->get(route('articles.show', 'newest-post'))
            ->assertOk()
            ->assertSee('جدیدترین مطلب - الواکارت آزمایشی');

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('درباره ما - الواکارت آزمایشی');

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('سوالات متداول - الواکارت آزمایشی');
    }

    public function test_pages_and_articles_never_leak_global_app_name_over_site_name(): void
    {
        $this->setSiteName('نام واقعی برند');

        $page = Page::create([
            'page_type' => 'general',
            'title' => 'تماس با ما',
            'slug' => 'contact-us',
            'content' => 'محتوا',
            'is_active' => true,
        ]);

        $article = Article::create([
            'title' => 'راهنما',
            'slug' => 'guide-post',
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('تماس با ما - نام واقعی برند');

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('راهنما - نام واقعی برند');
    }
}
