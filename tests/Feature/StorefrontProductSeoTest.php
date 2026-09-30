<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StorefrontProductSeoTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کارت هدیه تست',
            'slug' => 'seo-product-'.uniqid(),
            'description' => 'توضیح کوتاه محصول',
            'base_price' => 200000,
            'main_image' => 'products/seo-main.png',
            'is_active' => true,
        ], $overrides));
    }

    private function color(string $name): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => sprintf('#%06X', random_int(0, 0xFFFFFF)),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function colorPrice(Product $product, Color $color, int $price): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function schemaArray(string $content): array
    {
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $content, $matches);
        $this->assertNotEmpty($matches, 'A JSON-LD script tag should be rendered.');

        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded, 'JSON-LD must be valid JSON.');

        return $decoded;
    }

    public function test_product_detail_renders_meta_title(): void
    {
        $product = $this->product(['meta_title' => 'عنوان سئو اختصاصی']);
        $siteName = site_setting('site_name', config('app.name'));

        $response = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>عنوان سئو اختصاصی - '.$siteName.'</title>', false);

        // Asserting the string is present is not enough: the layout must own the
        // only title element, otherwise the fixed site-name title wins and the
        // admin meta_title is inert (N-Onyx-49 / SEO-01).
        $this->assertSame(
            1,
            preg_match_all('/<title\b[^>]*>/i', $response->getContent()),
            'The product page must render exactly one title element.'
        );
    }

    public function test_product_detail_title_falls_back_to_product_name(): void
    {
        $product = $this->product(['meta_title' => null]);
        $siteName = site_setting('site_name', config('app.name'));

        $response = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>'.$product->name.' - '.$siteName.'</title>', false);

        $this->assertSame(
            1,
            preg_match_all('/<title\b[^>]*>/i', $response->getContent()),
            'The product page must render exactly one title element.'
        );
    }

    public function test_meta_description_renders_when_set(): void
    {
        $product = $this->product(['meta_description' => 'توضیح متای اختصاصی']);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta name="description" content="توضیح متای اختصاصی">', false);
    }

    public function test_meta_description_is_omitted_when_empty(): void
    {
        $product = $this->product(['meta_description' => null]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertDontSee('<meta name="description"', false);
    }

    public function test_canonical_points_to_the_current_product_url_by_default(): void
    {
        $product = $this->product();

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('catalog.products.show', $product->slug).'">', false);
    }

    public function test_canonical_ignores_irrelevant_query_strings(): void
    {
        $product = $this->product();

        $content = $this->get(route('catalog.products.show', $product->slug).'?color_id=7&source=social')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('catalog.products.show', $product->slug).'">',
            $content
        );
        $this->assertStringNotContainsString('?color_id=7', explode("\n", $content)[0] ?? '');
    }

    public function test_configured_canonical_override_is_respected(): void
    {
        $product = $this->product(['canonical_url' => 'https://example.com/legacy/product']);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://example.com/legacy/product">', false);
    }

    public function test_non_http_canonical_override_falls_back_to_current_url(): void
    {
        $product = $this->product(['canonical_url' => 'javascript:alert(1)']);

        $content = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('catalog.products.show', $product->slug).'">',
            $content
        );
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_robots_index_follow_by_default(): void
    {
        $product = $this->product();

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta name="robots" content="index,follow">', false);
    }

    public function test_robots_noindex_follow_when_disabled(): void
    {
        $product = $this->product(['robots_index' => false]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow">', false);
    }

    public function test_open_graph_tags_render_with_configured_image(): void
    {
        $product = $this->product([
            'meta_title' => 'عنوان کارت',
            'meta_description' => 'توضیح کارت',
            'og_image' => 'products/seo-og.png',
        ]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:title" content="عنوان کارت">', false)
            ->assertSee('<meta property="og:description" content="توضیح کارت">', false)
            ->assertSee('<meta property="og:url" content="'.route('catalog.products.show', $product->slug).'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('storage/products/seo-og.png').'">', false);
    }

    public function test_open_graph_image_falls_back_to_main_image(): void
    {
        $product = $this->product(['og_image' => null]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('storage/products/seo-main.png').'">', false);
    }

    public function test_twitter_card_tags_render(): void
    {
        $product = $this->product([
            'meta_title' => 'عنوان کارت',
            'meta_description' => 'توضیح کارت',
            'og_image' => 'products/seo-og.png',
        ]);

        $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
            ->assertSee('<meta name="twitter:title" content="عنوان کارت">', false)
            ->assertSee('<meta name="twitter:description" content="توضیح کارت">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('storage/products/seo-og.png').'">', false);
    }

    public function test_json_ld_offer_for_single_base_price_product(): void
    {
        $product = $this->product(['base_price' => 200000]);

        $schema = $this->schemaArray($this->get(route('catalog.products.show', $product->slug))->getContent());

        $this->assertSame('https://schema.org', $schema['@context']);
        $this->assertSame('Product', $schema['@type']);
        $this->assertSame($product->name, $schema['name']);
        $this->assertSame(route('catalog.products.show', $product->slug), $schema['url']);
        $this->assertSame($product->description, $schema['description']);
        $this->assertSame(asset('storage/products/seo-main.png'), $schema['image']);

        $this->assertSame('Offer', $schema['offers']['@type']);
        $this->assertSame(200000, $schema['offers']['price']);
        $this->assertSame('IRT', $schema['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
    }

    public function test_json_ld_aggregate_offer_covers_color_and_base_price_set(): void
    {
        $product = $this->product(['base_price' => 200000]);
        $this->colorPrice($product, $this->color('سئو طلایی'), 150000);
        $this->colorPrice($product, $this->color('سئو نقره‌ای'), 260000);

        $schema = $this->schemaArray($this->get(route('catalog.products.show', $product->slug))->getContent());

        $this->assertSame('AggregateOffer', $schema['offers']['@type']);
        $this->assertSame(150000, $schema['offers']['lowPrice']);
        $this->assertSame(260000, $schema['offers']['highPrice']);
        $this->assertSame(3, $schema['offers']['offerCount']);
        $this->assertSame('IRT', $schema['offers']['priceCurrency']);
    }

    public function test_json_ld_omits_offers_for_intentionally_unpurchasable_page(): void
    {
        $product = $this->product();

        DB::table('products')->where('id', $product->id)->update(['customization_workflow' => 'vaporwave']);

        $schema = $this->schemaArray($this->get(route('catalog.products.show', $product->slug))->getContent());

        $this->assertSame('Product', $schema['@type']);
        $this->assertSame($product->name, $schema['name']);
        $this->assertArrayNotHasKey('offers', $schema);
    }

    public function test_json_ld_does_not_leak_raw_markup_from_product_name(): void
    {
        $product = $this->product(['name' => 'کارت <b>طلایی</b>']);

        $content = $this->get(route('catalog.products.show', $product->slug))->getContent();
        $schema = $this->schemaArray($content);

        $this->assertSame('کارت <b>طلایی</b>', $schema['name']);
        $this->assertStringNotContainsString('<b>طلایی</b>', $content, 'JSON-LD must hex-escape markup.');
    }

    public function test_seo_content_is_rendered_as_escaped_text(): void
    {
        $product = $this->product(['seo_content' => 'توضیح سئو <script>alert(1)</script>']);

        $content = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(e('توضیح سئو <script>alert(1)</script>'), $content);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $content);
    }
}
