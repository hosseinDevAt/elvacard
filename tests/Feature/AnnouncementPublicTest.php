<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnnouncementManager;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementPublicTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function announcement(array $attributes): Announcement
    {
        return Announcement::create(array_merge([
            'title' => 'اطلاعیه',
            'content' => 'متن اطلاعیه',
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    public function test_visible_orders_by_sort_order_then_id(): void
    {
        $first = $this->announcement(['title' => 'اول', 'sort_order' => 1]);
        $second = $this->announcement(['title' => 'دوم', 'sort_order' => 2]);
        $zero = $this->announcement(['title' => 'صفر', 'sort_order' => 0]);

        $visible = Announcement::visible()->get();

        $this->assertSame([$zero->id, $first->id, $second->id], $visible->pluck('id')->all());
    }

    public function test_visible_tie_break_by_id_when_sort_order_equal(): void
    {
        $a = $this->announcement(['title' => 'الف', 'sort_order' => 3]);
        $b = $this->announcement(['title' => 'ب', 'sort_order' => 3]);

        $this->assertSame($a->id, Announcement::visible()->first()->id);

        $c = $this->announcement(['title' => 'ج', 'sort_order' => 3]);

        $this->assertSame($a->id, Announcement::visible()->first()->id, 'The earliest id stays the deterministic winner.');
        $this->assertSame([$a->id, $b->id, $c->id], Announcement::visible()->pluck('id')->all());
    }

    public function test_inactive_and_out_of_window_announcements_are_not_visible(): void
    {
        $this->announcement(['title' => 'غیرفعال', 'is_active' => false, 'sort_order' => 0]);
        $this->announcement(['title' => 'منقضی', 'sort_order' => 0, 'start_date' => now()->subDays(5), 'end_date' => now()->subDay()]);
        $this->announcement(['title' => 'آینده', 'sort_order' => 0, 'start_date' => now()->addDay()]);

        $visible = $this->announcement(['title' => 'قابل‌نمایش', 'sort_order' => 0]);

        $this->assertSame([$visible->id], Announcement::visible()->pluck('id')->all());
    }

    public function test_public_homepage_renders_the_deterministic_first_visible_announcement(): void
    {
        $this->announcement(['title' => 'اطلاعیه دوم', 'sort_order' => 2]);
        $first = $this->announcement(['title' => 'اطلاعیه اول', 'sort_order' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertSee('اطلاعیه اول')
            ->assertSee('متن اطلاعیه');
    }

    public function test_public_homepage_renders_no_announcement_when_none_visible(): void
    {
        $this->announcement(['title' => 'غیرفعال', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('غیرفعال');
    }

    public function test_admin_can_set_sort_order_and_edit_persists_it(): void
    {
        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->set('title', 'اطلاعیه مرتب')
            ->set('content', 'متن')
            ->set('sortOrder', 7)
            ->call('save')
            ->assertHasNoErrors();

        $saved = Announcement::where('title', 'اطلاعیه مرتب')->firstOrFail();
        $this->assertSame(7, (int) $saved->sort_order);

        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->call('edit', $saved->id)
            ->assertSet('sortOrder', 7);
    }

    // ---------------------------------------------------------------------
    // N-Onyx-49 / SET-01: stored hex colors must actually reach the DOM.
    // ---------------------------------------------------------------------

    /**
     * The style attribute of the rendered announcement bar.
     */
    private function announcementBarStyle(string $html): ?string
    {
        $matched = preg_match('/<div\s[^>]*role="alert"[^>]*>/s', $html, $tag);

        $this->assertSame(1, $matched, 'The announcement bar should be rendered with role="alert".');

        if (preg_match('/\sstyle="([^"]*)"/', $tag[0], $style) !== 1) {
            return null;
        }

        return $style[1];
    }

    public function test_stored_hex_colors_are_rendered_as_inline_styles(): void
    {
        $this->announcement([
            'title' => 'اطلاعیه رنگی',
            'background_color' => '#ff0000',
            'text_color' => '#ffffff',
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertSame(
            'background-color: #ff0000;color: #ffffff',
            $this->announcementBarStyle($content)
        );

        // The stored hex must never be emitted as a utility class token again.
        $this->assertStringNotContainsString('#ff0000', $this->classAttributeOf($content));
    }

    public function test_three_digit_hex_colors_are_supported(): void
    {
        $this->announcement([
            'title' => 'اطلاعیه کوتاه',
            'background_color' => '#0af',
            'text_color' => '#fff',
        ]);

        $style = $this->announcementBarStyle($this->get('/')->assertOk()->getContent());

        $this->assertSame('background-color: #0af;color: #fff', $style);
    }

    public function test_default_visual_behavior_is_preserved_when_no_colors_are_stored(): void
    {
        $this->announcement(['title' => 'اطلاعیه بی‌رنگ']);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertNull(
            $this->announcementBarStyle($content),
            'Without stored colors no inline override should be emitted.'
        );

        $classes = $this->classAttributeOf($content);
        $this->assertStringContainsString('bg-primary-50', $classes);
        $this->assertStringContainsString('text-primary-900', $classes);
    }

    public function test_only_the_background_color_falls_back_for_the_text_color(): void
    {
        $this->announcement([
            'title' => 'اطلاعیه نیمه‌رنگی',
            'background_color' => '#123456',
            'text_color' => null,
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertSame('background-color: #123456', $this->announcementBarStyle($content));

        $classes = $this->classAttributeOf($content);
        $this->assertStringNotContainsString('bg-primary-50', $classes);
        $this->assertStringContainsString('text-primary-900', $classes);
    }

    public function test_admin_can_persist_valid_hex_colors(): void
    {
        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->set('title', 'اطلاعیه ذخیره‌شده')
            ->set('content', 'متن')
            ->set('backgroundColor', '#ABCDEF')
            ->set('textColor', '#123456')
            ->call('save')
            ->assertHasNoErrors();

        $saved = Announcement::where('title', 'اطلاعیه ذخیره‌شده')->firstOrFail();

        $this->assertSame('#ABCDEF', $saved->background_color);
        $this->assertSame('#123456', $saved->text_color);
    }

    public function test_manager_still_rejects_non_hex_colors(): void
    {
        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->set('title', 'اطلاعیه نامعتبر')
            ->set('content', 'متن')
            ->set('backgroundColor', 'bg-red-500')
            ->set('textColor', 'text-primary-900')
            ->call('save')
            ->assertHasErrors(['backgroundColor', 'textColor']);

        $this->assertNull(
            Announcement::where('title', 'اطلاعیه نامعتبر')->first(),
            'An invalid color must not be persisted.'
        );
    }

    public function test_a_css_injection_payload_in_a_legacy_row_cannot_reach_the_style_attribute(): void
    {
        // Bypass validation to simulate a legacy/hand-edited row.
        $this->announcement([
            'title' => 'اطلاعیه دستکاری‌شده',
            'background_color' => '#fff;background-image:url(javascript:alert(1))',
        ]);

        $content = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('javascript:', $content);
        $this->assertStringNotContainsString('url(javascript', $content);

        // The invalid value must degrade to the default, not be emitted at all.
        $this->assertNull($this->announcementBarStyle($content));
        $this->assertStringContainsString('bg-primary-50', $this->classAttributeOf($content));
    }

    /**
     * The class attribute of the rendered announcement bar.
     */
    private function classAttributeOf(string $html): string
    {
        $matched = preg_match('/<div\s[^>]*role="alert"[^>]*>/s', $html, $tag);

        $this->assertSame(1, $matched, 'The announcement bar should be rendered with role="alert".');

        if (preg_match('/\sclass="([^"]*)"/', $tag[0], $class) !== 1) {
            return '';
        }

        return $class[1];
    }
}
