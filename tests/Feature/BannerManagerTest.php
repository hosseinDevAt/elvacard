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

class BannerManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_can_access_banner_manager_route(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.banners'));
        $response->assertOk();
        $response->assertSee('مدیریت بنرهای صفحه اصلی');
    }

    public function test_guest_or_non_admin_cannot_access_banner_manager(): void
    {
        $this->get(route('admin.banners'))->assertRedirect(route('login'));

        $this->actingAs($this->regularUser())
            ->get(route('admin.banners'))
            ->assertForbidden();
    }

    public function test_admin_can_upload_and_save_desktop_banner_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTitle1', 'بنر اول دسکتاپ')
            ->set('bannerImage1', UploadedFile::fake()->image('desktop1.jpg', 1920, 600))
            ->call('save')
            ->assertHasNoErrors();

        $saved = site_setting('homepage_banner_1');
        $this->assertNotNull($saved);
        $this->assertNotEmpty($saved['image_path']);
        Storage::disk('public')->assertExists($saved['image_path']);
    }

    public function test_admin_can_upload_and_save_mobile_banner_image(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTitle1', 'بنر اول موبایل')
            ->set('bannerMobileImage1', UploadedFile::fake()->image('mobile1.jpg', 750, 750))
            ->call('save')
            ->assertHasNoErrors();

        $saved = site_setting('homepage_banner_1');
        $this->assertNotNull($saved);
        $this->assertNotEmpty($saved['mobile_image_path']);
        Storage::disk('public')->assertExists($saved['mobile_image_path']);
    }

    public function test_admin_can_replace_mobile_banner_image(): void
    {
        Storage::fake('public');

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerMobileImage1', UploadedFile::fake()->image('old_mobile.jpg', 750, 750))
            ->call('save');

        $initial = site_setting('homepage_banner_1');
        $oldPath = $initial['mobile_image_path'];
        Storage::disk('public')->assertExists($oldPath);

        // Replace with new mobile image
        $component->set('bannerMobileImage1', UploadedFile::fake()->image('new_mobile.jpg', 750, 750))
            ->call('save');

        $updated = site_setting('homepage_banner_1');
        $newPath = $updated['mobile_image_path'];

        $this->assertNotEquals($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_admin_can_remove_mobile_banner_without_affecting_desktop_image(): void
    {
        Storage::fake('public');

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTitle1', 'تست حذف موبایل')
            ->set('bannerImage1', UploadedFile::fake()->image('desktop.jpg', 1920, 600))
            ->set('bannerMobileImage1', UploadedFile::fake()->image('mobile.jpg', 750, 750))
            ->call('save');

        $saved = site_setting('homepage_banner_1');
        $desktopPath = $saved['image_path'];
        $mobilePath = $saved['mobile_image_path'];

        Storage::disk('public')->assertExists($desktopPath);
        Storage::disk('public')->assertExists($mobilePath);

        // Remove mobile image
        $component->call('removeBannerMobileImage', 1)
            ->assertSee('تصویر موبایل بنر شماره 1 حذف شد.');

        Storage::disk('public')->assertMissing($mobilePath);
        Storage::disk('public')->assertExists($desktopPath);

        $after = site_setting('homepage_banner_1');
        $this->assertNull($after['mobile_image_path']);
        $this->assertEquals($desktopPath, $after['image_path']);
        $this->assertEquals('تست حذف موبایل', $after['title']);
    }

    public function test_admin_can_remove_desktop_banner_without_affecting_mobile_image(): void
    {
        Storage::fake('public');

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTitle1', 'تست حذف دسکتاپ')
            ->set('bannerImage1', UploadedFile::fake()->image('desktop.jpg', 1920, 600))
            ->set('bannerMobileImage1', UploadedFile::fake()->image('mobile.jpg', 750, 750))
            ->call('save');

        $saved = site_setting('homepage_banner_1');
        $desktopPath = $saved['image_path'];
        $mobilePath = $saved['mobile_image_path'];

        Storage::disk('public')->assertExists($desktopPath);
        Storage::disk('public')->assertExists($mobilePath);

        // Remove desktop image
        $component->call('removeBannerImage', 1)
            ->assertSee('تصویر دسکتاپ بنر شماره 1 حذف شد.');

        Storage::disk('public')->assertMissing($desktopPath);
        Storage::disk('public')->assertExists($mobilePath);

        $after = site_setting('homepage_banner_1');
        $this->assertNull($after['image_path']);
        $this->assertEquals($mobilePath, $after['mobile_image_path']);
        $this->assertEquals('تست حذف دسکتاپ', $after['title']);
    }

    public function test_shared_file_protection_works_between_desktop_and_mobile_banners(): void
    {
        Storage::fake('public');
        $sharedPath = 'appearance/shared.jpg';
        Storage::disk('public')->put($sharedPath, 'shared-data');

        // Slot 1 has mobile image pointing to shared.jpg
        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'title' => 'بنر 1',
                'image_path' => null,
                'mobile_image_path' => $sharedPath,
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        // Slot 2 has desktop image pointing to shared.jpg
        SiteSetting::create([
            'key' => 'homepage_banner_2',
            'value' => json_encode([
                'title' => 'بنر 2',
                'image_path' => $sharedPath,
                'mobile_image_path' => null,
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(BannerManager::class);

        // Remove mobile image of slot 1
        $component->call('removeBannerMobileImage', 1);

        // Shared file must remain because slot 2 desktop uses it
        Storage::disk('public')->assertExists($sharedPath);

        $banner1 = site_setting('homepage_banner_1');
        $banner2 = site_setting('homepage_banner_2');

        $this->assertNull($banner1['mobile_image_path']);
        $this->assertEquals($sharedPath, $banner2['image_path']);
    }

    public function test_admin_can_save_valid_custom_colors(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTitle1', 'بنر رنگی')
            ->set('bannerTextColor1', '#ffffff')
            ->set('bannerButtonBgColor1', '#10b981')
            ->set('bannerButtonTextColor1', '#0f172a')
            ->call('save')
            ->assertHasNoErrors();

        $saved = site_setting('homepage_banner_1');
        $this->assertEquals('#ffffff', $saved['text_color']);
        $this->assertEquals('#10b981', $saved['button_bg_color']);
        $this->assertEquals('#0f172a', $saved['button_text_color']);
    }

    public function test_invalid_hex_colors_are_rejected(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerTextColor1', 'blue; background: url(evil.com)')
            ->set('bannerButtonBgColor1', 'rgb(255, 0, 0)')
            ->set('bannerButtonTextColor1', 'invalid-hex')
            ->call('save')
            ->assertHasErrors([
                'bannerTextColor1' => 'regex',
                'bannerButtonBgColor1' => 'regex',
                'bannerButtonTextColor1' => 'regex',
            ]);
    }

    public function test_cta_url_validation_rejects_unsafe_protocols(): void
    {
        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerCtaUrl1', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors('bannerCtaUrl1');

        Livewire::actingAs($this->admin())
            ->test(BannerManager::class)
            ->set('bannerCtaUrl1', '//malicious-domain.com')
            ->call('save')
            ->assertHasErrors('bannerCtaUrl1');
    }

    public function test_public_banner_slider_renders_picture_with_source_when_mobile_image_exists(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/desktop_hero.jpg';
        $mobile = 'appearance/mobile_hero.jpg';
        Storage::disk('public')->put($desktop, 'desktop-data');
        Storage::disk('public')->put($mobile, 'mobile-data');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $desktop,
                'mobile_image_path' => $mobile,
                'title' => 'جشنواره فلزی',
                'subtitle' => 'تخفیف ویژه کارت فلزی',
                'cta_text' => 'خرید آنلاین',
                'cta_url' => '/catalog',
                'text_color' => '#ffffff',
                'button_bg_color' => '#ffde5b',
                'button_text_color' => '#010619',
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

    public function test_public_banner_slider_falls_back_to_desktop_image_when_mobile_image_is_absent(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/desktop_only.jpg';
        Storage::disk('public')->put($desktop, 'desktop-data');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $desktop,
                'mobile_image_path' => null,
                'title' => 'فقط دسکتاپ',
                'subtitle' => 'تست فال‌بک',
                'cta_text' => 'مشاهده',
                'cta_url' => '/catalog',
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

    public function test_existing_banner_data_without_mobile_or_colors_renders_safely_with_defaults(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/legacy.jpg';
        Storage::disk('public')->put($desktop, 'legacy-data');

        // Legacy format without mobile_image_path or colors
        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $desktop,
                'title' => 'بنر قدیمی',
                'subtitle' => 'بدون کلیدهای جدید',
                'cta_text' => 'ادامه',
                'cta_url' => '/test',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        // Should use defaults safely
        $view->assertSee('style="color: #ffffff;"', false);
        $view->assertSee('background-color: #ffde5b', false);
        $view->assertSee('color: #010619', false);
        $view->assertSee(asset('storage/' . $desktop), false);
    }

    public function test_public_banner_slider_applies_custom_colors_safely(): void
    {
        Storage::fake('public');
        $desktop = 'appearance/colored.jpg';
        Storage::disk('public')->put($desktop, 'colored-data');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $desktop,
                'title' => 'بنر رنگ سفارشی',
                'subtitle' => 'توضیحات سفارشی',
                'cta_text' => 'دکمه سبز',
                'cta_url' => '/catalog',
                'text_color' => '#f3f4f6',
                'button_bg_color' => '#10b981',
                'button_text_color' => '#ffffff',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('style="color: #f3f4f6;"', false);
        $view->assertSee('background-color: #10b981; color: #ffffff;', false);
    }

    public function test_image_only_banner_with_mobile_image_remains_strictly_image_only(): void
    {
        Storage::fake('public');

        for ($i = 1; $i <= 3; $i++) {
            $desktop = "appearance/img_only_desktop_{$i}.jpg";
            $mobile = "appearance/img_only_mobile_{$i}.jpg";
            Storage::disk('public')->put($desktop, "desktop-data-{$i}");
            Storage::disk('public')->put($mobile, "mobile-data-{$i}");

            SiteSetting::create([
                'key' => "homepage_banner_{$i}",
                'value' => json_encode([
                    'image_path' => $desktop,
                    'mobile_image_path' => $mobile,
                    'title' => null,
                    'subtitle' => '',
                    'cta_text' => null,
                    'cta_url' => '',
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'group' => 'homepage',
                'is_public' => true,
            ]);
        }

        $view = $this->blade('<x-homepage.banner-slider />');

        // Images present in picture
        $view->assertSee(asset('storage/appearance/img_only_mobile_1.jpg'), false);
        $view->assertSee(asset('storage/appearance/img_only_desktop_1.jpg'), false);

        // Strictly NO text, NO h2, NO dark gradient overlay, NO site name
        $view->assertDontSee('<h2', false);
        $view->assertDontSee('bg-gradient-to-b from-black/40', false);
        $view->assertDontSee(site_setting('site_name', config('app.name')), false);
    }
}
