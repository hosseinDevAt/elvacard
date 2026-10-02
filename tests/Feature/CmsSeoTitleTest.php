<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Article;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * N-Onyx-49 / SEO-01 regression coverage.
 *
 * The public layouts used to render a fixed site-name title and then yield the
 * content meta section, so every content view appended a *second* title tag.
 * Browsers and crawlers honour the first one, which silently discarded the
 * admin-controlled meta_title on every public page.
 *
 * These tests assert the rendered document title itself -- not merely that a
 * string appears somewhere in the HTML -- so the duplicate-title regression
 * cannot return unnoticed.
 */
class CmsSeoTitleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every title element in the rendered response, in document order.
     *
     * @return list<string>
     */
    private function titleElements(string $content): array
    {
        preg_match_all('/<title\b[^>]*>(.*?)<\/title>/is', $content, $matches);

        return $matches[1];
    }

    /**
     * The authoritative document title: the single title element's content.
     */
    private function documentTitle(string $content): string
    {
        $titles = $this->titleElements($content);

        $this->assertCount(
            1,
            $titles,
            'A public page must render exactly one title element, found: '.json_encode($titles, JSON_UNESCAPED_UNICODE)
        );

        return trim($titles[0]);
    }

    private function siteName(): string
    {
        return (string) site_setting('site_name', config('app.name'));
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کارت هدیه تست',
            'slug' => 'seo-title-product-'.uniqid(),
            'description' => 'توضیح کوتاه محصول',
            'base_price' => 200000,
            'main_image' => 'products/seo-main.png',
            'is_active' => true,
        ], $overrides));
    }

    private function page(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'page_type' => 'general',
            'title' => 'درباره ما',
            'slug' => 'about-'.uniqid(),
            'content' => 'متن صفحه',
            'is_active' => true,
        ], $overrides));
    }

    private function article(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'title' => 'مقاله تست',
            'slug' => 'post-'.uniqid(),
            'content' => 'متن مقاله',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ], $overrides));
    }

    // ---------------------------------------------------------------------
    // Test A + B: exactly one title, and the admin meta_title wins.
    // ---------------------------------------------------------------------

    public function test_product_detail_renders_exactly_one_title_and_meta_title_wins(): void
    {
        $product = $this->product(['meta_title' => 'عنوان سئو اختصاصی محصول']);

        $content = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            'عنوان سئو اختصاصی محصول - '.$this->siteName(),
            $this->documentTitle($content)
        );
    }

    public function test_page_detail_renders_exactly_one_title_and_meta_title_wins(): void
    {
        $page = $this->page(['meta_title' => 'عنوان سئو اختصاصی صفحه']);

        $content = $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            'عنوان سئو اختصاصی صفحه - '.$this->siteName(),
            $this->documentTitle($content)
        );
    }

    public function test_article_detail_renders_exactly_one_title_and_meta_title_wins(): void
    {
        $article = $this->article(['meta_title' => 'عنوان سئو اختصاصی مقاله']);

        $content = $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            'عنوان سئو اختصاصی مقاله - '.$this->siteName(),
            $this->documentTitle($content)
        );
    }

    // ---------------------------------------------------------------------
    // Test C: the pre-existing fallback chain still holds.
    // ---------------------------------------------------------------------

    public function test_product_detail_title_falls_back_to_product_name(): void
    {
        $product = $this->product(['meta_title' => null]);

        $content = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame($product->name.' - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_page_detail_title_falls_back_to_page_title(): void
    {
        $page = $this->page(['meta_title' => null]);

        $content = $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame($page->title.' - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_article_detail_title_falls_back_to_article_title(): void
    {
        $article = $this->article(['meta_title' => null]);

        $content = $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame($article->title.' - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_homepage_without_seo_metadata_falls_back_to_the_site_name(): void
    {
        $content = $this->get(route('home'))
            ->assertOk()
            ->getContent();

        $this->assertSame($this->siteName(), $this->documentTitle($content));
    }

    public function test_articles_index_renders_exactly_one_title(): void
    {
        $content = $this->get(route('articles.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('مقالات - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_article_category_index_renders_exactly_one_title(): void
    {
        $article = $this->article();
        $category = $article->category()->create([
            'name' => 'راهنماها',
            'slug' => 'guides-'.uniqid(),
        ]);

        $content = $this->get(route('articles.category', $category->slug))
            ->assertOk()
            ->getContent();

        $this->assertSame($category->name.' - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_faq_index_renders_exactly_one_title(): void
    {
        $content = $this->get(route('faq.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('سوالات متداول - '.$this->siteName(), $this->documentTitle($content));
    }

    public function test_guest_layout_page_renders_exactly_one_title(): void
    {
        $content = $this->get(route('login'))
            ->assertOk()
            ->getContent();

        $this->assertNotEmpty(
            $this->documentTitle($content),
            'A guest page without SEO metadata falls back to the site name.'
        );
    }

    // ---------------------------------------------------------------------
    // Test D: an HTML-like meta_title stays escaped inside the title element.
    // ---------------------------------------------------------------------

    public function test_meta_title_with_html_is_escaped_and_cannot_break_out(): void
    {
        $product = $this->product(['meta_title' => '</title><script>alert(1)</script>']);

        $content = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertCount(1, $this->titleElements($content));

        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
        $this->assertStringNotContainsString('</title><script>', $content);

        $this->assertStringContainsString(
            '&lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
            $content
        );
    }

    public function test_page_meta_title_with_html_is_escaped(): void
    {
        $page = $this->page(['meta_title' => '<b>bold</b> "quoted" & ampersand']);

        $content = $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->getContent();

        $this->assertCount(1, $this->titleElements($content));

        $this->assertStringNotContainsString('<b>bold</b>', $content);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $content);
    }

    // ---------------------------------------------------------------------
    // Structural guard: no content view may emit its own title element.
    // ---------------------------------------------------------------------

    public function test_only_layouts_and_standalone_error_pages_own_a_title_element(): void
    {
        $root = resource_path('views');
        $owners = [];

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $real = $file->getRealPath();
            $relative = str_replace('\\', '/', substr($real, strlen($root) + 1));

            // A title element only counts when it is real markup, not prose
            // inside a Blade comment.
            $stripped = preg_replace('/\{\{--.*?--\}\}/s', '', (string) file_get_contents($real));

            if (preg_match('/<title\b/', (string) $stripped) === 1) {
                $owners[] = $relative;
            }
        }

        sort($owners);

        $this->assertSame([
            'errors/404.blade.php',
            'errors/500.blade.php',
            'layouts/admin.blade.php',
            'layouts/app.blade.php',
            'layouts/auth.blade.php',
            'layouts/guest.blade.php',
        ], $owners, 'Only layouts and standalone error documents may own a title element.');
    }
}
