<?php

namespace Tests\Feature;

use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Admin\AppearanceManager;
use App\Livewire\Admin\DesignColorCompatibilityManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\MenuItemManager;
use App\Livewire\Admin\PageManager;
use App\Models\CateDesign;
use App\Models\Design;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SvgSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UploadAndUrlSecurityTest extends TestCase
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

    private function validPngContent(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    }

    // =========================================================================
    // F9: safe_url tests
    // =========================================================================

    public function test_safe_url_rejects_protocol_relative_and_backslash_variants(): void
    {
        $malicious = [
            '//evil.com',
            '/\evil.com',
            '/\\evil.com',
            '\\evil.com',
            '\\\evil.com',
            '\/evil.com',
            '\evil.com',
            '///evil.com',
            '////evil.com',
            '/\\/evil.com',
            'https://trusted.com\evil.com',
            'http://trusted.com\evil.com',
            'http:evil.com',
            'https:evil.com',
            'https:/evil.com',
            '/\test/page',
            '//\example.org',
        ];

        foreach ($malicious as $url) {
            $this->assertNull(safe_url($url), "safe_url should reject: {$url}");
        }
    }

    public function test_safe_url_rejects_unsafe_schemes_and_control_chars(): void
    {
        $unsafe = [
            'javascript:alert(1)',
            'JAVASCRIPT:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            'file:///etc/passwd',
            "https://example.com/\r\nevil",
            "https://example.com/\tpath",
            "/path\x00withnull",
            '',
            '   ',
            null,
        ];

        foreach ($unsafe as $url) {
            $this->assertNull(safe_url($url), 'safe_url should reject: '.var_export($url, true));
        }
    }

    public function test_safe_url_preserves_valid_internal_and_https_urls(): void
    {
        $valid = [
            '/' => '/',
            '/about' => '/about',
            '/products' => '/products',
            '/products/123' => '/products/123',
            '/catalog/cards?type=metal&sort=desc#spec' => '/catalog/cards?type=metal&sort=desc#spec',
            'https://example.com' => 'https://example.com',
            'https://example.com/' => 'https://example.com/',
            'https://example.com/checkout' => 'https://example.com/checkout',
            'http://example.com/about' => 'http://example.com/about',
            'https://sub.domain.org:8443/api?key=val#hash' => 'https://sub.domain.org:8443/api?key=val#hash',
        ];

        foreach ($valid as $input => $expected) {
            $this->assertSame($expected, safe_url($input), "safe_url should preserve: {$input}");
        }
    }

    public function test_announcement_manager_rejects_malicious_url_variants(): void
    {
        $malicious = [
            '/\evil.com',
            '//evil.com',
            '\\evil.com',
            'javascript:alert(1)',
        ];

        foreach ($malicious as $badUrl) {
            Livewire::actingAs($this->admin())
                ->test(AnnouncementManager::class)
                ->set('title', 'اطلاعیه تست')
                ->set('content', 'متن')
                ->set('link', $badUrl)
                ->call('save')
                ->assertHasErrors(['link']);
        }

        // Valid URLs must pass
        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->set('title', 'اطلاعیه معتبر')
            ->set('content', 'متن معتبر')
            ->set('link', '/products')
            ->call('save')
            ->assertHasNoErrors(['link']);

        Livewire::actingAs($this->admin())
            ->test(AnnouncementManager::class)
            ->set('title', 'اطلاعیه خارجی')
            ->set('content', 'متن معتبر')
            ->set('link', 'https://example.com/promo')
            ->call('save')
            ->assertHasNoErrors(['link']);
    }

    public function test_menu_item_manager_rejects_malicious_url_variants(): void
    {
        $menu = Menu::create(['name' => 'Main', 'location' => 'header']);

        $malicious = [
            '/\evil.com',
            '//evil.com',
            '\\evil.com',
            'javascript:alert(1)',
        ];

        foreach ($malicious as $badUrl) {
            Livewire::actingAs($this->admin())
                ->test(MenuItemManager::class)
                ->set('menuId', $menu->id)
                ->set('itemType', 'url')
                ->set('title', 'منوی مخرب')
                ->set('customUrl', $badUrl)
                ->call('save')
                ->assertHasErrors(['customUrl']);
        }

        // Valid URLs must pass
        Livewire::actingAs($this->admin())
            ->test(MenuItemManager::class)
            ->set('menuId', $menu->id)
            ->set('itemType', 'url')
            ->set('title', 'منوی معتبر')
            ->set('customUrl', '/catalog')
            ->call('save')
            ->assertHasNoErrors(['customUrl']);
    }

    // =========================================================================
    // F8: SVG sanitizer extension / MIME mismatch tests
    // =========================================================================

    public function test_svg_sanitizer_detects_real_svg(): void
    {
        $sanitizer = app(SvgSanitizer::class);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';
        $file = UploadedFile::fake()->createWithContent('graphic.svg', $svgContent);

        $this->assertTrue($sanitizer->isSvg($file));
        $this->assertTrue($sanitizer->isSvg($svgContent));
    }

    public function test_svg_sanitizer_detects_svg_disguised_with_png_extension(): void
    {
        $sanitizer = app(SvgSanitizer::class);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><circle r="10"/></svg>';
        $file = UploadedFile::fake()->createWithContent('malicious.png', $svgContent);

        // Client filename is .png, but MIME/content is SVG
        $this->assertTrue($sanitizer->isSvg($file));
    }

    public function test_svg_sanitizer_detects_png_disguised_with_svg_extension_as_non_svg(): void
    {
        $sanitizer = app(SvgSanitizer::class);

        $pngContent = $this->validPngContent();
        $file = UploadedFile::fake()->createWithContent('photo.svg', $pngContent);

        // Client filename is .svg, but detected MIME and magic bytes are PNG
        $this->assertFalse($sanitizer->isSvg($file));
        $this->assertFalse($sanitizer->isSvg($pngContent));
    }

    public function test_svg_named_png_is_sanitized_and_not_stored_with_active_content(): void
    {
        Storage::fake('public');

        $activeSvg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<script>evil()</script>'
            .'<rect width="20" height="20"/></svg>';

        // Upload SVG disguised as .png to PageManager
        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', 'تست SVG مبدل')
            ->set('content', 'توضیحات')
            ->set('imageUpload', UploadedFile::fake()->createWithContent('picture.png', $activeSvg))
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::where('title', 'تست SVG مبدل')->firstOrFail();
        $storedContent = Storage::disk('public')->get($page->image_path);

        // Active content must have been stripped even though client extension was .png
        $this->assertStringNotContainsString('script', $storedContent);
        $this->assertStringNotContainsString('onload', $storedContent);
        $this->assertStringContainsString('<rect', $storedContent);
    }

    public function test_png_named_svg_is_stored_safely_as_valid_image_without_rejection(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $pngContent = $this->validPngContent();

        // Upload valid PNG named icon.svg to AppearanceManager siteFavicon
        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->createWithContent('icon.svg', $pngContent))
            ->call('save')
            ->assertHasNoErrors();

        $path = SiteSetting::where('key', 'site_favicon')->value('value');
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($pngContent, Storage::disk('public')->get($path));
    }

    public function test_malicious_svg_payload_is_rejected_and_never_stored(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $xxeSvg = '<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><rect width="10" height="10"/></svg>';

        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->createWithContent('xxe.png', $xxeSvg))
            ->call('save')
            ->assertHasErrors('siteFavicon');

        Storage::disk('public')->assertDirectoryEmpty('favicons');
    }

    public function test_valid_non_svg_image_is_preserved_as_is(): void
    {
        Storage::fake('public');

        $png = UploadedFile::fake()->image('normal.png', 10, 10);

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->set('pageType', 'general')
            ->set('title', 'صفحه تصویر عادی')
            ->set('content', 'متن')
            ->set('imageUpload', $png)
            ->call('save')
            ->assertHasNoErrors();

        $page = Page::where('title', 'صفحه تصویر عادی')->firstOrFail();
        Storage::disk('public')->assertExists($page->image_path);
    }

    public function test_admin_svg_upload_and_public_rendering(): void
    {
        Storage::fake('public');
        $this->seedHeaderMenuItem();

        $cleanSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
            .'<circle cx="50" cy="50" r="40" fill="green"/></svg>';

        // Upload clean SVG as favicon
        Livewire::actingAs($this->admin())
            ->test(AppearanceManager::class)
            ->set('siteFavicon', UploadedFile::fake()->createWithContent('clean.svg', $cleanSvg))
            ->call('save')
            ->assertHasNoErrors();

        $path = SiteSetting::where('key', 'site_favicon')->value('value');
        $this->assertNotNull($path);

        // Public homepage renders favicon link pointing to stored file
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee(asset('storage/'.$path), false);
    }

    // =========================================================================
    // F13: unconditional designFilter validation tests
    // =========================================================================

    public function test_design_image_manager_validates_design_filter_unconditionally(): void
    {
        $cat = CateDesign::create(['name' => 'ورزشی', 'slug' => 'sports-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $design = Design::create(['cate_design_id' => $cat->id, 'name' => 'طرح شیر', 'slug' => 'lion-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        // 1. Valid design ID -> passes with no errors
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', $design->id)
            ->assertHasNoErrors(['designFilter'])
            ->assertSet('designFilter', $design->id);

        // 2. Null or empty string -> passes with no errors (resets filter)
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', '')
            ->assertHasNoErrors(['designFilter'])
            ->assertSet('designFilter', null);

        // 3. Falsy edge value: 0 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', 0)
            ->assertHasErrors(['designFilter']);

        // 4. Falsy string edge value: "0" -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', '0')
            ->assertHasErrors(['designFilter']);

        // 5. Negative ID: -1 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', -1)
            ->assertHasErrors(['designFilter']);

        // 6. Non-existent design ID: 999999 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', 999999)
            ->assertHasErrors(['designFilter']);

        // 7. Non-numeric string: "abc" -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->set('designFilter', 'abc')
            ->assertHasErrors(['designFilter']);
    }

    public function test_design_color_compatibility_manager_validates_design_filter_unconditionally(): void
    {
        $cat = CateDesign::create(['name' => 'هنری', 'slug' => 'art-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $design = Design::create(['cate_design_id' => $cat->id, 'name' => 'طرح پروانه', 'slug' => 'butterfly-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        // 1. Valid design ID -> passes with no errors
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', $design->id)
            ->assertHasNoErrors(['designFilter'])
            ->assertSet('designFilter', $design->id);

        // 2. Null or empty string -> passes with no errors
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', '')
            ->assertHasNoErrors(['designFilter'])
            ->assertSet('designFilter', null);

        // 3. Falsy edge value: 0 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', 0)
            ->assertHasErrors(['designFilter']);

        // 4. Falsy string edge value: "0" -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', '0')
            ->assertHasErrors(['designFilter']);

        // 5. Negative ID: -1 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', -1)
            ->assertHasErrors(['designFilter']);

        // 6. Non-existent ID: 999999 -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', 999999)
            ->assertHasErrors(['designFilter']);

        // 7. Non-numeric string: "invalid" -> MUST fail validation
        Livewire::actingAs($this->admin())
            ->test(DesignColorCompatibilityManager::class)
            ->set('designFilter', 'invalid')
            ->assertHasErrors(['designFilter']);
    }
}
