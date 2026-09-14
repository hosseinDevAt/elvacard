<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Exceptions\CartPriceChangedException;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CartCheckoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createCommerceProduct(string $slug, int $price, Color $color, bool $isActive = true): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول تجاری '.$slug,
            'slug' => $slug,
            'base_price' => 100000,
            'is_active' => $isActive,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => true,
        ]);

        return $product;
    }

    private function createColor(string $hex = '#00AAFF'): Color
    {
        return Color::create([
            'name' => 'رنگ تست',
            'code_hex' => $hex,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createBankArtwork(): array
    {
        $category = CateDesign::create([
            'name' => 'دسته بانکی',
            'slug' => 'integrity-bank-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $color = $this->createColor('#FFD700');
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح بانکی',
            'slug' => 'integrity-bank-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/integrity-bank-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی یکپارچگی',
            'slug' => 'integrity-bank-product-'.uniqid(),
            'base_price' => null,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        return [
            'product' => $product,
            'color' => $color,
            'design' => $design,
            'designImage' => $designImage,
        ];
    }

    private function cartService(): CartService
    {
        return app(CartService::class);
    }

    private function addBankItem(array $seed, array $customization = []): void
    {
        $this->cartService()->addItem([
            'product_id' => $seed['product']->id,
            'color_id' => $seed['color']->id,
            'design_id' => $seed['design']->id,
            'design_image_id' => $seed['designImage']->id,
            'quantity' => 2,
            'customization_json' => $customization ?: [
                'card_number' => '6274000000000000',
                'card_holder_name' => 'HOSSEIN REZAIE',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
            ],
        ]);
    }

    private function submitCheckout(): void
    {
        $this->cartService()->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);
    }

    public function test_price_increase_after_add_rejects_checkout_without_order_and_refreshes_snapshots(): void
    {
        $color = $this->createColor();
        $product = $this->createCommerceProduct('integrity-increase-'.uniqid(), 300000, $color);

        $this->cartService()->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 2,
        ]);

        $product->colorPrices()->where('color_id', $color->id)->update(['price' => 350000]);

        try {
            $this->submitCheckout();
            $this->fail('Expected a CartPriceChangedException.');
        } catch (CartPriceChangedException $e) {
            $this->assertStringContainsString('قیمت', $e->getMessage());
        }

        $this->assertSame(0, Order::count(), 'No draft order may be created when the price changed.');
        $this->assertSame(700000, $this->cartService()->getCart()['total_price'], 'Cart snapshot must be refreshed to the new price.');

        $this->submitCheckout();
        $this->assertSame(1, Order::count());
        $this->assertSame(700000, Order::first()->total_price, 'The resubmitted order must use the updated authoritative price.');
    }

    public function test_price_decrease_after_add_rejects_checkout_without_order(): void
    {
        $color = $this->createColor();
        $product = $this->createCommerceProduct('integrity-decrease-'.uniqid(), 300000, $color);

        $this->cartService()->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 1,
        ]);

        $product->colorPrices()->where('color_id', $color->id)->update(['price' => 250000]);

        try {
            $this->submitCheckout();
            $this->fail('Expected a CartPriceChangedException.');
        } catch (CartPriceChangedException $e) {
            $this->assertStringContainsString('قیمت', $e->getMessage());
        }

        $this->assertSame(0, Order::count(), 'No draft order may be created when the price changed, even downward.');
        $this->assertSame(250000, $this->cartService()->getCart()['total_price']);
    }

    public function test_commerce_product_ignores_forged_design_ids(): void
    {
        $color = $this->createColor();
        $product = $this->createCommerceProduct('integrity-forge-'.uniqid(), 200000, $color);

        $this->cartService()->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => 999999,
            'design_image_id' => 888888,
            'quantity' => 1,
            'customization_json' => ['card_number' => '6274000000000000'],
        ]);

        $item = $this->cartService()->getCart()['items'][0];
        $this->assertNull($item['design_id']);
        $this->assertNull($item['design_image_id']);

        $this->submitCheckout();

        $orderItem = Order::first()->items()->first();
        $this->assertNull($orderItem->design_id);
        $this->assertNull($orderItem->design_image_id);
        $this->assertSame([], $orderItem->customization_json);
        $this->assertSame(200000, $orderItem->final_price);
    }

    public function test_card_workflow_without_required_design_is_rejected(): void
    {
        $seed = $this->createBankArtwork();

        try {
            $this->cartService()->addItem([
                'product_id' => $seed['product']->id,
                'color_id' => $seed['color']->id,
                'design_id' => null,
                'design_image_id' => null,
                'quantity' => 1,
            ]);
            $this->fail('A card workflow without a design must be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid product/color/design selection.', $e->getMessage());
        }
    }

    public function test_product_deactivated_after_add_rejects_checkout(): void
    {
        $seed = $this->createBankArtwork();
        $this->addBankItem($seed);

        $seed['product']->update(['is_active' => false]);

        try {
            $this->submitCheckout();
            $this->fail('A deactivated product must reject checkout.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Selected product is not available.', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
    }

    public function test_design_deactivated_after_add_rejects_checkout(): void
    {
        $seed = $this->createBankArtwork();
        $this->addBankItem($seed);

        $seed['design']->update(['is_active' => false]);

        try {
            $this->submitCheckout();
            $this->fail('A deactivated design must reject checkout.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Selected design is not available.', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
    }

    public function test_color_price_row_inactivated_after_add_rejects_checkout(): void
    {
        $seed = $this->createBankArtwork();
        $this->addBankItem($seed);

        ProductColorPrice::where('product_id', $seed['product']->id)
            ->where('color_id', $seed['color']->id)
            ->update(['is_active' => false]);

        try {
            $this->submitCheckout();
            $this->fail('An inactivated color price must reject checkout.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Selected color is not valid for this product.', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
    }

    public function test_design_color_compatibility_revoked_after_add_rejects_checkout(): void
    {
        $seed = $this->createBankArtwork();
        $this->addBankItem($seed);

        DesignColorCompatibility::where('design_image_id', $seed['designImage']->id)
            ->where('card_color_id', $seed['color']->id)
            ->update(['is_allowed' => false]);

        try {
            $this->submitCheckout();
            $this->fail('A revoked compatibility must reject checkout.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('not compatible', $e->getMessage());
        }

        $this->assertSame(0, Order::count());
    }

    public function test_order_total_uses_authoritative_state_not_tampered_session_amounts(): void
    {
        $color = $this->createColor();
        $product = $this->createCommerceProduct('integrity-total-'.uniqid(), 500000, $color);

        $this->cartService()->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 3,
        ]);

        $order = $this->cartService()->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $this->assertSame(1500000, $order->total_price, 'Order total must equal the authoritative price multiplied by quantity.');

        $this->cartService()->clear();
        $this->cartService()->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 1,
        ]);

        $cart = $this->cartService()->getCart();
        $cart['items'][0]['unit_price_snapshot'] = 1000;
        $cart['items'][0]['final_price'] = 1000;
        session(['cart' => $cart]);

        try {
            $this->cartService()->createDraftOrder([
                'customer_name' => 'حسین',
                'customer_phone' => '09120000000',
            ]);
            $this->fail('A tampered session price must never be charged.');
        } catch (CartPriceChangedException $e) {
            $this->assertStringContainsString('قیمت', $e->getMessage());
        }

        $this->assertSame(1, Order::count(), 'Only the legitimate order may exist; the tampered one must be rejected.');
        $this->assertSame(1500000, Order::first()->total_price);
    }
}
