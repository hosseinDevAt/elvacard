<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\OrderManager;
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
use Livewire\Livewire;
use Tests\TestCase;

class FuelCardSnapshotDisplayTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    private Product $fuelProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'تست',
            'slug' => 'fuel-snapshot-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'آبی سوخت',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح قطره',
            'slug' => 'fuel-snapshot-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/fuel-drop-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت نمایش',
            'slug' => 'fuel-snapshot-product-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'price' => 480000,
            'is_active' => true,
        ]);
    }

    private function createCustomer(): User
    {
        $customer = User::factory()->create();
        $customer->forceFill(['role' => 'customer'])->save();

        return $customer;
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        return $admin;
    }

    private function createFuelOrder(User $user, array $customization = []): Order
    {
        $order = new Order([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
            'shipping_address' => 'تهران',
        ]);

        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->total_price = 480000;
        $order->save();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $this->fuelProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
            'product_name_snapshot' => $this->fuelProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'unit_price_snapshot' => 480000,
            'quantity' => 1,
            'final_price' => 480000,
            'customization_json' => $customization,
        ]);

        return $order;
    }

    private function bankItem(): array
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی نمایش',
            'slug' => 'fuel-snapshot-bank-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        return [$product, $this->color, $this->design, $this->designImage];
    }

    private function createBankOrder(User $user): Order
    {
        [$product] = $this->bankItem();

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274051234567890',
                'card_holder_name' => 'ALI REZA',
                'back_text' => 'BORN TO LEAD',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
            ],
        ]);

        return app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], $user->id);
    }

    public function test_fuel_snapshot_stores_workflow_and_db_authoritative_values(): void
    {
        $order = $this->createFuelOrder($this->createCustomer());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame(CustomizationWorkflowEnum::FUEL_CARD, $item->customization_workflow);
        $this->assertSame($this->fuelProduct->id, $item->product_id);
        $this->assertSame('کارت سوخت نمایش', $item->product_name_snapshot);
        $this->assertSame($this->color->id, $item->color_id);
        $this->assertSame('آبی سوخت', $item->color_name_snapshot);
        $this->assertSame($this->design->id, $item->design_id);
        $this->assertSame('طرح قطره', $item->design_name_snapshot);
        $this->assertSame($this->designImage->id, $item->design_image_id);
        $this->assertSame($this->designImage->image_path, $item->design_image_path_snapshot);
        $this->assertSame(480000, (int) $item->unit_price_snapshot);
        $this->assertSame(1, (int) $item->quantity);
        $this->assertSame(480000, (int) $item->final_price);
        $this->assertSame([], $item->customization_json);
    }

    public function test_fuel_customization_json_is_canonically_empty_and_holds_no_references(): void
    {
        $order = $this->createFuelOrder($this->createCustomer());

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame([], $item->customization_json);
        $this->assertSame([], array_keys($item->customization_json));
    }

    public function test_fuel_snapshot_is_historical_independent_of_catalog_changes(): void
    {
        $user = $this->createCustomer();
        $order = $this->createFuelOrder($user);
        $originalImagePath = $this->designImage->image_path;

        $this->fuelProduct->update(['name' => 'کارت جدید', 'is_active' => false]);
        $this->color->update(['name' => 'قرمز جدید']);
        $this->design->update(['name' => 'طرح جدید', 'is_active' => false]);
        $this->designImage->update(['image_path' => 'designs/changed.png', 'is_active' => false]);

        $item = OrderItem::query()->where('order_id', $order->id)->first();

        $this->assertSame('کارت سوخت نمایش', $item->product_name_snapshot);
        $this->assertSame('آبی سوخت', $item->color_name_snapshot);
        $this->assertSame('طرح قطره', $item->design_name_snapshot);
        $this->assertSame($originalImagePath, $item->design_image_path_snapshot);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('کارت سوخت نمایش');
        $response->assertSee('آبی سوخت');
        $response->assertSee('طرح قطره');
        $response->assertDontSee('کارت جدید');
        $response->assertDontSee('قرمز جدید');
        $response->assertDontSee('طرح جدید');
        $response->assertDontSee('designs/changed.png');
    }

    public function test_customer_fuel_order_displays_fuel_information_only(): void
    {
        $user = $this->createCustomer();
        $order = $this->createFuelOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee(CustomizationWorkflowEnum::FUEL_CARD->faLabel());
        $response->assertSee('کارت سوخت نمایش');
        $response->assertSee('آبی سوخت');
        $response->assertSee('طرح قطره');
        $response->assertSee($this->designImage->image_path);
        $response->assertSee(number_format(480000));
        $response->assertSee($order->status->faLabel());
        $response->assertSee($order->payment_status->faLabel());

        $response->assertDontSee('شماره کارت');
        $response->assertDontSee('نام دارنده کارت');
        $response->assertDontSee('متن پشت کارت');
        $response->assertDontSee('CVV2');
        $response->assertDontSee('تاریخ انقضا');
        $response->assertDontSee('حکاکی');
    }

    public function test_customer_fuel_display_ignores_forged_bank_customization(): void
    {
        $user = $this->createCustomer();
        $order = $this->createFuelOrder($user, [
            'card_number' => '6274051234567890',
            'card_holder_name' => 'FORGED',
            'cvv2' => '808',
            'security_cvv_enabled' => true,
            'expiry_month' => '05',
            'expiry_year' => '29',
        ]);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('آبی سوخت');
        $response->assertDontSee('6274 0512 3456 7890');
        $response->assertDontSee('FORGED');
        $response->assertDontSee('شماره کارت');
        $response->assertDontSee('CVV2');
        $response->assertDontSee('تاریخ انقضا');
    }

    public function test_unauthorized_customer_cannot_view_fuel_order(): void
    {
        $owner = $this->createCustomer();
        $other = $this->createCustomer();
        $order = $this->createFuelOrder($owner);

        $this->actingAs($other)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_fuel_order(): void
    {
        $order = $this->createFuelOrder($this->createCustomer());

        $this->get(route('orders.show', $order))
            ->assertRedirect();
    }

    public function test_admin_fuel_order_renders_without_bank_fields(): void
    {
        $order = $this->createFuelOrder($this->createCustomer());

        $component = Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $component->assertSet('selectedOrderId', $order->id);
        $component->assertSee('مشخصات کارت سوخت');
        $component->assertSee(CustomizationWorkflowEnum::FUEL_CARD->faLabel());
        $component->assertSee('کارت سوخت نمایش');
        $component->assertSee('آبی سوخت');
        $component->assertSee('طرح قطره');
        $component->assertSee($this->designImage->image_path);

        $component->assertDontSee('پارامترهای حکاکی کاربر');
        $component->assertDontSee('2D Snapshot Preview');
        $component->assertDontSee('شماره کارت');
        $component->assertDontSee('نام دارنده کارت');
        $component->assertDontSee('وضعیت CVV2');
        $component->assertDontSee('متن دلخواه پشت');
    }

    public function test_admin_authorization_remains_intact(): void
    {
        $this->createFuelOrder($this->createCustomer());

        $this->actingAs($this->createCustomer())
            ->get(route('admin.orders'))
            ->assertForbidden();

        $this->actingAs($this->createAdmin())
            ->get(route('admin.orders'))
            ->assertOk();
    }

    public function test_bank_customer_order_display_unchanged(): void
    {
        $user = $this->createCustomer();
        $order = $this->createBankOrder($user);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('6274 0512 3456 7890');
        $response->assertSee('ALI REZA');
        $response->assertSee('BORN TO LEAD');
        $response->assertSee('808');
        $response->assertSee('شماره کارت');
        $response->assertSee('نام دارنده کارت');
        $response->assertDontSee(CustomizationWorkflowEnum::FUEL_CARD->faLabel());
        $response->assertDontSee('روش شخصی‌سازی');
    }

    public function test_bank_admin_order_display_unchanged(): void
    {
        $order = $this->createBankOrder($this->createCustomer());

        $component = Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $component->assertSee('پارامترهای حکاکی کاربر');
        $component->assertSee('2D Snapshot Preview');
        $component->assertSee('6274 0512 3456 7890');
        $component->assertSee('ALI REZA');
        $component->assertSee('فعال (مقدار: 808)');
        $component->assertDontSee('مشخصات کارت سوخت');
        $component->assertDontSee(CustomizationWorkflowEnum::FUEL_CARD->faLabel());
    }

    public function test_legacy_bank_json_still_renders_on_customer_and_admin(): void
    {
        $user = $this->createCustomer();

        $order = new Order([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);
        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->total_price = 600000;
        $order->save();

        [$product] = $this->bankItem();

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customization_workflow' => null,
            'product_name_snapshot' => 'کارت قدیمی',
            'unit_price_snapshot' => 600000,
            'quantity' => 1,
            'final_price' => 600000,
            'customization_json' => [
                'card_number' => '6274000000000000',
                'card_holder_name' => 'LEGACY',
                'cvv2' => '808',
                'security_cvv_enabled' => true,
                'qr_code_path' => 'customizations/qr_codes/legacy.png',
                'product_id' => $product->id,
                'color_id' => $this->color->id,
                'design_id' => $this->design->id,
            ],
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('6274 0000 0000 0000')
            ->assertSee('LEGACY')
            ->assertDontSee('مشخصات کارت سوخت');

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('پارامترهای حکاکی کاربر')
            ->assertSee('6274 0000 0000 0000')
            ->assertDontSee('مشخصات کارت سوخت');
    }

    public function test_cart_display_never_assumes_bank_fields(): void
    {
        $items = [[
            'id' => 'fuel-cart-item',
            'product_id' => $this->fuelProduct->id,
            'product_name_snapshot' => 'کارت سوخت نمایش',
            'color_id' => $this->color->id,
            'color_name_snapshot' => 'آبی سوخت',
            'design_id' => $this->design->id,
            'design_name_snapshot' => 'طرح قطره',
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 480000,
            'final_price' => 480000,
            'customization_json' => [],
        ]];

        $response = $this
            ->withSession(['cart' => ['items' => $items]])
            ->get(route('cart.index'));

        $response->assertOk();
        $response->assertSee('کارت سوخت نمایش');
        $response->assertSee('آبی سوخت');
        $response->assertSee('طرح قطره');
        $response->assertSee(number_format(480000));

        $response->assertDontSee('شماره کارت');
        $response->assertDontSee('نام دارنده کارت');
        $response->assertDontSee('حکاکی');
        $response->assertDontSee('CVV2');
    }

    public function test_fuel_is_active_and_public_cart_accepts_valid_payload(): void
    {
        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));

        $cart = app(CartService::class)->addItem([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
        ]);

        $item = $cart['items'][0];

        $this->assertSame($this->color->id, $item['color_id']);
        $this->assertSame($this->designImage->id, $item['design_image_id']);
        $this->assertSame([], $item['customization_json']);
    }
}
