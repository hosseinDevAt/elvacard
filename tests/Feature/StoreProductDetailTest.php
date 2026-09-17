<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductGallery;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private function commerceProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف چرمی لوکس',
            'slug' => 'luxury-bag-'.uniqid(),
            'description' => 'توضیح کوتاه محصول',
            'base_price' => 250000,
            'is_active' => true,
        ], $overrides));
    }

    private function color(string $name = 'طلایی'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function variant(Product $product, Color $color, int $price): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => true,
        ]);
    }

    private function image(Product $product, Color $color, string $path, int $sortOrder, bool $primary = false): ProductImage
    {
        return ProductImage::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'image_path' => $path,
            'sort_order' => $sortOrder,
            'is_primary' => $primary,
        ]);
    }

    private function makeCard(CustomizationWorkflowEnum $workflow): Product
    {
        $category = CateDesign::create([
            'name' => 'دسته تست',
            'slug' => 'detail-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $color = $this->color('نقرهای');

        $product = Product::create([
            'type' => $workflow === CustomizationWorkflowEnum::BANK_CARD
                ? ProductTypeEnum::BANK->value
                : ProductTypeEnum::FUEL->value,
            'customization_workflow' => $workflow->value,
            'name' => $workflow === CustomizationWorkflowEnum::BANK_CARD ? 'کارت بانکی' : 'کارت سوخت',
            'slug' => ($workflow === CustomizationWorkflowEnum::BANK_CARD ? 'bank-' : 'fuel-').uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تست',
            'slug' => 'detail-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/detail.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $product;
    }

    public function test_store_detail_auto_selects_single_color_with_its_price_and_image(): void
    {
        $product = $this->commerceProduct();
        $color = $this->color();
        $this->variant($product, $color, 380000);
        $this->image($product, $color, 'products/gold-variant.png', 1, true);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('افزودن به سبد خرید');
        $response->assertSee('380,000');
        $response->assertSee('<input type="hidden" name="color_id" value="'.$color->id.'">', false);

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertOk()
            ->assertSet('color_id', $color->id)
            ->assertSee(asset('storage/products/gold-variant.png'));

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $color->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertSame($color->id, (int) $cart['items'][0]['color_id']);
        $this->assertSame(380000, $cart['items'][0]['unit_price_snapshot']);
    }

    public function test_color_switch_updates_price_gallery_and_thumbnail_persists_after_interaction(): void
    {
        $product = $this->commerceProduct();
        $gold = $this->color('طلایی');
        $silver = $this->color('نقرهای');
        $this->variant($product, $gold, 300000);
        $this->variant($product, $silver, 420000);
        $this->image($product, $gold, 'products/gold-1.png', 1, true);
        $this->image($product, $gold, 'products/gold-2.png', 2, false);
        $this->image($product, $silver, 'products/silver-1.png', 1, true);

        $component = Livewire::test(ProductGallery::class, ['productId' => $product->id]);

        $component->assertOk()
            ->assertSee(asset('storage/products/gold-1.png'))
            ->assertSee('300,000');

        $component->call('selectImage', 1)
            ->assertSee(asset('storage/products/gold-2.png'));

        $component->call('selectColor', $silver->id)
            ->assertSet('color_id', $silver->id)
            ->assertSee(asset('storage/products/silver-1.png'))
            ->assertSee('420,000')
            ->assertDontSee(asset('storage/products/gold-1.png'));

        $cart = app(CartService::class)->addItem(['product_id' => $product->id, 'color_id' => $silver->id]);
        $this->assertSame(420000, $cart['items'][0]['unit_price_snapshot']);
    }

    public function test_foreign_or_stale_color_is_never_charged(): void
    {
        $product = $this->commerceProduct();
        $ownColor = $this->color('رنگ خود محصول');
        $this->variant($product, $ownColor, 300000);

        $otherProduct = $this->commerceProduct(['name' => 'محصول دیگر']);
        $foreignColor = $this->color('رنگ محصول دیگر');
        $this->variant($otherProduct, $foreignColor, 900000);

        $staleColor = $this->color('رنگ غیرفعال');
        $this->variant($product, $staleColor, 111);
        ProductColorPrice::where('product_id', $product->id)->where('color_id', $staleColor->id)
            ->update(['is_active' => false]);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $foreignColor->id])
            ->assertSessionHasErrors('cart');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $staleColor->id])
            ->assertSessionHasErrors('cart');

        $this->assertSame(0, count(app(CartService::class)->getCart()['items']));
    }

    public function test_store_detail_falls_back_to_untinted_images_then_main_image(): void
    {
        $product = $this->commerceProduct(['main_image' => 'products/commerce-main.png']);
        $gold = $this->color('طلایی');
        $this->variant($product, $gold, 300000);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/untinted.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertSee(asset('storage/products/untinted.png'))
            ->assertDontSee(asset('storage/products/commerce-main.png'));

        ProductImage::where('product_id', $product->id)->delete();

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertSee(asset('storage/products/commerce-main.png'));
    }

    public function test_gallery_mount_rejects_cards_and_unpurchasable_products(): void
    {
        $bank = $this->makeCard(CustomizationWorkflowEnum::BANK_CARD);
        $fuel = $this->makeCard(CustomizationWorkflowEnum::FUEL_CARD);
        $broken = $this->commerceProduct(['base_price' => null]);

        Livewire::test(ProductGallery::class, ['productId' => $bank->id])->assertStatus(404);
        Livewire::test(ProductGallery::class, ['productId' => $fuel->id])->assertStatus(404);
        Livewire::test(ProductGallery::class, ['productId' => $broken->id])->assertStatus(404);
    }

    public function test_store_cards_route_only_ordinary_products_to_product_detail(): void
    {
        $commerce = $this->commerceProduct(['name' => 'کیف ساده', 'slug' => 'plain-bag']);
        $bank = $this->makeCard(CustomizationWorkflowEnum::BANK_CARD);
        $fuel = $this->makeCard(CustomizationWorkflowEnum::FUEL_CARD);

        // The routing hub keeps the custom-design destination for card
        // workflows and the Product Detail destination for ordinary products.
        $this->assertSame(route('custom-card.bank'), $bank->storefrontUrl());
        $this->assertSame(route('custom-card.fuel'), $fuel->storefrontUrl());
        $this->assertSame(route('catalog.products.show', $commerce->slug), $commerce->storefrontUrl());

        // The Store listing is ordinary commerce only: card workflows must not
        // surface here as ordinary Store product cards.
        $response = $this->get(route('catalog.products.index'));

        $response->assertOk();
        $response->assertSee('href="'.route('catalog.products.show', $commerce->slug).'"', false);
        $response->assertDontSee('href="'.route('custom-card.bank').'"', false);
        $response->assertDontSee('href="'.route('custom-card.fuel').'"', false);

        // Opening the ordinary product card renders the Store gallery, never
        // the Custom Design workspace.
        $this->get(route('catalog.products.show', $commerce->slug))
            ->assertOk()
            ->assertSee('product-gallery', false)
            ->assertDontSee('product-customizer', false);
    }

    public function test_selected_color_from_url_is_honored_by_the_gallery(): void
    {
        $product = $this->commerceProduct();
        $gold = $this->color('طلایی');
        $silver = $this->color('نقرهای');
        $this->variant($product, $gold, 300000);
        $this->variant($product, $silver, 420000);
        $this->image($product, $silver, 'products/silver-from-url.png', 1, true);

        $component = Livewire::test(ProductGallery::class, [
            'productId' => $product->id,
            'colorId' => $silver->id,
        ]);

        $component->assertOk()
            ->assertSet('color_id', $silver->id)
            ->assertSee(asset('storage/products/silver-from-url.png'));

        $html = $this->get(route('catalog.products.show', $product->slug).'?color_id='.$silver->id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(asset('storage/products/silver-from-url.png'), $html);
    }

    public function test_gallery_quantity_is_clamped_to_the_1_to_20_range(): void
    {
        $product = $this->commerceProduct();
        $color = $this->color();
        $this->variant($product, $color, 380000);

        $component = Livewire::test(ProductGallery::class, ['productId' => $product->id]);

        $component->assertSet('quantity', 1)
            ->set('quantity', 0)
            ->assertSet('quantity', 1)
            ->set('quantity', 25)
            ->assertSet('quantity', 20)
            ->set('quantity', 1)
            ->assertSet('quantity', 1)
            ->set('quantity', 20)
            ->assertSet('quantity', 20);
    }

    public function test_cart_rejects_out_of_range_quantity_and_keeps_cart_unchanged(): void
    {
        $product = $this->commerceProduct();
        $color = $this->color();
        $this->variant($product, $color, 380000);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 0])
            ->assertSessionHasErrors('quantity');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 21])
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, count(app(CartService::class)->getCart()['items']));

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $color->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        $this->post(route('cart.add'), ['product_id' => $product->id, 'color_id' => $color->id, 'quantity' => 20])
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertSame(20, $cart['items'][0]['quantity']);
        $this->assertSame(380000, $cart['items'][0]['unit_price_snapshot']);
        $this->assertSame(7600000, $cart['items'][0]['final_price']);
    }

    public function test_variant_images_take_precedence_over_untinted_images_when_both_exist(): void
    {
        $product = $this->commerceProduct();
        $gold = $this->color('طلایی');
        $this->variant($product, $gold, 300000);
        $this->image($product, $gold, 'products/gold-a.png', 1, true);
        $this->image($product, $gold, 'products/gold-b.png', 2, false);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/untinted.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertOk()
            ->assertSee(asset('storage/products/gold-a.png'))
            ->assertSee(asset('storage/products/gold-b.png'))
            ->assertDontSee(asset('storage/products/untinted.png'));

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->call('selectImage', 1)
            ->assertSee(asset('storage/products/gold-b.png'));
    }

    public function test_base_price_only_product_is_purchasable_and_charges_base_price(): void
    {
        $product = $this->commerceProduct(['base_price' => 250000]);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('افزودن به سبد خرید');
        $response->assertSee('250,000');
        $response->assertDontSee('name="color_id"');

        Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertOk()
            ->assertSet('color_id', null)
            ->assertSee('250,000');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertNull($cart['items'][0]['color_id']);
        $this->assertSame(250000, $cart['items'][0]['unit_price_snapshot']);
    }

    public function test_stale_variant_gallery_falls_back_to_the_active_variant_for_selection(): void
    {
        $product = $this->commerceProduct();
        $gold = $this->color('طلایی');
        $silver = $this->color('نقرهای');
        $this->variant($product, $gold, 300000);
        $this->variant($product, $silver, 350000);

        $this->image($product, $gold, 'products/gold-a.png', 1, true);
        $this->image($product, $gold, 'products/gold-b.png', 2, false);
        $this->image($product, $gold, 'products/gold-c.png', 3, false);
        $this->image($product, $silver, 'products/silver-a.png', 1, false);
        $this->image($product, $silver, 'products/silver-b.png', 2, false);

        $component = Livewire::test(ProductGallery::class, ['productId' => $product->id])
            ->assertSee(asset('storage/products/gold-a.png'))
            ->call('selectColor', $gold->id)
            ->assertSet('color_id', $gold->id);

        // Variant A becomes inactive/stale during the Livewire lifecycle.
        ProductColorPrice::query()->where('product_id', $product->id)->where('color_id', $gold->id)
            ->update(['is_active' => false]);

        $component
            // A fresh interaction re-resolves the product: the stored selection
            // is left untouched, but the render falls back to silver.
            ->call('selectImage', 0)
            ->assertSet('color_id', $gold->id)
            ->assertDontSee(asset('storage/products/gold-a.png'))
            ->assertDontSee(asset('storage/products/gold-c.png'))
            ->assertSee(asset('storage/products/silver-a.png'))
            // Thumbnail selection operates on silver's effective gallery only,
            // so an out-of-range index clamps to silver's two images.
            ->call('selectImage', 5)
            ->assertSet('selected_image_index', 1)
            ->assertSee(asset('storage/products/silver-b.png'));
    }

    public function test_detail_json_ld_uses_the_effective_storefront_gallery_image(): void
    {
        $product = $this->commerceProduct(['main_image' => 'products/main.png']);
        $gold = $this->color('طلایی');
        $this->variant($product, $gold, 380000);
        $this->image($product, $gold, 'products/gold-variant.png', 1, true);
        $this->image($product, $gold, 'products/gold-second.png', 2, false);

        ProductImage::create([
            'product_id' => $product->id,
            'color_id' => null,
            'image_path' => 'products/untinted.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        $html = $this->get(route('catalog.products.show', $product->slug))
            ->assertOk()
            ->getContent();

        // The structured-data image must be the effective variant gallery image,
        // not main_image and not an untinted image.
        $this->assertStringContainsString('"image":"'.asset('storage/products/gold-variant.png').'"', $html);
        $this->assertStringNotContainsString('"image":"'.asset('storage/products/main.png').'"', $html);
        $this->assertStringNotContainsString('"image":"'.asset('storage/products/untinted.png').'"', $html);
    }

    public function test_detail_json_ld_image_falls_back_to_main_then_og(): void
    {
        $withMain = $this->commerceProduct(['main_image' => 'products/only-main.png']);

        $html = $this->get(route('catalog.products.show', $withMain->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"image":"'.asset('storage/products/only-main.png').'"', $html);

        $ogOnly = $this->commerceProduct(['main_image' => null, 'og_image' => 'products/only-og.png']);

        $html = $this->get(route('catalog.products.show', $ogOnly->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('"image":"'.asset('storage/products/only-og.png').'"', $html);
    }
}
