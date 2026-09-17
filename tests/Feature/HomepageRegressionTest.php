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

    public function test_seeded_homepage_never_exposes_card_workflows_as_store_products(): void
    {
        $this->seed();

        $response = $this->get('/')->assertOk();

        // Banner comes from SiteSettings, not from a hard-coded value.
        $response->assertSee('کارت شخصی فلزی');

        // Sections backed by ordinary data still render.
        $response->assertSee('طرح‌های محبوب');
        $response->assertSee('آخرین مقالات');

        $data = $response->viewData('sectionData');

        // The seed contains only Bank/Fuel card workflows (no ordinary Store
        // products), so the ordinary Store product section is legitimately
        // empty instead of leaking card products as Store items.
        $this->assertTrue($data['newest_products']['products']->isEmpty());
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

        $response->assertSee('آخرین مقالات');

        $this->assertSame(1, HomepageSection::query()->where('section_type', 'newest_products')->count());
    }
}
