<?php

namespace Tests\Feature\Admin;

use App\Enums\ArticleStatusEnum;
use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ArticleManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Admin\PageManager;
use App\Livewire\Admin\ProductManager;
use App\Models\Article;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StoredFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(): CateDesign
    {
        return CateDesign::create([
            'name' => 'پاکسازی',
            'slug' => 'cleanup-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(): Color
    {
        return Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function design(): Design
    {
        return Design::create([
            'cate_design_id' => $this->category()->id,
            'name' => 'طرح پاکسازی',
            'slug' => 'cleanup-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function image(string $path, ?Design $design = null, ?Color $color = null): DesignImage
    {
        return DesignImage::create([
            'design_id' => ($design ?? $this->design())->id,
            'color_id' => ($color ?? $this->color())->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function product(string $mainImage = 'products/main.png', string $ogImage = 'products/og.png'): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'محصول پاکسازی',
            'slug' => 'cleanup-product-'.uniqid(),
            'base_price' => 100000,
            'main_image' => $mainImage,
            'og_image' => $ogImage,
            'is_active' => false,
        ]);
    }

    public function test_deleting_design_image_removes_its_storage_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/cleanup.png', 'data');

        $design = $this->design();
        $this->image('designs/keep.png', $design);
        $image = $this->image('designs/cleanup.png', $design);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $image->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('designs/cleanup.png');
    }

    public function test_deleting_a_lone_design_image_via_wizard_removes_its_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/wizard.png', 'data');

        $design = $this->design();
        $image = $this->image('designs/wizard.png', $design);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class, ['designId' => $design->id])
            ->call('deleteImage', $image->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing('designs/wizard.png');
    }

    public function test_deleting_a_design_image_keeps_a_shared_referenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('designs/shared.png', 'data');

        $other = $this->image('designs/shared.png');
        $toDelete = $this->image('designs/shared.png', $other->design);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $toDelete->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $toDelete->id]);
        Storage::disk('public')->assertExists('designs/shared.png');
    }

    public function test_deleting_a_product_removes_stored_asset_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/main.png', 'data');
        Storage::disk('public')->put('products/og.png', 'data');

        $product = $this->product();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $product->id)
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('products/main.png');
        Storage::disk('public')->assertMissing('products/og.png');
    }

    public function test_deleting_an_article_removes_its_cover_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('articles/cover.png', 'data');

        $article = Article::create([
            'title' => 'مقاله پاکسازی',
            'slug' => 'cleanup-article',
            'content' => 'محتوا',
            'cover_image' => 'articles/cover.png',
            'status' => ArticleStatusEnum::DRAFT->value,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ArticleManager::class)
            ->call('delete', $article->id)
            ->assertOk();

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        Storage::disk('public')->assertMissing('articles/cover.png');
    }

    public function test_deleting_a_page_removes_its_image(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pages/about.png', 'data');

        $page = Page::create([
            'page_type' => 'about',
            'title' => 'درباره ما',
            'slug' => 'cleanup-page',
            'content' => 'محتوا',
            'image_path' => 'pages/about.png',
        ]);

        Livewire::actingAs($this->admin())
            ->test(PageManager::class)
            ->call('delete', $page->id)
            ->assertOk();

        $this->assertDatabaseMissing('pages', ['id' => $page->id]);
        Storage::disk('public')->assertMissing('pages/about.png');
    }

    public function test_deleting_an_entity_with_no_stored_file_does_not_error(): void
    {
        Storage::fake('public');

        $image = $this->image('designs/never-uploaded.png');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $image->id)
            ->assertOk();
    }

    public function test_cleanup_never_touches_local_disk_receipts(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        Storage::disk('local')->put('receipts/receipt-1.pdf', 'data');

        $product = $this->product('products/main.png', 'products/og.png');
        Storage::disk('public')->put('products/main.png', 'data');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $product->id)
            ->assertOk();

        Storage::disk('local')->assertExists('receipts/receipt-1.pdf');
        Storage::disk('public')->assertMissing('products/main.png');
    }
}
