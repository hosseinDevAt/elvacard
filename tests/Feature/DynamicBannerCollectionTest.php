<?php

namespace Tests\Feature;

use App\Livewire\Admin\BannerManager;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicBannerCollectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // =========================================================================
    // Admin Dynamic Operations (Add, Multiple, Remove, Empty State, Reorder)
    // =========================================================================

    public function test_admin_can_add_first_banner_from_empty_state(): void
    {
        // Simulate empty state in DB
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        $this->assertCount(0, $component->get('banners'));

        // Add first banner
        $component->call('addBanner')
            ->set('banners.0.title', 'اولین بنر جدید')
            ->set('banners.0.cta_text', 'مشاهده')
            ->call('save')
            ->assertHasNoErrors();

        $saved = site_setting('homepage_banners');
        $this->assertCount(1, $saved);
        $this->assertEquals('اولین بنر جدید', $saved[0]['title']);
    }

    public function test_admin_can_manage_five_or_more_banners_dynamically(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        // Clear existing and add 5 banners
        $component->set('banners', []);

        for ($i = 1; $i <= 5; $i++) {
            $component->call('addBanner')
                ->set("banners." . ($i - 1) . ".title", "بنر شماره {$i}")
                ->set("banners." . ($i - 1) . ".cta_text", "کلیک {$i}")
                ->set("banners." . ($i - 1) . ".text_color", "#112233")
                ->set("banners." . ($i - 1) . ".button_bg_color", "#445566")
                ->set("banners." . ($i - 1) . ".button_text_color", "#ffffff");
        }

        $component->call('save')->assertHasNoErrors();

        $saved = site_setting('homepage_banners');
        $this->assertCount(5, $saved);
        for ($i = 1; $i <= 5; $i++) {
            $this->assertEquals("بنر شماره {$i}", $saved[$i - 1]['title']);
            $this->assertEquals('#112233', $saved[$i - 1]['text_color']);
        }
    }

    public function test_admin_can_remove_individual_banner(): void
    {
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                ['id' => 'b1', 'title' => 'بنر اول'],
                ['id' => 'b2', 'title' => 'بنر دوم'],
                ['id' => 'b3', 'title' => 'بنر سوم'],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        // Remove middle banner (index 1 -> b2)
        $component->call('removeBanner', 1)
            ->call('save');

        $saved = site_setting('homepage_banners');
        $this->assertCount(2, $saved);
        $this->assertEquals('بنر اول', $saved[0]['title']);
        $this->assertEquals('بنر سوم', $saved[1]['title']);
    }

    public function test_admin_can_remove_all_banners_down_to_empty_state(): void
    {
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                ['id' => 'b1', 'title' => 'بنر تک'],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        $component->call('removeBanner', 0)
            ->call('save');

        $saved = site_setting('homepage_banners');
        $this->assertIsArray($saved);
        $this->assertCount(0, $saved);
        $this->assertEmpty($component->get('banners'));
    }

    public function test_admin_can_reorder_banners_and_preserve_all_attributes(): void
    {
        Storage::fake('public');
        $imgA = 'appearance/imgA.jpg';
        $mobA = 'appearance/mobA.jpg';
        Storage::disk('public')->put($imgA, 'a');
        Storage::disk('public')->put($mobA, 'a-m');

        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                [
                    'id' => 'b1',
                    'title' => 'بنر الف',
                    'subtitle' => 'توضیح الف',
                    'cta_text' => 'دکمه الف',
                    'cta_url' => '/a',
                    'image_path' => $imgA,
                    'mobile_image_path' => $mobA,
                    'text_color' => '#111111',
                    'button_bg_color' => '#222222',
                    'button_text_color' => '#333333',
                ],
                [
                    'id' => 'b2',
                    'title' => 'بنر ب',
                    'subtitle' => 'توضیح ب',
                    'cta_text' => 'دکمه ب',
                    'cta_url' => '/b',
                    'image_path' => null,
                    'mobile_image_path' => null,
                    'text_color' => '#ffffff',
                    'button_bg_color' => '#ffde5b',
                    'button_text_color' => '#010619',
                ],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        // Move banner 2 up to position 0
        $component->call('moveUp', 1)
            ->call('save')
            ->assertHasNoErrors();

        $saved = site_setting('homepage_banners');
        $this->assertEquals('بنر ب', $saved[0]['title']);
        $this->assertEquals('بنر الف', $saved[1]['title']);

        // Check banner A attributes preserved in new position
        $this->assertEquals('توضیح الف', $saved[1]['subtitle']);
        $this->assertEquals('/a', $saved[1]['cta_url']);
        $this->assertEquals($imgA, $saved[1]['image_path']);
        $this->assertEquals($mobA, $saved[1]['mobile_image_path']);
        $this->assertEquals('#111111', $saved[1]['text_color']);
        $this->assertEquals('#222222', $saved[1]['button_bg_color']);
        $this->assertEquals('#333333', $saved[1]['button_text_color']);

        // Move banner 1 down back to position 1
        $component->call('moveDown', 0)
            ->call('save');

        $reSaved = site_setting('homepage_banners');
        $this->assertEquals('بنر الف', $reSaved[0]['title']);
        $this->assertEquals('بنر ب', $reSaved[1]['title']);
    }

    // =========================================================================
    // Frontend Public Rendering (0, 1, 2, 3, 5+ banners)
    // =========================================================================

    public function test_frontend_renders_nothing_when_banners_collection_is_empty(): void
    {
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        // Absolutely no slider section rendered
        $view->assertDontSee('aria-label="بنرهای تبلیغاتی"', false);
        $view->assertDontSee('x-data="bannerSlider', false);
    }

    public function test_frontend_renders_single_banner_without_arrows_or_dots(): void
    {
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                [
                    'id' => 'single',
                    'title' => 'تنها بنر فعال سایت',
                    'subtitle' => 'معرفی محصول برتر',
                    'cta_text' => 'سفارش مستقیم',
                    'cta_url' => '/catalog',
                    'image_path' => null,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        // Banner content is rendered
        $view->assertSee('تنها بنر فعال سایت');
        $view->assertSee('سفارش مستقیم');
        $view->assertSee('x-data="bannerSlider(1)"', false);

        // Arrows and indicator template dots must NOT be rendered when count == 1
        $view->assertDontSee('بنر قبلی');
        $view->assertDontSee('بنر بعدی');
        $view->assertDontSee('template x-for="i in count"', false);
    }

    public function test_frontend_renders_two_banners_with_navigation_and_indicators(): void
    {
        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                ['id' => '1', 'title' => 'اسلاید اول'],
                ['id' => '2', 'title' => 'اسلاید دوم'],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('اسلاید اول');
        $view->assertSee('اسلاید دوم');
        $view->assertSee('x-data="bannerSlider(2)"', false);

        // Navigation elements must be rendered
        $view->assertSee('بنر قبلی');
        $view->assertSee('بنر بعدی');
        $view->assertSee('template x-for="i in count"', false);
    }

    public function test_frontend_renders_five_banners_with_full_slider(): void
    {
        $banners = [];
        for ($i = 1; $i <= 5; $i++) {
            $banners[] = [
                'id' => "slide-{$i}",
                'title' => "عنوان اسلاید {$i}",
                'subtitle' => "توضیحات اسلاید {$i}",
                'cta_text' => "اقدام {$i}",
                'cta_url' => "/page/{$i}",
            ];
        }

        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode($banners, JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('x-data="bannerSlider(5)"', false);
        for ($i = 1; $i <= 5; $i++) {
            $view->assertSee("عنوان اسلاید {$i}");
        }
    }

    public function test_frontend_dynamic_banner_with_desktop_and_mobile_picture_tag(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/dyn_desk.jpg';
        $mobile = 'appearance/dyn_mob.jpg';
        Storage::disk('public')->put($desktop, 'desk');
        Storage::disk('public')->put($mobile, 'mob');

        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                [
                    'id' => 'dyn1',
                    'title' => 'بنر ریسپانسیو پویا',
                    'image_path' => $desktop,
                    'mobile_image_path' => $mobile,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('<picture', false);
        $view->assertSee('<source media="(max-width: 767px)"', false);
        $view->assertSee(asset('storage/' . $mobile), false);
        $view->assertSee(asset('storage/' . $desktop), false);
    }

    public function test_frontend_dynamic_banner_desktop_fallback_when_mobile_is_null(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/desk_only.jpg';
        Storage::disk('public')->put($desktop, 'desk');

        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                [
                    'id' => 'dyn1',
                    'title' => 'فقط دسکتاپ پویا',
                    'image_path' => $desktop,
                    'mobile_image_path' => null,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('<picture', false);
        $view->assertDontSee('<source media="(max-width: 767px)"', false);
        $view->assertSee(asset('storage/' . $desktop), false);
    }

    public function test_frontend_dynamic_image_only_banner_renders_zero_headings_or_overlays(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/img_only.jpg';
        Storage::disk('public')->put($desktop, 'img');

        SiteSetting::create([
            'key' => 'homepage_banners',
            'value' => json_encode([
                [
                    'id' => 'dyn1',
                    'title' => null,
                    'subtitle' => '',
                    'cta_text' => null,
                    'cta_url' => '',
                    'image_path' => $desktop,
                ],
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee(asset('storage/' . $desktop), false);
        $view->assertDontSee('<h2', false);
        $view->assertDontSee('bg-gradient-to-b from-black/40', false);
        $view->assertDontSee(site_setting('site_name', config('app.name')), false);
    }
}
