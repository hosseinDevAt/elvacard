<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Livewire\Catalog\ProductGallery;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductGalleryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_creating_product_with_multiple_gallery_images_and_selected_primary(): void
    {
        Storage::fake('public');

        $img1 = UploadedFile::fake()->image('img1.png');
        $img2 = UploadedFile::fake()->image('img2.png');
        $img3 = UploadedFile::fake()->image('img3.png');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'محصول چند تصویری')
            ->set('basePrice', 150000)
            ->set('isActive', true)
            ->set('galleryUploads', [$img1, $img2, $img3])
            ->set('primaryUploadIndex', 1) // second image is primary
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('name', 'محصول چند تصویری')->firstOrFail();
        $this->assertCount(3, $product->images);

        $primaryImage = $product->images()->where('is_primary', true)->first();
        $this->assertNotNull($primaryImage);
        $this->assertNull($primaryImage->color_id);
        $this->assertEquals($product->main_image, $primaryImage->image_path);

        Storage::disk('public')->assertExists($primaryImage->image_path);
    }

    public function test_editing_product_and_adding_more_gallery_images(): void
    {
        Storage::fake('public');

        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول تک تصویر',
            'slug' => 'single-image-product',
            'base_price' => 200000,
            'is_active' => true,
            'main_image' => 'products/initial.png',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/initial.png',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $newFile = UploadedFile::fake()->image('second.png');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('editingId', $product->id)
            ->set('galleryUploads', [$newFile])
            ->call('save')
            ->assertHasNoErrors();

        $images = ProductImage::where('product_id', $product->id)->get();
        $this->assertCount(2, $images);
        $this->assertEquals('products/initial.png', $product->fresh()->main_image);
    }

    public function test_changing_primary_image_in_existing_gallery(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول آزمایشی',
            'slug' => 'test-product',
            'base_price' => 200000,
            'is_active' => true,
            'main_image' => 'products/img1.png',
        ]);

        $first = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/img1.png',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $second = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/img2.png',
            'sort_order' => 2,
            'is_primary' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->call('setPrimaryProductImage', $second->id);

        $this->assertFalse((bool) $first->fresh()->is_primary);
        $this->assertTrue((bool) $second->fresh()->is_primary);
        $this->assertEquals('products/img2.png', $product->fresh()->main_image);
    }

    public function test_deleting_primary_image_auto_promotes_remaining_image(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول حذفی',
            'slug' => 'delete-test-prod',
            'base_price' => 200000,
            'is_active' => true,
            'main_image' => 'products/img1.png',
        ]);

        $first = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/img1.png',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $second = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/img2.png',
            'sort_order' => 2,
            'is_primary' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->call('deleteProductImage', $first->id);

        $this->assertDatabaseMissing('product_images', ['id' => $first->id]);
        $this->assertTrue((bool) $second->fresh()->is_primary);
        $this->assertEquals('products/img2.png', $product->fresh()->main_image);
    }

    public function test_reordering_gallery_images(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول جابجایی',
            'slug' => 'reorder-prod',
            'base_price' => 200000,
            'is_active' => true,
        ]);

        $first = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/a.png',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $second = ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/b.png',
            'sort_order' => 2,
            'is_primary' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->call('moveProductImageDown', 0);

        $this->assertEquals(2, $first->fresh()->sort_order);
        $this->assertEquals(1, $second->fresh()->sort_order);
    }

    public function test_storefront_gallery_paths_prioritizes_primary_image_and_renders_gallery(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'دستبند هوشمند',
            'slug' => 'smart-bracelet',
            'base_price' => 350000,
            'is_active' => true,
            'main_image' => 'products/main.png',
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/sub1.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/main.png',
            'sort_order' => 2,
            'is_primary' => true,
        ]);

        $paths = $product->galleryPaths(null);
        $this->assertEquals(['products/main.png', 'products/sub1.png'], $paths);

        // Storefront Livewire gallery component mounts and renders both
        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertSee('تصویر 1 از 2')
            ->call('selectImage', 1)
            ->assertSet('selected_image_index', 1);
    }

    public function test_single_image_product_and_no_image_product_fallback(): void
    {
        $single = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'تک تصویر',
            'slug' => 'single-test',
            'base_price' => 100000,
            'is_active' => true,
            'main_image' => 'products/only_one.png',
        ]);

        $this->assertEquals(['products/only_one.png'], $single->galleryPaths(null));

        $empty = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'بدون تصویر',
            'slug' => 'empty-test',
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $this->assertEquals([], $empty->galleryPaths(null));
    }
}
