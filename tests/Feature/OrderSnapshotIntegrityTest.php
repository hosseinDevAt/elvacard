<?php

namespace Tests\Feature;

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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSnapshotIntegrityTest extends TestCase
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

    private function addItem(array $customization = []): void
    {
        app(CartService::class)->addItem([
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

    public function test_order_item_snapshots_catalog_references_at_creation(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($item);

        $this->assertSame($this->product->id, $item->product_id);
        $this->assertSame('کارت فلزی کلاسیک', $item->product_name_snapshot);
        $this->assertSame($this->color->id, $item->color_id);
        $this->assertSame('طلایی', $item->color_name_snapshot);
        $this->assertSame($this->design->id, $item->design_id);
        $this->assertSame('طرح شیر', $item->design_name_snapshot);
        $this->assertSame($this->designImage->id, $item->design_image_id);
        $this->assertSame('designs/lion-gold.png', $item->design_image_path_snapshot);
        $this->assertSame(600000, (int) $item->unit_price_snapshot);
        $this->assertSame(1, (int) $item->quantity);
        $this->assertSame(600000, (int) $item->final_price);
    }

    public function test_new_customization_json_holds_only_customer_fields(): void
    {
        $order = $this->createOrderForUser(User::factory()->create());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame('6274000000000000', $item->customization_json['card_number']);
        $this->assertSame('HOSSEIN REZAIE', $item->customization_json['card_holder_name']);
        $this->assertTrue($item->customization_json['security_cvv_enabled']);
        $this->assertSame('808', $item->customization_json['cvv2']);

        foreach (['product_id', 'color_id', 'design_id', 'design_image_id'] as $relationalKey) {
            $this->assertArrayNotHasKey($relationalKey, $item->customization_json);
        }
    }

    public function test_order_detail_keeps_snapshot_names_after_catalog_mutation(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user);

        $this->color->update(['name' => 'نقره‌ای']);
        $this->design->update(['name' => 'طرح عقاب', 'is_active' => false]);
        $this->product->update(['name' => 'کارت پلاستیکی', 'is_active' => false]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('کارت فلزی کلاسیک');
        $response->assertSee('طلایی');
        $response->assertSee('طرح شیر');
        $response->assertDontSee('نقره‌ای');
        $response->assertDontSee('طرح عقاب');
        $response->assertDontSee('کارت پلاستیکی');
    }

    public function test_order_detail_renders_when_catalog_records_are_missing(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrderForUser($user);

        // Simulate a later hard-delete: with the nullOnDelete FK the id columns
        // become null while the name/path snapshots keep the historical facts.
        OrderItem::query()
            ->where('order_id', $order->id)
            ->update([
                'color_id' => null,
                'design_id' => null,
                'design_image_id' => null,
            ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('طلایی');
        $response->assertSee('طرح شیر');
        $response->assertDontSee('طرح کارت</span> '.$this->design->id);
        $response->assertDontSee('رنگ کارت</span> '.$this->color->id);
    }

    public function test_legacy_order_with_qr_and_positions_renders_without_crash(): void
    {
        $user = User::factory()->create();

        $order = new Order;
        $order->user_id = $user->id;
        $order->customer_name = 'حسین';
        $order->customer_phone = '09120000000';
        $order->total_price = 100000;
        $order->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'product_name_snapshot' => 'کارت قدیمی',
            'unit_price_snapshot' => 100000,
            'quantity' => 1,
            'final_price' => 100000,
            'customization_json' => [
                'card_number' => '6274000000000000',
                'card_holder_name' => 'LEGACY',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
                'security_expiry_enabled' => false,
                'qr_code_enabled' => true,
                'qr_code_path' => 'customizations/qr_codes/legacy.png',
                'positions' => [
                    'card_number' => ['x' => 0.5, 'y' => 0.5],
                    'card_holder_name' => ['x' => 0.2, 'y' => 0.2],
                ],
                'product_id' => $this->product->id,
                'color_id' => $this->color->id,
                'design_id' => $this->design->id,
                'design_image_id' => $this->designImage->id,
            ],
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('6274 0000 0000 0000');
        $response->assertSee('LEGACY');
        $response->assertSee('ثبت نشده');
    }

    public function test_cart_and_checkout_display_live_names_not_raw_ids(): void
    {
        $user = User::factory()->create();
        $this->addItem();

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('طلایی')
            ->assertSee('طرح شیر');

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('طلایی')
            ->assertSee('طرح شیر');
    }
}
