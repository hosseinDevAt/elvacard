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
}
