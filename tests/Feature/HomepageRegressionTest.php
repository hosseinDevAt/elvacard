<?php

namespace Tests\Feature;

use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_database_homepage_still_returns_200_without_error(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_seeded_homepage_renders_the_banner_and_every_configured_section(): void
    {
        $this->seed();

        $response = $this->get('/')->assertOk();

        // Banner comes from SiteSettings, not from a hard-coded value.
        $response->assertSee('کارت شخصی فلزی');

        // Each section renders only when its data source is non-empty.
        $response->assertSee('جدیدترین محصولات');
        $response->assertSee('طرح‌های محبوب');
        $response->assertSee('آخرین مقالات');

        $response->assertSee('کارت بانکی');
        $response->assertSee('راهنمای انتخاب کارت شخصی فلزی');

        $data = $response->viewData('sectionData');

        $this->assertTrue($data['newest_products']['products']->isNotEmpty());
        $this->assertTrue($data['featured_designs']['designs']->isNotEmpty());
        $this->assertTrue($data['articles']['articles']->isNotEmpty());
    }

    public function test_seeded_card_products_have_a_real_purchasable_design_path(): void
    {
        $this->seed();

        $purchasable = Product::query()->purchasable()->pluck('slug')->all();

        $this->assertContains('bank-card', $purchasable);
        $this->assertContains('fuel-card', $purchasable);
    }

    public function test_seeding_twice_is_idempotent_for_homepage_data(): void
    {
        $this->seed();
        $this->seed();

        $response = $this->get('/')->assertOk();

        $response->assertSee('جدیدترین محصولات');
        $response->assertSee('آخرین مقالات');

        $this->assertSame(1, HomepageSection::query()->where('section_type', 'newest_products')->count());
    }
}
