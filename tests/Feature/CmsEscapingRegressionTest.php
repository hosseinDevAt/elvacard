<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Enums\HomepageSectionTypeEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Article;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\Product;
use FilesystemIterator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Stored-XSS regression coverage for every admin-authored string that is
 * rendered on a public page (N-Onyx-42 findings CMS-01 and CMS-02, plus the
 * FAQ answer sink the audit missed).
 *
 * None of these fields has a rich-text editor behind it: they are plain
 * <textarea wire:model> inputs validated as `string`, so any markup reaching
 * them is hostile or accidental, never legitimate formatting. They must
 * therefore be escaped, not sanitized.
 */
class CmsEscapingRegressionTest extends TestCase
{
    use RefreshDatabase;

    private const SCRIPT_PAYLOAD = '<script>alert("xss")</script>';

    private const IMG_PAYLOAD = '<img src=x onerror=alert(1)>';

    private const ESCAPED_SCRIPT = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';

    private const ESCAPED_IMG = '&lt;img src=x onerror=alert(1)&gt;';

    public function test_article_body_is_escaped_on_the_public_article_page(): void
    {
        $article = Article::create([
            'title' => 'مقاله امن',
            'slug' => 'safe-article',
            'content' => self::SCRIPT_PAYLOAD,
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get(route('articles.show', $article->slug));

        $response->assertOk();
        $response->assertDontSee(self::SCRIPT_PAYLOAD, false);
        $response->assertSee(self::ESCAPED_SCRIPT, false);
    }

    public function test_faq_answer_is_escaped_on_the_public_faq_page(): void
    {
        FaqItem::create([
            'question' => 'سوال امن',
            'answer' => self::SCRIPT_PAYLOAD,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertDontSee(self::SCRIPT_PAYLOAD, false);
        $response->assertSee(self::ESCAPED_SCRIPT, false);
    }

    /**
     * End-to-end proof for the homepage sections that render unconditionally.
     * The data-driven sections (featured products/designs, faq, newest products,
     * articles) are covered structurally by the test below rather than through
     * a deep purchasable()/design-catalogue fixture chain that says nothing about
     * escaping.
     */
    #[DataProvider('unconditionalSectionTypes')]
    public function test_homepage_section_title_and_content_are_escaped(string $type): void
    {
        HomepageSection::create([
            'section_type' => $type,
            'title' => self::IMG_PAYLOAD,
            'content' => self::SCRIPT_PAYLOAD,
            'settings' => [],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(self::IMG_PAYLOAD, false);
        $response->assertDontSee(self::SCRIPT_PAYLOAD, false);
        $response->assertSee(self::ESCAPED_IMG, false);
        $response->assertSee(self::ESCAPED_SCRIPT, false);
    }

    public static function unconditionalSectionTypes(): array
    {
        return [
            'hero' => [HomepageSectionTypeEnum::HERO->value],
            'banner' => [HomepageSectionTypeEnum::BANNER->value],
            'text block' => [HomepageSectionTypeEnum::TEXT_BLOCK->value],
        ];
    }

    /**
     * Structural invariant: no public view may emit an admin-authored content
     * field through Blade's unescaped {!! !!} syntax. This covers every
     * homepage section type, including the data-driven ones and any added in
     * future, without duplicating the escaping assertions per partial.
     *
     * The single sanctioned unescaped output is the product JSON-LD block, which
     * is json_encode()d with JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT
     * and therefore cannot break out of its <script> context.
     */
    public function test_no_public_view_unescapes_admin_authored_content(): void
    {
        $views = [
            ...glob(resource_path('views/components/homepage/*.blade.php')),
            ...glob(resource_path('views/components/faq-item.blade.php')),
            resource_path('views/cms/articles/show.blade.php'),
        ];

        $this->assertNotEmpty($views);

        $offenders = [];

        foreach ($views as $view) {
            $contents = (string) file_get_contents($view);

            if (preg_match_all('/\{!!(.*?)!!\}/s', $contents, $matches) === 0) {
                continue;
            }

            foreach ($matches[1] as $expression) {
                foreach (['$section->title', '$section->content', '$article->content', '$faq->answer'] as $field) {
                    if (str_contains($expression, $field)) {
                        $offenders[] = basename($view).' => '.$field;
                    }
                }
            }
        }

        $this->assertSame([], $offenders, 'Admin-authored content must be escaped with {{ }}.');
    }

    public function test_the_only_unescaped_output_is_the_hardened_json_ld_block(): void
    {
        $hits = [];

        foreach ($this->bladeViews() as $path => $contents) {
            if (str_contains($contents, '{!!')) {
                $hits[] = $path;
            }
        }

        $this->assertSame(['catalog/products/show.blade.php'], $hits);

        $controller = (string) file_get_contents(app_path('Http/Controllers/Catalog/ProductCatalogController.php'));

        $this->assertStringContainsString('JSON_HEX_TAG', $controller);
        $this->assertStringContainsString('JSON_HEX_AMP', $controller);
    }

    /**
     * Every Blade view, keyed by its path relative to resources/views.
     * PHP's glob() has no recursive "**" support, so walk the tree.
     *
     * @return array<string, string>
     */
    private function bladeViews(): array
    {
        $root = resource_path('views');
        $views = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            // getExtension() is "php" for "show.blade.php", so match the suffix.
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $real = $file->getRealPath();
            $views[mb_substr(str_replace('\\', '/', $real), mb_strlen(str_replace('\\', '/', $root)) + 1)] = (string) file_get_contents($real);
        }

        ksort($views);

        return $views;
    }

    public function test_legitimate_plain_text_content_still_renders_readable(): void
    {
        $article = Article::create([
            'title' => 'مقاله سالم',
            'slug' => 'plain-article',
            'content' => 'متن ساده و معمولی مقاله',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('articles.show', $article->slug))
            ->assertOk()
            ->assertSee('متن ساده و معمولی مقاله');
    }

    public function test_homepage_plain_text_sections_still_render_readable(): void
    {
        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کالا',
            'slug' => 'readable-product',
            'base_price' => 1000,
            'is_active' => false,
        ]);

        HomepageSection::create([
            'section_type' => HomepageSectionTypeEnum::TEXT_BLOCK->value,
            'title' => 'عنوان سالم',
            'content' => 'متن سالم و معمولی',
            'settings' => [],
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('عنوان سالم')
            ->assertSee('متن سالم و معمولی');
    }
}
