<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
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
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemWorkflowSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CateDesign::create([
            'name' => 'کلاسیک',
            'slug' => 'classic',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی کلاسیک',
            'slug' => 'classic-metal',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $this->color = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح شیر',
            'slug' => 'lion-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/lion-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    private function addItem(array $customization = [], array $extraPayload = []): void
    {
        app(CartService::class)->addItem($extraPayload + [
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => $customization ?: [
                'card_number' => '6274000000000000',
                'card_holder_name' => 'HOSSEIN REZAIE',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
            ],
        ]);
    }

    private function createOrderForUser(User $user): Order
    {
        $this->addItem();

        return app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], $user->id);
    }

    public function test_bank_card_product_snapshots_bank_card_workflow(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->customization_workflow);
    }

    public function test_commerce_only_product_snapshots_null_workflow(): void
    {
        $commerce = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کارت ساده',
            'slug' => 'plain-card',
            'base_price' => 200000,
            'is_active' => true,
        ]);

        app(CartService::class)->addItem([
            'product_id' => $commerce->id,
            'quantity' => 1,
        ]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], User::factory()->create()->id);

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($item);
        $this->assertNull($item->customization_workflow);
    }

    public function test_order_item_workflow_is_an_immutable_historical_snapshot(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->product->update([
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);
        $this->product->update([
            'customization_workflow' => null,
        ]);

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->fresh()->customization_workflow);
    }

    public function test_workflow_is_resolved_server_side_not_from_customization_json(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->addItem([
            'card_number' => '6274000000000000',
            'card_holder_name' => 'HOSSEIN REZAIE',
            'security_cvv_enabled' => true,
            'cvv2' => '808',
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);

        $tamperedOrder = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], User::factory()->create()->id);

        $tamperedItem = OrderItem::query()->where('order_id', $tamperedOrder->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $tamperedItem->customization_workflow);
        $this->assertArrayNotHasKey('customization_workflow', $tamperedItem->customization_json);
        $this->assertSame(600000, (int) $tamperedItem->final_price);
    }

    public function test_top_level_workflow_in_cart_payload_is_ignored(): void
    {
        $this->addItem([], [
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);

        $cart = app(CartService::class)->getCart();

        $this->assertArrayNotHasKey('customization_workflow', $cart['items'][0]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], User::factory()->create()->id);

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->customization_workflow);
    }

    public function test_legacy_classification_uses_json_evidence_only(): void
    {
        foreach ([
            ['card_number' => '6274000000000000'],
            ['qr_code_enabled' => true],
            ['positions' => ['card_number' => ['x' => 0.5, 'y' => 0.5]]],
            ['security_expiry_enabled' => false],
        ] as $customization) {
            $this->assertSame(
                CustomizationWorkflowEnum::BANK_CARD,
                CustomizationWorkflowRegistry::classifyLegacyCustomization($customization)
            );
        }

        $this->assertNull(CustomizationWorkflowRegistry::classifyLegacyCustomization([
            'product_id' => 1,
            'color_id' => 1,
            'design_id' => 1,
            'design_image_id' => 1,
        ]));

        $this->assertNull(CustomizationWorkflowRegistry::classifyLegacyCustomization([]));
    }

    public function test_existing_order_snapshot_columns_stay_intact(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame('کارت فلزی کلاسیک', $item->product_name_snapshot);
        $this->assertSame('طلایی', $item->color_name_snapshot);
        $this->assertSame('طرح شیر', $item->design_name_snapshot);
        $this->assertSame('designs/lion-gold.png', $item->design_image_path_snapshot);
        $this->assertSame('6274000000000000', $item->customization_json['card_number']);
        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $item->customization_workflow);
    }
}
