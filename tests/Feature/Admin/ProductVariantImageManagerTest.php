<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ProductColorPriceManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVariantImageManagerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function product(): Product
    {
        return Product::create([
            'type' => 'standard',
            'customization_workflow' => null,
            'name' => 'محصول گالری',
            'slug' => 'gallery-product-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ]);
    }

    private function color(): Color
    {
        return Color::create([
            'name' => 'آبی',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function saveVariant(Product $product, Color $color, array $uploads = []): int
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $product->id)
            ->set('colorId', $color->id)
            ->set('price', 250000)
            ->set('isActive', true);

        if ($uploads !== []) {
            $component->set('imageUploads', $uploads);
        }

        $component->call('save')->assertHasNoErrors();

        return ProductColorPrice::where('product_id', $product->id)->where('color_id', $color->id)->firstOrFail()->id;
    }

    public function test_guest_cannot_manage_variant_images(): void
    {
        Livewire::test(ProductColorPriceManager::class)->assertStatus(403);
    }

    public function test_admin_uploads_variant_images_and_first_becomes_primary(): void
    {
        Storage::fake('public');

        $product = $this->product();
        $color = $this->color();

        $this->saveVariant($product, $color, [
            UploadedFile::fake()->image('main.png'),
            UploadedFile::fake()->image('gallery.png'),
        ]);

        $images = ProductImage::where('product_id', $product->id)->where('color_id', $color->id)
            ->orderBy('sort_order')
            ->get();

        $this->assertCount(2, $images);
        $this->assertTrue((bool) $images[0]->is_primary);
        $this->assertFalse((bool) $images[1]->is_primary);
        $this->assertSame(1, $images[0]->sort_order);
        $this->assertSame(2, $images[1]->sort_order);

        foreach ($images as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    public function test_admin_sets_primary_and_deletes_variant_image(): void
    {
        Storage::fake('public');

        $product = $this->product();
        $color = $this->color();
        $this->saveVariant($product, $color, [
            UploadedFile::fake()->image('first.png'),
            UploadedFile::fake()->image('second.png'),
        ]);

        $first = ProductImage::where('product_id', $product->id)->orderBy('sort_order')->firstOrFail();
        $second = ProductImage::where('product_id', $product->id)->orderByDesc('sort_order')->firstOrFail();
        $firstPath = $first->image_path;

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('setPrimaryImage', $second->id)
            ->call('deleteImage', $first->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_images', ['id' => $first->id]);
        $this->assertTrue((bool) $second->fresh()->is_primary);
        Storage::disk('public')->assertMissing($firstPath);
    }

    public function test_deleting_a_variant_removes_its_images_and_files(): void
    {
        Storage::fake('public');

        $product = $this->product();
        $color = $this->color();
        $variantId = $this->saveVariant($product, $color, [
            UploadedFile::fake()->image('variant.png'),
        ]);

        $image = ProductImage::where('product_id', $product->id)->where('color_id', $color->id)->firstOrFail();
        $path = $image->image_path;

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $variantId)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_changing_variant_color_reassigns_its_images(): void
    {
        Storage::fake('public');

        $product = $this->product();
        $oldColor = $this->color();
        $newColor = $this->color();
        $this->saveVariant($product, $oldColor, [
            UploadedFile::fake()->image('blue.png'),
        ]);

        $image = ProductImage::where('product_id', $product->id)->where('color_id', $oldColor->id)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('editingId', ProductColorPrice::where('product_id', $product->id)->firstOrFail()->id)
            ->set('productId', $product->id)
            ->set('colorId', $newColor->id)
            ->set('price', 280000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($newColor->id, (int) $image->fresh()->color_id);
        Storage::disk('public')->assertExists($image->fresh()->image_path);
    }
}
