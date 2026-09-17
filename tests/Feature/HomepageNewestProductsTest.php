<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Models\HomepageSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HomepageNewestProductsTest extends TestCase
{
    use RefreshDatabase;

    private function section(string $type, array $settings): HomepageSection
    {
        return HomepageSection::create([
            'section_type' => $type,
            'title' => 'بخش '.$type,
            'settings' => $settings,
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function product(string $name, string $createdAt): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'commerce-'.Str::slug($name).'-'.uniqid(),
            'base_price' => 100000,
            'is_active' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_newest_products_sort_by_created_at_desc_then_id_desc(): void
    {
        $this->section('newest_products', []);

        $oldest = $this->product('قدیمی', '2026-01-01 10:00:00');
        $sameTimeA = $this->product('همزمان الف', '2026-01-05 10:00:00');
        $sameTimeB = $this->product('همزمان ب', '2026-01-05 10:00:00');
        $newest = $this->product('جدیدترین', '2026-02-01 10:00:00');

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $ids = $data['newest_products']['products']->pluck('id')->all();

        // Newest created_at first; equal created_at broken by id DESC.
        $this->assertSame(
            [$newest->id, $sameTimeB->id, $sameTimeA->id, $oldest->id],
            $ids
        );
    }

    public function test_newest_products_respect_configured_limit_and_never_leak_inactive(): void
    {
        $this->section('newest_products', ['limit' => 2]);

        $this->product('مخفی', '2026-02-01 10:00:00');
        Product::where('name', 'مخفی')->update(['is_active' => false]);

        $first = $this->product('اول', '2026-03-01 10:00:00');
        $second = $this->product('دوم', '2026-03-02 10:00:00');
        $third = $this->product('سوم', '2026-03-03 10:00:00');

        $data = $this->get('/')->assertOk()->viewData('sectionData');

        $ids = $data['newest_products']['products']->pluck('id')->all();

        $this->assertSame([$third->id, $second->id], $ids);
    }
}
