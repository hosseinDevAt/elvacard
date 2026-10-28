<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CardColorPricingSeederIndependenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function createColor(string $name = 'مشکی براق', string $hex = '#000000'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => $hex,
            'is_active' => true,
        ]);
    }

    private function createDesignFixture(Color $color): array
    {
        $category = CateDesign::create([
            'name' => 'طرح‌های عمومی',
            'slug' => 'general-designs-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح مینیمال',
            'slug' => 'minimal-design-'.uniqid(),
            'is_active' => true,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/minimal.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return [$category, $design, $designImage];
    }

    /**
     * 1. Both workflow options are always available in the UI even when the Store products table has 0 products.
     */
    public function test_1_both_workflow_options_always_available_even_with_zero_store_products(): void
    {
        $this->assertSame(0, Product::count());

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertStatus(200);

        $options = $component->get('productOptions');
        $this->assertCount(2, $options);
        $this->assertSame('bank_card', $options[0]['workflow']);
        $this->assertSame('کارت بانکی', $options[0]['name']);
        $this->assertSame('fuel_card', $options[1]['workflow']);
        $this->assertSame('کارت سوخت', $options[1]['name']);

        // Admin can open create form without error or banner
        $component->call('create')
            ->assertSet('showForm', true)
            ->assertSee('کارت بانکی')
            ->assertSee('کارت سوخت');

        $this->assertSame(0, Product::count());
    }

    /**
     * 2. Rendering the pricing page creates 0 products and 0 price records.
     */
    public function test_2_rendering_pricing_page_creates_zero_products_and_zero_price_records(): void
    {
        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductColorPrice::count());

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        // Access computed properties multiple times
        $options = $component->get('productOptions');
        $this->assertCount(2, $options);
        $component->get('colorOptions');

        // Re-render multiple times
        $component->call('$refresh');
        $component->call('$refresh');

        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductColorPrice::count());
    }

    /**
     * 3. An admin can save a color price for Bank Card (workflow = 'bank_card', product_id = null).
     */
    public function test_3_admin_can_save_bank_card_color_price_with_null_product_id(): void
    {
        $color = $this->createColor('مشکی مات', '#111111');

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('workflow', 'bank_card')
            ->set('colorId', $color->id)
            ->set('price', 850000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'customization_workflow' => 'bank_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 850000,
            'is_active' => true,
        ]);

        $this->assertSame(0, Product::count());
    }

    /**
     * 4. An admin can save a color price for Fuel Card (workflow = 'fuel_card', product_id = null).
     */
    public function test_4_admin_can_save_fuel_card_color_price_with_null_product_id(): void
    {
        $color = $this->createColor('طلایی سوخت', '#ffd700');

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('workflow', 'fuel_card')
            ->set('colorId', $color->id)
            ->set('price', 450000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'customization_workflow' => 'fuel_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        $this->assertSame(0, Product::count());
    }

    /**
     * 5. Changing the price of a color in Bank Card does not affect the price of the same color in Fuel Card, and vice versa.
     */
    public function test_5_changing_bank_card_price_does_not_affect_fuel_card_price_for_same_color(): void
    {
        $color = $this->createColor('نقره‌ای براق', '#c0c0c0');

        // 1. Create Bank Card price
        $bankPrice = ProductColorPrice::create([
            'customization_workflow' => 'bank_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 800000,
            'is_active' => true,
        ]);

        // 2. Create Fuel Card price for the exact same color
        $fuelPrice = ProductColorPrice::create([
            'customization_workflow' => 'fuel_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 400000,
            'is_active' => true,
        ]);

        // 3. Admin updates Bank Card price via manager
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('edit', $bankPrice->id)
            ->set('price', 920000)
            ->call('save')
            ->assertHasNoErrors();

        // 4. Bank Card price is updated; Fuel Card price remains unchanged
        $this->assertSame(920000, $bankPrice->fresh()->price);
        $this->assertSame(400000, $fuelPrice->fresh()->price);

        // 5. Admin updates Fuel Card price
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('edit', $fuelPrice->id)
            ->set('price', 490000)
            ->call('save')
            ->assertHasNoErrors();

        // 6. Fuel Card is updated; Bank Card remains 920000
        $this->assertSame(490000, $fuelPrice->fresh()->price);
        $this->assertSame(920000, $bankPrice->fresh()->price);
    }

    /**
     * 6. Store products cannot be priced through the card-workflow interface.
     */
    public function test_6_store_products_cannot_be_priced_through_card_workflow_interface(): void
    {
        // Standard commerce products in products table
        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف چرمی کارت',
            'slug' => 'leather-card-holder',
            'base_price' => 300000,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        // Only the two dedicated card design workflows are offered
        $options = $component->get('productOptions');
        $this->assertCount(2, $options);
        $this->assertSame(['bank_card', 'fuel_card'], array_column($options, 'workflow'));

        // Invalid workflow value is rejected
        $color = $this->createColor();
        $component->call('create')
            ->set('workflow', 'standard')
            ->set('colorId', $color->id)
            ->set('price', 250000)
            ->call('save')
            ->assertHasErrors(['workflow']);

        $this->assertSame(0, ProductColorPrice::count());
    }

    /**
     * 7. Customer dashboard / customizer displays and uses correct workflow-specific price without Store products.
     */
    public function test_7_customizer_and_checkout_use_workflow_prices_without_store_products(): void
    {
        $this->assertSame(0, Product::count());

        $color = $this->createColor('رزگلد', '#b76e79');
        [$category, $design, $designImage] = $this->createDesignFixture($color);

        // Set workflow-level Bank Card price
        ProductColorPrice::create([
            'customization_workflow' => 'bank_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 880000,
            'is_active' => true,
        ]);

        // 1. Standalone customizer mounts successfully without a Product record
        $customizer = Livewire::test(ProductCustomizer::class, [
            'workflow' => 'bank_card',
        ]);

        $customizer->assertStatus(200);
        $this->assertSame(880000, $customizer->get('colorPrices')[0]['price']);
        $this->assertSame(880000, $customizer->viewData('unitPrice'));

        // 2. Add to cart via CartService directly
        $cartService = app(CartService::class);
        $cartService->addItem([
            'workflow' => 'bank_card',
            'color_id' => $color->id,
            'design_id' => $design->id,
            'design_image_id' => $designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6037991812345678',
                'card_holder' => 'سارا احمدی',
            ],
        ]);

        $cart = $cartService->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertSame('bank_card', $cart['items'][0]['workflow']);
        $this->assertNull($cart['items'][0]['product_id']);
        $this->assertSame(880000, $cart['items'][0]['unit_price_snapshot']);
        $this->assertSame('کارت بانکی', $cart['items'][0]['product_name_snapshot']);

        // 3. Create draft order
        $order = $cartService->createDraftOrder([
            'customer_name' => 'سارا احمدی',
            'customer_phone' => '09121112233',
        ]);

        $this->assertSame(880000, $order->total_price);
        $this->assertCount(1, $order->items);
        $orderItem = $order->items->first();
        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $orderItem->customization_workflow);
        $this->assertNull($orderItem->product_id);
        $this->assertSame(880000, $orderItem->unit_price_snapshot);
        $this->assertSame('کارت بانکی', $orderItem->product_name_snapshot);

        // Entire flow succeeded with 0 records in products table
        $this->assertSame(0, Product::count());
    }

    /**
     * 8. Missing prices are handled correctly (no active price = cannot purchase, no fake fallback/default prices).
     */
    public function test_8_missing_prices_are_handled_correctly_no_fake_defaults(): void
    {
        $this->assertSame(0, ProductColorPrice::count());

        // 1. Storefront /design/bank 404s when no active Bank Card price exists
        $this->get(route('custom-card.bank'))->assertNotFound();

        // 2. Storefront /design/fuel 404s when no active Fuel Card price exists
        $this->get(route('custom-card.fuel'))->assertNotFound();

        // 3. Customizer 404s when mounting workflow with no active prices
        Livewire::test(ProductCustomizer::class, [
            'workflow' => 'bank_card',
        ])->assertStatus(404);
    }

    /**
     * 8b. CartService throws exception if color has no active price.
     */
    public function test_8b_cart_service_rejects_missing_or_inactive_color_price(): void
    {
        $color = $this->createColor();
        [$category, $design, $designImage] = $this->createDesignFixture($color);

        // Inactive price
        ProductColorPrice::create([
            'customization_workflow' => 'bank_card',
            'product_id' => null,
            'color_id' => $color->id,
            'price' => 700000,
            'is_active' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected color is not valid for this product.');

        app(CartService::class)->addItem([
            'workflow' => 'bank_card',
            'color_id' => $color->id,
            'design_id' => $design->id,
            'design_image_id' => $designImage->id,
            'quantity' => 1,
        ]);
    }

    /**
     * 9. Admin authorization and validation continue to work.
     */
    public function test_9_admin_authorization_and_validation_continue_to_work(): void
    {
        // 1. Guest is forbidden
        Livewire::test(ProductColorPriceManager::class)
            ->assertStatus(403);

        // 2. Customer is forbidden
        Livewire::actingAs($this->customer())
            ->test(ProductColorPriceManager::class)
            ->assertStatus(403);

        $color1 = $this->createColor('رنگ ۱', '#111111');
        $color2 = $this->createColor('رنگ ۲', '#222222');

        // 3. Duplicate color in same workflow is rejected
        ProductColorPrice::create([
            'customization_workflow' => 'bank_card',
            'product_id' => null,
            'color_id' => $color1->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('workflow', 'bank_card')
            ->set('colorId', $color1->id)
            ->set('price', 750000)
            ->call('save')
            ->assertHasErrors(['colorId']);

        // 4. Fuel Card single-active-color invariant
        ProductColorPrice::create([
            'customization_workflow' => 'fuel_card',
            'product_id' => null,
            'color_id' => $color1->id,
            'price' => 400000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('workflow', 'fuel_card')
            ->set('colorId', $color2->id)
            ->set('price', 420000)
            ->set('isActive', true)
            ->call('save');

        $this->assertSame(1, ProductColorPrice::fuelActiveCount());
        $this->assertDatabaseMissing('product_color_prices', [
            'customization_workflow' => 'fuel_card',
            'color_id' => $color2->id,
        ]);
    }

    /**
     * 10. Existing store product pricing behavior remains completely unchanged.
     */
    public function test_10_existing_store_product_pricing_behavior_remains_unchanged(): void
    {
        $color = $this->createColor('آبی نفتی', '#000080');

        $storeProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'جا کارتی لوکس چرمی',
            'slug' => 'luxury-leather-wallet-'.uniqid(),
            'base_price' => 250000,
            'is_active' => true,
        ]);

        // Standard store product prices are stored with product_id and customization_workflow = null
        $storePrice = ProductColorPrice::create([
            'product_id' => $storeProduct->id,
            'customization_workflow' => null,
            'color_id' => $color->id,
            'price' => 280000,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cartService->addItem([
            'product_id' => $storeProduct->id,
            'color_id' => $color->id,
            'quantity' => 2,
        ]);

        $cart = $cartService->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertSame($storeProduct->id, $cart['items'][0]['product_id']);
        $this->assertNull($cart['items'][0]['workflow'] ?? null);
        $this->assertSame(280000, $cart['items'][0]['unit_price_snapshot']);
        $this->assertSame(560000, $cart['items'][0]['final_price']);
        $this->assertSame('جا کارتی لوکس چرمی', $cart['items'][0]['product_name_snapshot']);

        $order = $cartService->createDraftOrder([
            'customer_name' => 'رضا حسینی',
            'customer_phone' => '09129998877',
        ]);

        $this->assertSame(560000, $order->total_price);
        $this->assertSame($storeProduct->id, $order->items->first()->product_id);
        $this->assertNull($order->items->first()->customization_workflow);
    }
}
