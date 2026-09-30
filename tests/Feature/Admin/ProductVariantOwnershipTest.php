<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A variant price row and its gallery images belong to exactly one Store
 * product. While an admin has one product open in the form, no variant action
 * may reach another product's rows - not through the rendered UI, and not
 * through a crafted /livewire/update call.
 *
 * Each case asserts the persisted state, because that is the property that
 * actually matters: a rejected action must leave the other product untouched.
 */
class ProductVariantOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function color(string $name): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function storeProduct(string $name): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => 'variant-ownership-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ]);
    }

    /**
     * Product A is the one opened in the form; product B owns the row and the
     * image that every test below tries to reach from A.
     *
     * @return array{0: Product, 1: Product, 2: ProductColorPrice, 3: ProductImage}
     */
    private function twoProductsWithAssetsOnTheSecond(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/owned-by-b.png', 'image of product b');

        $productA = $this->storeProduct('کیف الف');
        $productB = $this->storeProduct('کیف ب');
        $colorB = $this->color('نقره‌ای');

        $variantB = ProductColorPrice::create([
            'product_id' => $productB->id,
            'color_id' => $colorB->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $imageB = ProductImage::create([
            'product_id' => $productB->id,
            'color_id' => $colorB->id,
            'image_path' => 'products/owned-by-b.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        return [$productA, $productB, $variantB, $imageB];
    }

    public function test_edit_variant_of_another_product_is_rejected(): void
    {
        [$productA, $productB, $variantB] = $this->twoProductsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $productA->id)
            ->call('editVariant', $variantB->id)
            ->assertSet('editingVariantId', null)
            ->assertSet('showVariantForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $variantB->id,
            'product_id' => $productB->id,
        ]);
    }

    public function test_save_variant_cannot_reparent_another_products_row(): void
    {
        [$productA, $productB, $variantB] = $this->twoProductsWithAssetsOnTheSecond();
        $colorA = $this->color('طلایی');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $productA->id)
            // a crafted call aims the form at product B's row while product A is open
            ->set('editingVariantId', $variantB->id)
            ->set('variantColorId', $colorA->id)
            ->set('variantPrice', 999000)
            ->set('variantIsActive', true)
            ->call('saveVariant');

        // the row still belongs to product B, with its original price and colour
        $this->assertDatabaseHas('product_color_prices', [
            'id' => $variantB->id,
            'product_id' => $productB->id,
            'color_id' => $variantB->color_id,
            'price' => 500000,
        ]);

        // and nothing was created for product A
        $this->assertDatabaseMissing('product_color_prices', [
            'product_id' => $productA->id,
        ]);
    }

    public function test_delete_variant_of_another_product_is_rejected(): void
    {
        [$productA, $productB, $variantB, $imageB] = $this->twoProductsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $productA->id)
            ->call('deleteVariant', $variantB->id);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $variantB->id,
            'product_id' => $productB->id,
        ]);
        $this->assertDatabaseHas('product_images', ['id' => $imageB->id]);
    }

    public function test_set_primary_variant_image_of_another_product_is_rejected(): void
    {
        [$productA, , , $imageB] = $this->twoProductsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $productA->id)
            ->call('setPrimaryVariantImage', $imageB->id);

        $this->assertFalse((bool) $imageB->fresh()->is_primary);
    }

    public function test_delete_variant_image_of_another_product_is_rejected(): void
    {
        [$productA, $productB, , $imageB] = $this->twoProductsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $productA->id)
            ->call('deleteVariantImage', $imageB->id);

        $this->assertDatabaseHas('product_images', [
            'id' => $imageB->id,
            'product_id' => $productB->id,
        ]);
        Storage::disk('public')->assertExists('products/owned-by-b.png');
    }

    public function test_variant_actions_are_rejected_while_no_product_is_open(): void
    {
        [$productA, $productB, $variantB, $imageB] = $this->twoProductsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingVariantId', $variantB->id)
            ->set('variantColorId', $variantB->color_id)
            ->set('variantPrice', 111000)
            ->call('editVariant', $variantB->id)
            ->call('saveVariant')
            ->call('deleteVariant', $variantB->id)
            ->call('setPrimaryVariantImage', $imageB->id)
            ->call('deleteVariantImage', $imageB->id)
            ->assertSet('showVariantForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $variantB->id,
            'product_id' => $productB->id,
            'price' => 500000,
        ]);
        $this->assertDatabaseHas('product_images', ['id' => $imageB->id]);
        $this->assertDatabaseHas('products', ['id' => $productA->id]);
        Storage::disk('public')->assertExists('products/owned-by-b.png');
    }

    public function test_the_open_products_own_variant_is_still_editable(): void
    {
        Storage::fake('public');

        $product = $this->storeProduct('کیف الف');
        $color = $this->color('طلایی');
        $variant = ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->call('editVariant', $variant->id)
            ->assertSet('editingVariantId', $variant->id)
            ->assertSet('showVariantForm', true)
            ->set('variantPrice', 750000)
            ->call('saveVariant');

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $variant->id,
            'product_id' => $product->id,
            'price' => 750000,
        ]);
    }

    public function test_delete_variant_of_the_open_product_still_works(): void
    {
        Storage::fake('public');

        $product = $this->storeProduct('کیف الف');
        $color = $this->color('طلایی');
        $variant = ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->call('deleteVariant', $variant->id);

        $this->assertDatabaseMissing('product_color_prices', ['id' => $variant->id]);
    }
}
