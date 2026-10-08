<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScratchedCardColorFlowTest extends TestCase
{
    use RefreshDatabase;

    private Color $scratchedColor;

    private Color $smoothColor;

    private Product $bankProduct;

    private Product $fuelProduct;

    private CateDesign $category;

    private Design $bankDesign;

    private DesignImage $bankDesignImage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scratchedColor = Color::create([
            'name' => 'طلایی متالیک خش‌دار',
            'code_hex' => '#FFD700',
            'is_scratched' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->smoothColor = Color::create([
            'name' => 'مشکی براق',
            'code_hex' => '#1A1A1A',
            'is_scratched' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->bankProduct = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی هوشمند',
            'slug' => 'bank-card-smart',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->scratchedColor->id,
            'price' => 550000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->smoothColor->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت فلزی',
            'slug' => 'fuel-card-metal',
            'base_price' => 700000,
            'is_active' => true,
        ]);

        // Fuel product configured with the scratched color to test strict isolation
        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->scratchedColor->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        $this->category = CateDesign::create([
            'name' => 'طرح‌های لوکس',
            'slug' => 'luxury-designs',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->bankDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح رویال',
            'slug' => 'royal-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->bankDesignImage = DesignImage::create([
            'design_id' => $this->bankDesign->id,
            'color_id' => $this->scratchedColor->id,
            'image_path' => 'designs/royal.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->bankDesignImage->id,
            'card_color_id' => $this->scratchedColor->id,
            'is_allowed' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->bankDesignImage->id,
            'card_color_id' => $this->smoothColor->id,
            'is_allowed' => true,
        ]);
    }

    public function test_bank_customizer_reflects_scratched_property_in_color_prices(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->assertSet('colorPrices', function ($prices) {
                $scratchedItem = collect($prices)->firstWhere('color_id', $this->scratchedColor->id);
                $smoothItem = collect($prices)->firstWhere('color_id', $this->smoothColor->id);

                return $scratchedItem['is_scratched'] === true
                    && $smoothItem['is_scratched'] === false;
            });
    }

    public function test_bank_customizer_renders_scratched_texture_when_scratched_color_is_selected(): void
    {
        // Select scratched color: texture must be visible
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('selectColor', $this->scratchedColor->id)
            ->assertSee('scratched-metal-pattern')
            ->assertSee('card-surface-scratched-overlay');

        // Select smooth color: texture must NOT be visible
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('selectColor', $this->smoothColor->id)
            ->assertDontSee('scratched-metal-pattern')
            ->assertDontSee('card-surface-scratched-overlay');
    }

    public function test_fuel_customizer_never_renders_scratched_texture_even_if_color_is_scratched(): void
    {
        // Fuel customizer has the scratchedColor configured, but MUST strictly ignore texture
        Livewire::test(ProductCustomizer::class, ['productId' => $this->fuelProduct->id])
            ->assertDontSee('scratched-metal-pattern')
            ->assertDontSee('card-surface-scratched-overlay');
    }

    public function test_cart_service_captures_is_scratched_in_bank_card_snapshot(): void
    {
        $cartService = app(CartService::class);
        $cartService->clear();

        $cartService->addItem([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->scratchedColor->id,
            'design_id' => $this->bankDesign->id,
            'design_image_id' => $this->bankDesignImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_holder_name' => 'کوروش بزرگ',
                'card_number' => '6037997123456789',
            ],
        ]);

        $cart = $cartService->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertTrue($cart['items'][0]['customization_json']['is_scratched']);

        // Create order
        $order = $cartService->createDraftOrder([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09123456789',
        ], null, 'token-test-1234567890123456789012345678901234567890');

        $orderItem = $order->items->first();
        $this->assertNotNull($orderItem);
        $this->assertTrue($orderItem->isScratched());
        $this->assertTrue($orderItem->customization_json['is_scratched']);
    }

    public function test_historical_orders_without_snapshot_key_remain_smooth_and_never_fallback_to_color_model(): void
    {
        $order = new Order();
        $order->customer_name = 'مشتری قدیمی';
        $order->customer_phone = '09123456789';
        $order->total_price = 500000;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->save();

        // Historical order item whose snapshot does NOT have 'is_scratched'
        $historicalItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => 'کارت بانکی',
            'color_id' => $this->scratchedColor->id, // Color model has is_scratched = true!
            'color_name_snapshot' => 'طلایی',
            'design_id' => $this->bankDesign->id,
            'design_name_snapshot' => 'طرح رویال',
            'design_image_id' => $this->bankDesignImage->id,
            'design_image_path_snapshot' => 'designs/royal.png',
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_holder_name' => 'مشتری قدیمی',
            ],
        ]);

        // Even though $this->scratchedColor->is_scratched is true:
        $this->assertTrue($this->scratchedColor->fresh()->is_scratched);
        // The historical order MUST be false!
        $this->assertFalse($historicalItem->isScratched());
    }

    public function test_admin_order_manager_renders_texture_and_badge_for_scratched_order(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $order = new Order();
        $order->customer_name = 'مشتری بافت‌دار';
        $order->customer_phone = '09121112233';
        $order->total_price = 550000;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->save();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => 'کارت بانکی',
            'color_id' => $this->scratchedColor->id,
            'color_name_snapshot' => 'طلایی متالیک',
            'design_id' => $this->bankDesign->id,
            'design_name_snapshot' => 'طرح رویال',
            'design_image_id' => $this->bankDesignImage->id,
            'design_image_path_snapshot' => 'designs/royal.png',
            'unit_price_snapshot' => 550000,
            'quantity' => 1,
            'final_price' => 550000,
            'customization_json' => [
                'card_holder_name' => 'آرش کمانگیر',
                'is_scratched' => true,
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('خش‌دار')
            ->assertSee('card-surface-scratched-overlay');
    }
}
