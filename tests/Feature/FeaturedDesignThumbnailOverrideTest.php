<?php

namespace Tests\Feature;

use App\Livewire\Admin\DesignWizard;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\User;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FeaturedDesignThumbnailOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private CateDesign $category;
    private Color $color1;
    private Color $color2;
    private string $color1Path = 'designs/color1.png';
    private string $color2Path = 'designs/color2.png';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $this->category = CateDesign::create([
            'name' => 'دسته‌بندی پرطرفدار',
            'slug' => 'featured-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color1 = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'color_code' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color2 = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Place physical dummy color images on disk
        Storage::disk('public')->put($this->color1Path, 'dummy-color-1');
        Storage::disk('public')->put($this->color2Path, 'dummy-color-2');
    }

    private function createPopularDesignWithColors(bool $isPopular = true, ?string $customThumb = null): Design
    {
        $design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح محبوب تستی',
            'slug' => 'featured-test-'.uniqid(),
            'is_active' => true,
            'is_popular' => $isPopular,
            'workspace_visibility' => 'both',
            'featured_image_path' => $customThumb,
            'sort_order' => 1,
        ]);

        $img1 = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color1->id,
            'image_path' => $this->color1Path,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $img2 = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color2->id,
            'image_path' => $this->color2Path,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $img1->id,
            'card_color_id' => $this->color1->id,
            'is_allowed' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $img2->id,
            'card_color_id' => $this->color2->id,
            'is_allowed' => true,
        ]);

        return $design->fresh();
    }

    /**
     * Requirement 1: طرح پرطرفدار بدون تصویر اختصاصی، تصویر آخرین/اولین رنگ فعال را نمایش می‌دهد.
     */
    public function test_featured_design_without_custom_thumbnail_displays_automatic_color_image(): void
    {
        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: null);

        $this->assertTrue($design->is_popular);
        $this->assertNull($design->featured_image_path);
        $this->assertFalse($design->hasCustomFeaturedThumbnail());
        $this->assertFalse($design->isUsingCustomFeaturedThumbnail());

        // Central resolution method returns the color image path
        $resolved = $design->resolveFeaturedThumbnailPath();
        $this->assertSame($this->color1Path, $resolved);

        // Catalog service pagination for popular designs also returns the color image path
        $catalog = app(DesignCatalogService::class)->paginatePopularPublic();
        $item = collect($catalog->items())->firstWhere('id', $design->id);
        $this->assertNotNull($item);
        $this->assertSame($this->color1Path, $item['preview_image_path']);
    }

    /**
     * Requirement 2: طرح پرطرفدار با تصویر اختصاصی، تصویر اختصاصی را نمایش می‌دهد.
     */
    public function test_featured_design_with_custom_thumbnail_displays_custom_thumbnail(): void
    {
        $customThumbPath = 'designs/featured/custom-cover.webp';
        Storage::disk('public')->put($customThumbPath, 'dummy-custom-cover');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        $this->assertTrue($design->is_popular);
        $this->assertSame($customThumbPath, $design->featured_image_path);
        $this->assertTrue($design->hasCustomFeaturedThumbnail());
        $this->assertTrue($design->isUsingCustomFeaturedThumbnail());

        // Central resolution prioritizes custom thumbnail
        $this->assertSame($customThumbPath, $design->resolveFeaturedThumbnailPath());

        // Catalog service pagination reflects custom thumbnail
        $catalog = app(DesignCatalogService::class)->paginatePopularPublic();
        $item = collect($catalog->items())->firstWhere('id', $design->id);
        $this->assertNotNull($item);
        $this->assertSame($customThumbPath, $item['preview_image_path']);
    }

    /**
     * Requirement 3: حذف تصویر اختصاصی باعث بازگشت به تصویر خودکار می‌شود.
     */
    public function test_removing_custom_thumbnail_restores_automatic_color_image(): void
    {
        $this->actingAs($this->admin);

        $customThumbPath = 'designs/featured/delete-me.jpg';
        Storage::disk('public')->put($customThumbPath, 'dummy-to-delete');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);
        $this->assertSame($customThumbPath, $design->resolveFeaturedThumbnailPath());

        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->call('removeFeaturedImage')
            ->assertSet('featuredImagePath', null);

        $design->refresh();
        $this->assertNull($design->featured_image_path);
        Storage::disk('public')->assertMissing($customThumbPath);

        // Automatic fallback restored
        $this->assertSame($this->color1Path, $design->resolveFeaturedThumbnailPath());
    }

    /**
     * Requirement 4: جایگزینی تصویر اختصاصی به‌درستی انجام می‌شود و فایل قبلی پاک می‌گردد.
     */
    public function test_replacing_custom_thumbnail_updates_file_and_deletes_old_file(): void
    {
        $this->actingAs($this->admin);

        $oldThumbPath = 'designs/featured/old-thumb.png';
        Storage::disk('public')->put($oldThumbPath, 'old-thumb-content');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $oldThumbPath);
        Storage::disk('public')->assertExists($oldThumbPath);

        $newFile = UploadedFile::fake()->image('brand-new.webp', 800, 600);

        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('featuredImageUpload', $newFile)
            ->call('next');

        $design->refresh();
        $this->assertNotNull($design->featured_image_path);
        $this->assertNotSame($oldThumbPath, $design->featured_image_path);

        // Old file deleted, new file exists
        Storage::disk('public')->assertMissing($oldThumbPath);
        Storage::disk('public')->assertExists($design->featured_image_path);
        $this->assertSame($design->featured_image_path, $design->resolveFeaturedThumbnailPath());
    }

    /**
     * Requirement 5: طرح غیرپرطرفدار به‌اشتباه تصویر اختصاصی Featured دریافت نمی‌کند.
     */
    public function test_non_featured_design_does_not_use_custom_featured_thumbnail(): void
    {
        $customThumbPath = 'designs/featured/dormant.png';
        Storage::disk('public')->put($customThumbPath, 'dormant-thumb');

        // Design with is_popular = false
        $design = $this->createPopularDesignWithColors(isPopular: false, customThumb: $customThumbPath);

        $this->assertFalse($design->is_popular);
        $this->assertFalse($design->isUsingCustomFeaturedThumbnail());

        // Central resolution refuses custom featured thumbnail when is_popular = false
        $this->assertSame($this->color1Path, $design->resolveFeaturedThumbnailPath());

        // In standard catalog pagination, preview image remains the color image
        $catalog = app(DesignCatalogService::class)->paginatePublic();
        $item = collect($catalog->items())->firstWhere('id', $design->id);
        $this->assertNotNull($item);
        $this->assertSame($this->color1Path, $item['preview_image_path']);
    }

    /**
     * Requirement 6: تصاویر رنگ‌های طرح بدون تغییر باقی می‌مانند.
     */
    public function test_design_color_images_remain_unaltered_by_featured_thumbnail(): void
    {
        $this->actingAs($this->admin);

        $customThumbPath = 'designs/featured/promo.png';
        Storage::disk('public')->put($customThumbPath, 'promo-data');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        // Ensure both design images still exist and have their exact paths
        $this->assertCount(2, $design->images);
        $this->assertSame($this->color1Path, $design->images()->where('color_id', $this->color1->id)->value('image_path'));
        $this->assertSame($this->color2Path, $design->images()->where('color_id', $this->color2->id)->value('image_path'));

        // Delete featured thumbnail and re-verify color images
        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->call('removeFeaturedImage');

        $design->refresh();
        $this->assertCount(2, $design->images);
        $this->assertSame($this->color1Path, $design->images()->where('color_id', $this->color1->id)->value('image_path'));
        $this->assertSame($this->color2Path, $design->images()->where('color_id', $this->color2->id)->value('image_path'));
    }

    /**
     * Requirement 7: چند رنگ یک طرح همچنان یک تصویر شاخص اختصاصی مشترک دارند.
     */
    public function test_multiple_colors_of_a_design_share_single_custom_featured_thumbnail(): void
    {
        $customThumbPath = 'designs/featured/shared-cover.png';
        Storage::disk('public')->put($customThumbPath, 'shared-cover-content');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        // Multiple distinct colors
        $this->assertGreaterThan(1, $design->images()->count());

        // Exactly one custom thumbnail on the design entity
        $this->assertSame($customThumbPath, $design->featured_image_path);
        $this->assertSame($customThumbPath, $design->resolveFeaturedThumbnailPath());
    }

    /**
     * Requirement 8: صفحه اصلی و صفحه فهرست طرح‌های پرطرفدار از منطق یکسان استفاده می‌کنند.
     */
    public function test_homepage_and_popular_catalog_listing_use_identical_resolution_logic(): void
    {
        $customThumbPath = 'designs/featured/sync.png';
        Storage::disk('public')->put($customThumbPath, 'sync-content');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        // 1. Popular designs catalog listing:
        $catalogService = app(DesignCatalogService::class);
        $popularCatalog = $catalogService->paginatePopularPublic();
        $catalogItem = collect($popularCatalog->items())->firstWhere('id', $design->id);

        $this->assertSame($customThumbPath, $catalogItem['preview_image_path']);

        // 2. Homepage collection resolution:
        $homepageDesigns = $catalogService->visiblePopularDesigns();
        $homepageDesign = $homepageDesigns->firstWhere('id', $design->id);

        $this->assertSame($customThumbPath, $homepageDesign->resolveFeaturedThumbnailPath());
        $this->assertSame($catalogItem['preview_image_path'], $homepageDesign->resolveFeaturedThumbnailPath());
    }

    /**
     * Requirement 9: کاربر غیرمجاز نمی‌تواند تصویر اختصاصی را مدیریت کند.
     */
    public function test_unauthorized_user_cannot_manage_custom_featured_thumbnail(): void
    {
        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: null);

        // Guest is redirected from admin route
        $this->get(route('admin.designs.edit', $design->id))
            ->assertRedirect(route('login'));

        // Customer role is forbidden from admin route
        $this->actingAs($this->customer);
        $this->get(route('admin.designs.edit', $design->id))
            ->assertForbidden();
    }

    /**
     * Requirement 10: فایل نامعتبر و فایل بیش‌ازحد مجاز پذیرفته نمی‌شود.
     */
    public function test_invalid_or_oversized_file_is_rejected_by_validation(): void
    {
        $this->actingAs($this->admin);

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: null);

        // Text file disguised or non-image
        $invalidFile = UploadedFile::fake()->create('malicious.txt', 100);

        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('isPopular', true)
            ->set('featuredImageUpload', $invalidFile)
            ->call('next')
            ->assertHasErrors(['featuredImageUpload']);

        // Oversized file (> 3072 KB)
        $oversizedFile = UploadedFile::fake()->image('huge.png')->size(4000);

        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('isPopular', true)
            ->set('featuredImageUpload', $oversizedFile)
            ->call('next')
            ->assertHasErrors(['featuredImageUpload']);
    }

    /**
     * Requirement 11: حذف یا جایگزینی تصویر باعث حذف فایل تصاویر رنگ‌ها نمی‌شود.
     */
    public function test_deleting_or_replacing_featured_thumbnail_never_deletes_color_image_files(): void
    {
        $this->actingAs($this->admin);

        $customThumbPath = 'designs/featured/thumb-test.png';
        Storage::disk('public')->put($customThumbPath, 'thumb-bytes');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        // Initial check: all files exist
        Storage::disk('public')->assertExists($customThumbPath);
        Storage::disk('public')->assertExists($this->color1Path);
        Storage::disk('public')->assertExists($this->color2Path);

        // Delete custom thumbnail
        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->call('removeFeaturedImage');

        // Custom thumbnail deleted, color images completely safe
        Storage::disk('public')->assertMissing($customThumbPath);
        Storage::disk('public')->assertExists($this->color1Path);
        Storage::disk('public')->assertExists($this->color2Path);

        // Now upload replacement
        $replacementFile = UploadedFile::fake()->image('brand-new.png');
        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('featuredImageUpload', $replacementFile)
            ->call('next');

        $design->refresh();
        Storage::disk('public')->assertExists($design->featured_image_path);
        Storage::disk('public')->assertExists($this->color1Path);
        Storage::disk('public')->assertExists($this->color2Path);
    }

    /**
     * Requirement 12: غیرفعال و فعال‌کردن مجدد گزینه Featured موجب حذف ناخواسته تصویر نمی‌شود.
     */
    public function test_toggling_featured_off_and_on_does_not_accidentally_delete_custom_thumbnail(): void
    {
        $this->actingAs($this->admin);

        $customThumbPath = 'designs/featured/persistent.webp';
        Storage::disk('public')->put($customThumbPath, 'persistent-bytes');

        $design = $this->createPopularDesignWithColors(isPopular: true, customThumb: $customThumbPath);

        // Toggle isPopular to false in wizard and save
        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('isPopular', false)
            ->call('next');

        $design->refresh();
        $this->assertFalse($design->is_popular);
        // Column value and physical file are preserved
        $this->assertSame($customThumbPath, $design->featured_image_path);
        Storage::disk('public')->assertExists($customThumbPath);

        // Re-enable isPopular in wizard and save
        Livewire::test(DesignWizard::class, ['designId' => $design->id])
            ->set('isPopular', true)
            ->call('next');

        $design->refresh();
        $this->assertTrue($design->is_popular);
        $this->assertSame($customThumbPath, $design->featured_image_path);
        Storage::disk('public')->assertExists($customThumbPath);
        $this->assertSame($customThumbPath, $design->resolveFeaturedThumbnailPath());
    }
}
