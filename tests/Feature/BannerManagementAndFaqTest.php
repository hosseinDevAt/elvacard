<?php

namespace Tests\Feature;

use App\Livewire\Admin\AppearanceManager;
use App\Models\FaqItem;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannerManagementAndFaqTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function seedHeaderMenuItem(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);

        MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => 'url',
            'route_key' => 'home',
            'title' => 'خانه',
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_upload_and_save_banner_image(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('bannerTitle1', 'بنر تستی اول')
            ->set('bannerImage1', UploadedFile::fake()->image('banner1.jpg', 1200, 400))
            ->call('save')
            ->assertHasNoErrors();

        $savedBanner = site_setting('homepage_banner_1');
        $this->assertNotNull($savedBanner);
        $this->assertNotEmpty($savedBanner['image_path']);
        Storage::disk('public')->assertExists($savedBanner['image_path']);
    }

    public function test_admin_can_remove_existing_banner_image(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        // 1. Upload and persist a banner image
        $component = Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('bannerTitle1', 'بنر اول')
            ->set('bannerImage1', UploadedFile::fake()->image('banner1.jpg', 1200, 400))
            ->call('save');

        $savedBanner = site_setting('homepage_banner_1');
        $filePath = $savedBanner['image_path'];
        $this->assertNotNull($filePath);
        Storage::disk('public')->assertExists($filePath);

        // 2. Remove the banner image
        $component->call('removeBannerImage', 1)
            ->assertSee('تصویر بنر شماره 1 حذف شد');

        // 3. Verify file deleted from disk
        Storage::disk('public')->assertMissing($filePath);

        // 4. Verify setting updated to null image_path while preserving other attributes
        $updatedBanner = site_setting('homepage_banner_1');
        $this->assertNull($updatedBanner['image_path']);
        $this->assertEquals('بنر اول', $updatedBanner['title']);

        // 5. Verify component state updated
        $this->assertNull($component->get('bannerImagePath1'));
        $this->assertNull($component->get('bannerImage1'));
    }

    public function test_remove_banner_image_preserves_shared_file_if_used_by_another_banner(): void
    {
        Storage::fake('public');
        $sharedPath = 'banners/shared.jpg';
        Storage::disk('public')->put($sharedPath, 'dummy-content');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'title' => 'بنر 1',
                'subtitle' => 'توضیح 1',
                'link' => '/catalog',
                'link_text' => 'مشاهده',
                'image_path' => $sharedPath,
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        SiteSetting::create([
            'key' => 'homepage_banner_2',
            'value' => json_encode([
                'title' => 'بنر 2',
                'subtitle' => 'توضیح 2',
                'link' => '/catalog',
                'link_text' => 'مشاهده',
                'image_path' => $sharedPath,
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class);

        // Remove banner 1 image
        $component->call('removeBannerImage', 1);

        // Shared file should NOT be deleted because banner 2 still uses it
        Storage::disk('public')->assertExists($sharedPath);

        $banner1 = site_setting('homepage_banner_1');
        $banner2 = site_setting('homepage_banner_2');

        $this->assertNull($banner1['image_path']);
        $this->assertEquals($sharedPath, $banner2['image_path']);
    }

    public function test_remove_banner_image_handles_missing_file_gracefully(): void
    {
        Storage::fake('public');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'title' => 'بنر با فایل ناموجود',
                'subtitle' => 'توضیح',
                'link' => '/catalog',
                'link_text' => 'مشاهده',
                'image_path' => 'banners/non-existent.jpg',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->call('removeBannerImage', 1)
            ->assertSee('تصویر بنر شماره 1 حذف شد');

        $banner = site_setting('homepage_banner_1');
        $this->assertNull($banner['image_path']);
    }

    public function test_remove_banner_image_ignores_invalid_index(): void
    {
        Storage::fake('public');

        $component = Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class);

        $component->call('removeBannerImage', 99);
        $component->assertSessionMissing('success');
    }

    public function test_homepage_renders_all_default_banners_when_no_custom_settings(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Slide 1 default title
        $response->assertSee('کارت شخصی فلزی');
        // Slide 2 default title
        $response->assertSee('طراحی کارت اختصاصی');
        // Slide 3 default title
        $response->assertSee('کیفیت تضمینی');
    }

    public function test_faq_item_component_renders_with_smooth_collapse_structure(): void
    {
        $faq = new FaqItem([
            'question' => 'پرسش تستی',
            'answer' => 'پاسخ تستی',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $faq->id = 42;

        $view = $this->blade('<x-faq-item :faq="$faq" />', ['faq' => $faq]);

        $view->assertSee('پرسش تستی');
        $view->assertSee('پاسخ تستی');
        $view->assertSee('x-collapse', false);
        // Ensure inner container wraps answer for clean zero-padding collapse
        $view->assertSee('border-t border-slate-100/80', false);
    }

    public function test_image_only_banner_renders_without_site_name_or_text_content(): void
    {
        Storage::fake('public');
        $imagePath = 'banners/image-only.jpg';
        Storage::disk('public')->put($imagePath, 'dummy-image-data');

        // Intentionally empty text content
        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $imagePath,
                'title' => null,
                'subtitle' => null,
                'cta_text' => null,
                'cta_url' => null,
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        // Image must be rendered
        $view->assertSee(asset('storage/' . $imagePath), false);

        // Site name must NOT be rendered on the banner
        $siteName = site_setting('site_name', config('app.name'));
        $view->assertDontSee($siteName, false);
    }

    public function test_banner_with_custom_text_renders_title_and_subtitle(): void
    {
        Storage::fake('public');
        $imagePath = 'banners/promo.jpg';
        Storage::disk('public')->put($imagePath, 'promo-image');

        SiteSetting::create([
            'key' => 'homepage_banner_1',
            'value' => json_encode([
                'image_path' => $imagePath,
                'title' => 'جشنواره تخفیف بهاره',
                'subtitle' => 'تا پنجاه درصد تخفیف ویژه',
                'cta_text' => 'مشاهده جشنواره',
                'cta_url' => '/catalog/promo',
            ], JSON_UNESCAPED_UNICODE),
            'type' => 'json',
            'group' => 'homepage',
            'is_public' => true,
        ]);

        $view = $this->blade('<x-homepage.banner-slider />');

        $view->assertSee('جشنواره تخفیف بهاره');
        $view->assertSee('تا پنجاه درصد تخفیف ویژه');
        $view->assertSee('مشاهده جشنواره');
        $view->assertSee(asset('storage/' . $imagePath), false);
    }

    public function test_all_image_only_banners_render_zero_headings_or_overlays(): void
    {
        Storage::fake('public');

        for ($i = 1; $i <= 3; $i++) {
            $path = "banners/banner{$i}.jpg";
            Storage::disk('public')->put($path, "content-{$i}");

            SiteSetting::create([
                'key' => "homepage_banner_{$i}",
                'value' => json_encode([
                    'image_path' => $path,
                    'title' => null,
                    'subtitle' => null,
                    'cta_text' => null,
                    'cta_url' => null,
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
                'group' => 'homepage',
                'is_public' => true,
            ]);
        }

        $view = $this->blade('<x-homepage.banner-slider />');

        for ($i = 1; $i <= 3; $i++) {
            $view->assertSee(asset("storage/banners/banner{$i}.jpg"), false);
        }

        // Absolutely no headings or overlays rendered
        $view->assertDontSee('<h2', false);
        $view->assertDontSee('bg-gradient-to-b from-black/40', false);
        $view->assertDontSee(site_setting('site_name', config('app.name')), false);
    }
}
