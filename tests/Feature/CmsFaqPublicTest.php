<?php

namespace Tests\Feature;

use App\Models\FaqItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsFaqPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_faq_index_lists_active_questions_in_order(): void
    {
        FaqItem::create(['question' => 'پرسش دوم', 'answer' => 'پاسخ دوم', 'sort_order' => 2, 'is_active' => true]);
        $first = FaqItem::create(['question' => 'پرسش اول', 'answer' => 'پاسخ اول', 'sort_order' => 1, 'is_active' => true]);
        FaqItem::create(['question' => 'پرسش مخفی', 'answer' => 'پاسخ مخفی', 'sort_order' => 0, 'is_active' => false]);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('پرسش اول')
            ->assertSee('پرسش دوم')
            ->assertDontSee('پرسش مخفی');
    }
}
