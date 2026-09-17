<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPagesPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_page_is_publicly_visible(): void
    {
        $page = Page::create([
            'page_type' => 'general',
            'title' => 'قوانین خرید',
            'slug' => 'shopping-rules',
            'content' => 'متن صفحه قوانین',
            'is_active' => true,
        ]);

        $this->get(route('pages.show', $page->slug))
            ->assertOk()
            ->assertSee('قوانین خرید')
            ->assertSee('متن صفحه قوانین');
    }

    public function test_inactive_page_returns_404(): void
    {
        $page = Page::create([
            'page_type' => 'general',
            'title' => 'مخفی',
            'slug' => 'hidden-page',
            'content' => 'متن',
            'is_active' => false,
        ]);

        $this->get(route('pages.show', $page->slug))->assertNotFound();
    }

    public function test_unknown_page_returns_404(): void
    {
        $this->get(route('pages.show', 'missing-page'))->assertNotFound();
    }
}
