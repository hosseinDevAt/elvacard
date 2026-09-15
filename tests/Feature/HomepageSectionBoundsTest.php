<?php

namespace Tests\Feature;

use App\Enums\ArticleStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Article;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomepageSectionBoundsTest extends TestCase
{
    use RefreshDatabase;

    private function section(string $type, array $settings, int $sortOrder = 1): HomepageSection
    {
        return HomepageSection::create([
            'section_type' => $type,
            'title' => 'بخش '.$type,
            'settings' => $settings,
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function commerceProduct(string $name): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'commerce-'.Str::slug($name).'-'.uniqid(),
            'base_price' => 100000,
            'is_active' => true,
        ]);
    }

    private function publishedArticle(string $name): Article
    {
        return Article::create([
            'title' => $name,
            'slug' => 'article-'.uniqid(),
            'content' => 'محتوا',
            'status' => ArticleStatusEnum::PUBLISHED->value,
            'published_at' => now()->subMinutes(random_int(1, 60)),
        ]);
    }

    private function faq(string $question): FaqItem
    {
        return FaqItem::create([
            'question' => $question,
            'answer' => 'پاسخ',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function design(string $name): Design
    {
        $category = CateDesign::create([
            'name' => 'دسته '.$name,
            'slug' => 'cat-'.$name.'-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => $name,
            'slug' => 'design-'.Str::slug($name).'-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $color = Color::create([
            'name' => 'رنگ '.$name,
            'code_hex' => '#123456',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return $design;
    }

    public function test_homepage_default_section_limits_keep_every_section_bounded(): void
    {
        for ($i = 0; $i < 12; $i++) {
            $this->commerceProduct('محصول '.$i);
            $this->faq('سوال '.$i);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->publishedArticle('مقاله '.$i);
        }
        for ($i = 0; $i < 12; $i++) {
            $this->design('طرح '.$i);
        }

        $this->section('newest_products', []);
        $this->section('faq', []);
        $this->section('articles', []);
        $this->section('featured_designs', []);

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $this->assertCount(8, $data['newest_products']['products']);
        $this->assertCount(8, $data['faq']['faqs']);
        $this->assertCount(3, $data['articles']['articles']);
        $this->assertCount(8, $data['featured_designs']['designs']);
    }

    public function test_homepage_respects_configured_limits(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->commerceProduct('گزینه '.$i);
            $this->faq('پرسش '.$i);
        }
        for ($i = 0; $i < 5; $i++) {
            $this->publishedArticle('مطلب '.$i);
        }
        for ($i = 0; $i < 10; $i++) {
            $this->design('نمونه '.$i);
        }

        $this->section('newest_products', ['limit' => 2]);
        $this->section('faq', ['limit' => 3]);
        $this->section('articles', ['limit' => 2]);
        $this->section('featured_designs', ['limit' => 5]);

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $this->assertCount(2, $data['newest_products']['products']);
        $this->assertCount(3, $data['faq']['faqs']);
        $this->assertCount(2, $data['articles']['articles']);
        $this->assertCount(5, $data['featured_designs']['designs']);
    }

    public function test_homepage_caps_featured_products_even_with_explicit_ids(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->commerceProduct('ویترین '.$i)->id;
        }

        $this->section('featured_products', ['product_ids' => $ids]);

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $this->assertCount(8, $data['featured_products']['products']);
    }

    public function test_homepage_applies_configured_featured_product_limit(): void
    {
        $ids = [];
        for ($i = 0; $i < 10; $i++) {
            $ids[] = $this->commerceProduct('منتخب '.$i)->id;
        }

        $this->section('featured_products', ['product_ids' => $ids, 'limit' => 4]);

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $this->assertCount(4, $data['featured_products']['products']);
    }
}
