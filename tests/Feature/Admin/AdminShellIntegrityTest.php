<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the admin shell contract that the N-Onyx-41 fix pass established:
 * one canonical brand, no controls the admin panel cannot honour, and icon
 * components whose size the caller actually controls.
 */
class AdminShellIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function adminHtml(): string
    {
        $admin = User::factory()->create(['role' => 'admin']);

        return $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();
    }

    public function test_header_offers_no_search_the_admin_panel_cannot_honour(): void
    {
        $html = $this->adminHtml();

        $this->assertStringNotContainsString('جستجو در سیستم', $html);
    }

    public function test_header_offers_no_notification_bell_without_a_notification_system(): void
    {
        $html = $this->adminHtml();

        $this->assertStringNotContainsString('اعلان‌ها', $html);
    }

    public function test_admin_shell_uses_one_canonical_brand(): void
    {
        $html = $this->adminHtml();

        $this->assertStringContainsString('ElvaCard', $html);
        $this->assertStringNotContainsString('elvacard', $html);
        $this->assertStringNotContainsString('سامانه مدیریت الواکارت', $html);
    }

    public function test_header_date_icon_keeps_the_size_requested_by_the_caller(): void
    {
        $html = $this->adminHtml();

        $this->assertStringContainsString('h-3.5 w-3.5 text-indigo-500', $html);
    }

    public function test_sidebar_icons_keep_the_size_requested_by_the_caller(): void
    {
        $html = $this->adminHtml();

        $this->assertStringContainsString('h-4 w-4 shrink-0', $html);
    }

    public function test_icon_class_lets_the_caller_own_the_size(): void
    {
        // an explicit caller size always wins over the component default
        $this->assertSame('h-3.5 w-3.5 text-indigo-500', icon_class('h-3.5 w-3.5 text-indigo-500'));
        $this->assertSame('h-4 w-4', icon_class('h-4 w-4', 'h-5 w-5'));

        // the default only applies when the caller expressed no size
        $this->assertSame('h-5 w-5 text-slate-400', icon_class('text-slate-400'));
        $this->assertSame('h-5 w-5', icon_class(null));
        $this->assertSame('w-16 h-16', icon_class('', 'w-16 h-16'));

        // an explicit size prop wins over both
        $this->assertSame('h-8 w-8 text-slate-400', icon_class('text-slate-400', 'h-5 w-5', 'h-8 w-8'));
    }

    public function test_every_icon_component_resolves_its_size_through_the_shared_helper(): void
    {
        $icons = glob(resource_path('views/components/icons').'/*.blade.php');

        $this->assertNotEmpty($icons);

        foreach ($icons as $icon) {
            $source = file_get_contents($icon);

            $this->assertStringContainsString(
                'icon_class(',
                $source,
                basename($icon).' must resolve its classes through icon_class()'
            );
            $this->assertStringContainsString(
                "'size' => null",
                $source,
                basename($icon).' must expose the size prop'
            );
        }
    }
}
