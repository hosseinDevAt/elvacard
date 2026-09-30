<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Admin\OrderManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\FuelCard\FuelCardCustomization;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * N-Onyx-45 — Commerce Correctness & Fulfillment Integrity
 *
 * F-03: Fuel customization / order identity integrity (cart identity,
 *       server authority, immutable order snapshot).
 * F-02: Fuel fulfillment / admin completeness (every production-required
 *       field readable from the historical order, never from the workspace).
 * F-05: Design deletion / paid order durability (the purchased asset must
 *       survive permitted catalog deletion).
 */
class FuelOrderIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    private Product $fuelProduct;

    private int $unitPrice = 480000;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'دسته یکپارچگی',
            'slug' => 'onyx45-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'آبی سوخت',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        [$this->design, $this->designImage] = $this->createDesignAsset('fuel-onyx45');

        $this->fuelProduct = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت یکپارچگی',
            'slug' => 'onyx45-fuel-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'price' => $this->unitPrice,
            'is_active' => true,
        ]);
    }

    // =========================================================================
    // F-03 — CART IDENTITY
    // =========================================================================

    public function test_two_fuel_lines_with_different_vins_stay_distinct(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);
        $this->addFuel(['vin' => 'NBBBBBBBBBBBBBB22']);

        $items = $this->cartLines();

        $this->assertCount(2, $items, 'Different VINs are two different physical cards and must not collapse.');
        $this->assertSame(['NAAAAAAAAAAAAAAA1', 'NBBBBBBBBBBBBBB22'], $this->vins($items));
    }

    public function test_two_fuel_lines_with_different_plates_stay_distinct(): void
    {
        $this->addFuel(['plate_number' => '11A111']);
        $this->addFuel(['plate_number' => '22B222']);

        $items = $this->cartLines();

        $this->assertCount(2, $items);
        $this->assertSame(
            ['11A111', '22B222'],
            array_map(fn (array $i): string => (string) $i['customization_json']['plate_number'], $items),
        );
    }

    public function test_two_fuel_lines_with_different_owner_data_stay_distinct(): void
    {
        $this->addFuel(['owner_name' => 'علی رضایی', 'car_info' => 'پراید سفید']);
        $this->addFuel(['owner_name' => 'زهرا محمدی', 'car_info' => 'دنا مشکی']);

        $items = $this->cartLines();

        $this->assertCount(2, $items);
        $this->assertSame(
            ['علی رضایی', 'زهرا محمدی'],
            array_map(fn (array $i): string => (string) $i['customization_json']['owner_name'], $items),
        );
    }

    public function test_fuel_lines_with_different_chip_sizes_stay_distinct(): void
    {
        $this->addFuel(['chip_info' => 'small']);
        $this->addFuel(['chip_info' => 'large']);

        $items = $this->cartLines();

        $this->assertCount(2, $items);
        $this->assertSame(
            ['small', 'large'],
            array_map(fn (array $i): string => (string) $i['customization_json']['chip_info'], $items),
        );
    }

    public function test_fuel_lines_with_different_fuel_systems_stay_distinct(): void
    {
        $this->addFuel(['system_name' => 'کارت سوخت ملی', 'system_identifier' => 'NATIONAL-A']);
        $this->addFuel(['system_name' => 'سایر', 'system_identifier' => 'OTHER-B']);

        $items = $this->cartLines();

        $this->assertCount(2, $items);
        $this->assertSame(
            ['NATIONAL-A', 'OTHER-B'],
            array_map(fn (array $i): string => (string) $i['customization_json']['system_identifier'], $items),
        );
    }

    public function test_identical_fuel_configuration_still_merges_into_one_line(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'plate_number' => '11A111'], 1);
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'plate_number' => '11A111'], 2);

        $items = $this->cartLines();

        $this->assertCount(1, $items, 'The exact same configuration is the same product and must merge.');
        $this->assertSame(3, (int) $items[0]['quantity']);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $items[0]['customization_json']['vin']);
        $this->assertSame($this->unitPrice * 3, (int) $items[0]['final_price']);
    }

    public function test_merging_identical_lines_never_drops_the_customization_snapshot(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'chip_info' => 'large'], 1);
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'chip_info' => 'large'], 1);

        $items = $this->cartLines();

        $this->assertCount(1, $items);
        $this->assertSame(
            ['vin' => 'NAAAAAAAAAAAAAAA1', 'chip_info' => 'large'],
            $items[0]['customization_json'],
            'The canonical payload key order is the Fuel allow-list order and is stable.',
        );
    }

    public function test_quantity_update_keeps_the_lines_own_customization(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'plate_number' => '11A111']);
        $line = $this->cartLines()[0];

        app(CartService::class)->updateQuantity((string) $line['id'], 4);

        $updated = $this->cartLines()[0];

        $this->assertSame(4, (int) $updated['quantity']);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $updated['customization_json']['vin']);
        $this->assertSame('11A111', $updated['customization_json']['plate_number']);
        $this->assertSame($this->unitPrice * 4, (int) $updated['final_price']);
    }

    public function test_quantity_update_on_one_line_does_not_touch_its_sibling(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);
        $this->addFuel(['vin' => 'NBBBBBBBBBBBBBB22']);

        $lines = $this->cartLines();
        app(CartService::class)->updateQuantity((string) $lines[0]['id'], 3);

        $after = $this->cartLines();

        $this->assertCount(2, $after);
        $this->assertSame(3, (int) $after[0]['quantity']);
        $this->assertSame(1, (int) $after[1]['quantity']);
        $this->assertSame('NBBBBBBBBBBBBBB22', $after[1]['customization_json']['vin']);
    }

    /**
     * The end-to-end guarantee: two Fuel cards for two different vehicles both
     * reach fulfillment with their own VIN. Before the cart identity fix the
     * second add overwrote the first line's VIN while billing for both.
     */
    public function test_checkout_creates_one_order_item_per_distinct_fuel_configuration(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1', 'plate_number' => '11A111', 'chip_info' => 'small']);
        $this->addFuel(['vin' => 'NBBBBBBBBBBBBBB22', 'plate_number' => '22B222', 'chip_info' => 'large']);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $items = OrderItem::query()->where('order_id', $order->id)->orderBy('id')->get();

        $this->assertCount(2, $items, 'Both vehicles must survive to the order as separate lines.');
        $this->assertSame(
            ['NAAAAAAAAAAAAAAA1', 'NBBBBBBBBBBBBBB22'],
            $items->map(fn (OrderItem $i): string => (string) $i->customization_json['vin'])->all(),
        );
        $this->assertSame(
            ['11A111', '22B222'],
            $items->map(fn (OrderItem $i): string => (string) $i->customization_json['plate_number'])->all(),
        );
        $this->assertSame(
            ['small', 'large'],
            $items->map(fn (OrderItem $i): string => (string) $i->customization_json['chip_info'])->all(),
        );
        $this->assertSame($this->unitPrice * 2, (int) $order->refresh()->total_price);
    }

    public function test_a_merged_fuel_line_is_billed_for_its_whole_quantity(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1'], 3);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(3, (int) $item->quantity);
        $this->assertSame($this->unitPrice * 3, (int) $item->final_price);
    }

    public function test_store_lines_without_customization_still_merge(): void
    {
        $product = $this->createStoreProduct();

        app(CartService::class)->addItem($this->storePayload($product, 1));
        app(CartService::class)->addItem($this->storePayload($product, 2));

        $items = $this->cartLines();

        $this->assertCount(1, $items, 'Ordinary Store commerce lines carry no customization and must merge.');
        $this->assertSame(3, (int) $items[0]['quantity']);
    }

    public function test_bank_lines_with_different_holder_names_stay_distinct(): void
    {
        $product = $this->createBankProduct();

        $this->addBank($product, ['card_holder_name' => 'ALI REZA', 'card_number' => '6274051234567890']);
        $this->addBank($product, ['card_holder_name' => 'ZEHRA', 'card_number' => '6274051234567890']);

        $items = $this->cartLines();

        $this->assertCount(2, $items);
        $this->assertSame(
            ['ALI REZA', 'ZEHRA'],
            array_map(fn (array $i): string => (string) $i['customization_json']['card_holder_name'], $items),
        );
    }

    public function test_identical_bank_lines_still_merge(): void
    {
        $product = $this->createBankProduct();
        $customization = ['card_holder_name' => 'ALI REZA', 'card_number' => '6274051234567890'];

        $this->addBank($product, $customization, 1);
        $this->addBank($product, $customization, 2);

        $items = $this->cartLines();

        $this->assertCount(1, $items);
        $this->assertSame(3, (int) $items[0]['quantity']);
    }

    // =========================================================================
    // F-03 — SERVER AUTHORITY (crafted payloads)
    // =========================================================================

    public function test_crafted_fuel_line_with_a_foreign_design_image_is_rejected(): void
    {
        [$foreignDesign, $foreignImage] = $this->createDesignAsset('foreign', allowed: false);

        $before = $this->cartLines();

        try {
            app(CartService::class)->addItem([
                'product_id' => $this->fuelProduct->id,
                'color_id' => $this->color->id,
                'design_id' => $this->design->id,
                'design_image_id' => $foreignImage->id,
                'quantity' => 1,
                'customization_json' => $this->fuelCustomization(['vin' => 'NAAAAAAAAAAAAAAA1']),
            ]);
            $this->fail('Expected the incompatible design image to be rejected.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertCount(count($before), $this->cartLines(), 'No cart line may be created from a crafted payload.');
    }

    public function test_crafted_fuel_line_with_a_foreign_color_is_rejected(): void
    {
        $otherColor = Color::create([
            'name' => 'قرمز',
            'code_hex' => '#FF0000',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        try {
            app(CartService::class)->addItem([
                'product_id' => $this->fuelProduct->id,
                'color_id' => $otherColor->id,
                'design_id' => $this->design->id,
                'design_image_id' => $this->designImage->id,
                'quantity' => 1,
                'customization_json' => $this->fuelCustomization(['vin' => 'NAAAAAAAAAAAAAAA1']),
            ]);
            $this->fail('Expected a color that does not belong to the Fuel product to be rejected.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertCount(0, $this->cartLines());
    }

    public function test_crafted_fuel_line_cannot_smuggle_bank_or_foreign_keys(): void
    {
        $this->addFuel([
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'card_number' => '6274051234567890',
            'cvv2' => '808',
            'product_id' => 999999,
            'design_id' => 999999,
        ]);

        $customization = $this->cartLines()[0]['customization_json'];

        $this->assertArrayNotHasKey('card_number', $customization);
        $this->assertArrayNotHasKey('cvv2', $customization);
        $this->assertArrayNotHasKey('product_id', $customization);
        $this->assertArrayNotHasKey('design_id', $customization);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $customization['vin']);
    }

    public function test_crafted_fuel_line_with_an_invalid_chip_size_is_rejected(): void
    {
        try {
            app(CartService::class)->addItem([
                'product_id' => $this->fuelProduct->id,
                'color_id' => $this->color->id,
                'design_id' => $this->design->id,
                'design_image_id' => $this->designImage->id,
                'quantity' => 1,
                'customization_json' => $this->fuelCustomization(['chip_info' => 'gigantic']),
            ]);
            $this->fail('Expected an invalid chip size to be rejected.');
        } catch (InvalidArgumentException) {
            // Expected.
        }

        $this->assertCount(0, $this->cartLines());
    }

    public function test_a_short_vin_is_dropped_rather_than_stored_as_identity(): void
    {
        // A rejected VIN must not become a usable identity: two cards whose only
        // difference is an invalid VIN are the same configuration, because an
        // invalid VIN is not stored at all.
        $this->addFuel(['vin' => 'TOOSHORT']);
        $this->addFuel(['vin' => 'ALSOSHORT']);

        $items = $this->cartLines();

        $this->assertCount(1, $items);
        $this->assertArrayNotHasKey('vin', $items[0]['customization_json']);
    }

    public function test_fuel_payload_is_canonicalized_before_it_becomes_cart_identity(): void
    {
        // Persian digits and stray whitespace normalize to one canonical form,
        // so the same physical card entered twice still merges instead of
        // forking into two identical lines.
        $this->addFuel(['plate_number' => '۴۴ب۱۱۱']);
        $this->addFuel(['plate_number' => '  44ب111  ']);

        $items = $this->cartLines();

        $this->assertCount(1, $items);
        $this->assertSame('44ب111', $items[0]['customization_json']['plate_number']);
    }

    // =========================================================================
    // F-03 — END-TO-END: NO ORDER, NO PAYMENT
    // =========================================================================

    public function test_a_tampered_session_cart_produces_no_order_and_no_payment(): void
    {
        // The session cart is client-held storage, so it is treated as fully
        // untrusted. This forges a line that never passed addItem validation:
        // a legitimate Fuel product combined with a design image it is not
        // allowed to use, plus a forged price.
        [$foreignDesign, $foreignImage] = $this->createDesignAsset('forged', allowed: false);

        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);
        $legitimate = $this->cartLines()[0];

        session(['cart' => ['items' => [[
            'product_id' => $legitimate['product_id'],
            'color_id' => $legitimate['color_id'],
            'design_id' => $foreignDesign->id,
            'design_image_id' => $foreignImage->id,
            'quantity' => 1,
            'unit_price' => 1,
            'customization_json' => $legitimate['customization_json'],
        ]]]]);

        $this->submitCheckoutExpectingRejection();

        $this->assertSame(0, Order::count(), 'A tampered cart must not create an order.');
        $this->assertSame(0, OrderItem::count(), 'A tampered cart must not create order items.');
        $this->assertSame(0, Payment::count(), 'A tampered cart must not create a payment.');
    }

    public function test_a_tampered_price_produces_no_order_and_no_payment(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);

        // Forge the real snapshot fields, not an unknown key: checkout must
        // detect the drift from the authoritative price and refuse to order.
        $forged = $this->cartLines()[0];
        $forged['unit_price_snapshot'] = 1;
        $forged['final_price'] = 1;
        session(['cart' => ['items' => [$forged]]]);

        $this->submitCheckoutExpectingRejection();

        $this->assertSame(0, Order::count(), 'A forged cart price must not create an order.');
        $this->assertSame(0, OrderItem::count(), 'A forged cart price must not create order items.');
        $this->assertSame(0, Payment::count(), 'A forged cart price must not create a payment.');
    }

    public function test_an_unknown_forged_price_key_is_ignored_and_the_authoritative_price_wins(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);

        // Injecting a key the server does not recognise must not discount the
        // order: the authoritative price is re-resolved regardless.
        $forged = $this->cartLines()[0];
        $forged['unit_price'] = 1;
        $forged['price'] = 1;
        $forged['discount'] = 999999;
        session(['cart' => ['items' => [$forged]]]);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $this->assertSame($this->unitPrice, $order->total_price, 'No client-supplied price key may influence the order total.');
        $this->assertSame($this->unitPrice, (int) $order->items()->first()->unit_price_snapshot);
    }

    public function test_a_tampered_fuel_payload_produces_no_order_and_no_payment(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);

        // Forge the customization straight into the session, bypassing the
        // sanitizer, to prove checkout re-validates rather than trusting a
        // snapshot that merely came from an authenticated UI.
        $forged = $this->cartLines()[0];
        $forged['customization_json'] = [
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'chip_info' => 'gigantic',
            'card_number' => '6274051234567890',
        ];
        session(['cart' => ['items' => [$forged]]]);

        $this->submitCheckoutExpectingRejection();

        $this->assertSame(0, Order::count(), 'An invalid Fuel payload must not create an order.');
        $this->assertSame(0, OrderItem::count(), 'An invalid Fuel payload must not create order items.');
        $this->assertSame(0, Payment::count(), 'An invalid Fuel payload must not create a payment.');
    }

    public function test_a_genuine_fuel_cart_checkout_creates_exactly_one_order_and_no_forgery(): void
    {
        $this->addFuel(['vin' => 'NAAAAAAAAAAAAAAA1']);

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ]);

        $this->assertSame(1, Order::count());
        $this->assertSame($this->unitPrice, $order->total_price, 'The server-authoritative price must win.');
        $this->assertSame(1, OrderItem::count());
        $this->assertSame('NAAAAAAAAAAAAAAA1', $order->items()->first()->customization_json['vin']);
        $this->assertSame(0, Payment::count(), 'A draft order is not yet paid.');
    }

    // =========================================================================
    // F-03 — ORDER SNAPSHOT IMMUTABILITY
    // =========================================================================

    public function test_paid_order_snapshot_is_never_rebuilt_from_catalog_or_workspace(): void
    {
        $user = $this->createCustomer();
        $order = $this->createPaidFuelOrder($user, [
            'owner_name' => 'علی رضایی',
            'car_info' => 'پراید سفید',
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'system_name' => 'کارت سوخت ملی',
            'system_identifier' => 'NATIONAL-A',
            'plate_number' => '11A111',
            'chip_info' => 'small',
        ]);

        $this->fuelProduct->update(['name' => 'کارت جدید', 'is_active' => false]);
        $this->color->update(['name' => 'قرمز جدید']);
        $this->design->update(['name' => 'طرح جدید', 'is_active' => false]);
        $this->designImage->update(['image_path' => 'designs/changed.png', 'is_active' => false]);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame('کارت سوخت یکپارچگی', $item->product_name_snapshot);
        $this->assertSame('آبی سوخت', $item->color_name_snapshot);
        $this->assertSame('طرح سوخت تست', $item->design_name_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);
        $this->assertSame('علی رضایی', $item->customization_json['owner_name']);

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertDontSee('کارت جدید');
        $response->assertDontSee('طرح جدید');
    }

    // =========================================================================
    // F-02 — FUEL FULFILLMENT / ADMIN COMPLETENESS
    // =========================================================================

    public function test_admin_sees_every_fuel_fulfillment_field_from_the_order_snapshot(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), [
            'owner_name' => 'علی رضایی',
            'car_info' => 'پراید سفید مدل ۱۳۹۸',
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'system_name' => 'کارت سوخت ملی',
            'system_identifier' => 'NATIONAL-A',
            'plate_number' => '11A111',
            'chip_info' => 'small',
        ]);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('اطلاعات مشتری برای تولید کارت سوخت')
            ->assertSee('علی رضایی')
            ->assertSee('پراید سفید مدل ۱۳۹۸')
            ->assertSee('NAAAAAAAAAAAAAAA1')
            ->assertSee('کارت سوخت ملی')
            ->assertSee('NATIONAL-A')
            ->assertSee('11A111')
            ->assertSee('سایز چیپ')
            ->assertSee('کوچک');
    }

    public function test_admin_fuel_fulfillment_preserves_persian_and_long_values(): void
    {
        $longSystem = mb_substr(str_repeat('سامانه-تست-', 6).'پایان', 0, 64);
        $longCar = trim(str_repeat('توضیحات خودرو ', 20));

        $order = $this->createPaidFuelOrder($this->createAdmin(), [
            'owner_name' => 'محمد رضا نوروزی',
            'car_info' => $longCar,
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'system_name' => 'سامانه سوخت مرکزی',
            'system_identifier' => $longSystem,
            'plate_number' => '۴۴ب۲۲۲',
            'chip_info' => 'large',
        ]);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
        $fields = FuelCardCustomization::fulfillmentFields($item->customization_json);

        $this->assertSame('محمد رضا نوروزی', $fields['owner_name']['value']);
        $this->assertSame($longCar, $fields['car_info']['value'], 'Fulfillment must not shorten a long vehicle description.');
        $this->assertSame($longSystem, $fields['system_identifier']['value']);
        $this->assertSame('44ب222', $fields['plate_number']['value'], 'Persian plate digits must canonicalize to ASCII.');
        $this->assertSame('بزرگ', $fields['chip_info']['value']);

        // The same values reach the operator, not just the read model.
        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('محمد رضا نوروزی')
            ->assertSee('NAAAAAAAAAAAAAAA1')
            ->assertSee($longSystem)
            ->assertSee('44ب222')
            ->assertSee('بزرگ');
    }

    public function test_chip_size_is_fulfillment_context_and_not_a_printed_specification(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), ['chip_info' => 'large']);

        $response = Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id);

        $response->assertSee('سایز چیپ')->assertSee('بزرگ');

        // The Bank-only engraved-parameter table is a printed specification and
        // must never be used to present a Fuel card's chip size.
        $response->assertDontSee('پارامترهای حکاکی کاربر');
    }

    public function test_admin_fuel_fulfillment_ignores_forged_non_fuel_keys_in_the_snapshot(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), [
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'card_number' => '6274051234567890',
            'card_holder_name' => 'FORGED',
            'cvv2' => '808',
        ]);

        $response = Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id);

        $response->assertSee('NAAAAAAAAAAAAAAA1');
        $response->assertDontSee('6274051234567890');
        $response->assertDontSee('FORGED');
    }

    public function test_admin_fuel_fulfillment_never_truncates_or_drops_historical_values(): void
    {
        // A snapshot row can legitimately hold values that today's input rules
        // would no longer accept (a longer system identifier, a VIN that is not
        // 17 characters). The fulfillment screen must still show exactly what
        // was purchased instead of shortening it or blanking the field.
        $overLengthIdentifier = str_repeat('SYS-', 30);
        $legacyVin = 'LEGACY-VIN-1234';

        $order = $this->createPaidFuelOrder($this->createAdmin(), [
            'system_identifier' => $overLengthIdentifier,
            'chip_info' => 'small',
        ]);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        // Write the historical row the way a legacy record would look.
        $snapshot = $item->customization_json;
        $snapshot['system_identifier'] = $overLengthIdentifier;
        $snapshot['vin'] = $legacyVin;
        $item->update(['customization_json' => $snapshot]);

        $fields = FuelCardCustomization::fulfillmentFields($item->fresh()->customization_json);

        $this->assertSame($overLengthIdentifier, $fields['system_identifier']['value'], 'An over-length historical identifier must not be truncated.');
        $this->assertSame('LEGACYVIN1234', $fields['vin']['value'], 'A historical VIN must be normalized, never dropped.');

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee($overLengthIdentifier)
            ->assertSee('LEGACYVIN1234');
    }

    public function test_admin_fuel_fulfillment_survives_catalog_deletion(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), [
            'owner_name' => 'علی رضایی',
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'system_identifier' => 'NATIONAL-A',
            'plate_number' => '11A111',
            'chip_info' => 'small',
        ]);

        // The catalog loses everything the order referenced.
        $this->designImage->delete();
        $this->design->delete();
        $this->color->update(['name' => 'حذف‌شده']);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertNull($item->design_image_id, 'The catalog FK is expected to be nulled by the deletion.');
        $this->assertSame('طرح سوخت تست', $item->design_name_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('علی رضایی')
            ->assertSee('NAAAAAAAAAAAAAAA1')
            ->assertSee('NATIONAL-A')
            ->assertSee('11A111')
            ->assertSee('کوچک')
            ->assertSee('طرح سوخت تست');
    }

    public function test_fulfillment_read_model_is_built_only_from_the_order_snapshot(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), ['vin' => 'NAAAAAAAAAAAAAAA1']);

        // A live workspace is irrelevant: the read model is a pure function of
        // the snapshot, so it can never leak current customer state.
        $fields = FuelCardCustomization::fulfillmentFields(
            OrderItem::query()->where('order_id', $order->id)->firstOrFail()->customization_json
        );

        $this->assertSame('NAAAAAAAAAAAAAAA1', $fields['vin']['value']);
        $this->assertSame(
            ['owner_name', 'car_info', 'vin', 'system_name', 'system_identifier', 'plate_number', 'chip_info'],
            array_keys($fields),
        );
    }

    // =========================================================================
    // F-05 — DESIGN DELETION / PAID ORDER DURABILITY
    // =========================================================================

    public function test_deleting_a_design_image_used_by_a_paid_order_keeps_its_file(): void
    {
        Storage::fake('public');

        $order = $this->createPaidFuelOrder($this->createAdmin());
        $path = $this->designImage->image_path;
        Storage::disk('public')->put($path, 'design-bytes');

        // A second active image keeps the "last active image" purchaseability
        // guard out of the way so this test isolates file durability only.
        $this->createReplaceableImageFor($this->design, 'sibling');

        Livewire::actingAs($this->createAdmin())
            ->test(DesignImageManager::class)
            ->call('delete', $this->designImage->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $this->designImage->id]);
        Storage::disk('public')->assertExists($path);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($path, $item->design_image_path_snapshot);
    }

    public function test_deleting_a_design_image_used_by_a_paid_order_keeps_its_file_via_the_wizard(): void
    {
        Storage::fake('public');

        $order = $this->createPaidFuelOrder($this->createAdmin());
        $path = $this->designImage->image_path;
        Storage::disk('public')->put($path, 'design-bytes');

        $this->createReplaceableImageFor($this->design, 'sibling');

        Livewire::actingAs($this->createAdmin())
            ->test(DesignWizard::class, ['designId' => $this->design->id])
            ->call('deleteImage', $this->designImage->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $this->designImage->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_replacing_a_design_image_keeps_a_file_a_paid_order_still_needs(): void
    {
        Storage::fake('public');

        $order = $this->createPaidFuelOrder($this->createAdmin());
        $oldPath = $this->designImage->image_path;
        Storage::disk('public')->put($oldPath, 'old-design-bytes');

        $replacement = 'designs/onyx45-replacement-'.uniqid().'.png';
        Storage::disk('public')->put($replacement, 'new-design-bytes');

        Livewire::actingAs($this->createAdmin())
            ->test(DesignWizard::class, ['designId' => $this->design->id])
            ->set('editingImageId', $this->designImage->id)
            ->set('colorId', $this->color->id)
            ->set('imagePath', $replacement)
            ->set('imageIsActive', true)
            ->set('imageSortOrder', 1)
            ->call('saveImage')
            ->assertHasNoErrors();

        // The row really was rewritten, otherwise this proves nothing.
        $this->assertSame($replacement, $this->designImage->fresh()->image_path);

        Storage::disk('public')->assertExists($replacement);
        // Replacing a design image must not unlink a file a paid order still
        // points at.
        Storage::disk('public')->assertExists($oldPath);

        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();
        $this->assertSame($oldPath, $item->design_image_path_snapshot);
    }

    public function test_deleting_an_unreferenced_design_image_still_removes_its_file(): void
    {
        Storage::fake('public');

        [$design, $keepImage] = $this->createDesignAsset('cleanup');
        $doomed = $this->createImageFor($design, 'cleanup');
        Storage::disk('public')->put($doomed->image_path, 'bytes');

        Livewire::actingAs($this->createAdmin())
            ->test(DesignImageManager::class)
            ->call('delete', $doomed->id)
            ->assertOk();

        $this->assertDatabaseMissing('design_images', ['id' => $doomed->id]);
        Storage::disk('public')->assertMissing($doomed->image_path);
    }

    public function test_a_paid_order_survives_deleting_its_design(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin(), ['vin' => 'NAAAAAAAAAAAAAAA1']);
        $item = OrderItem::query()->where('order_id', $order->id)->firstOrFail();

        // The design cannot go while it still owns images; once the image is
        // gone the design itself may be removed from the catalog.
        Livewire::actingAs($this->createAdmin())
            ->test(DesignManager::class)
            ->call('delete', $this->design->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            Design::find($this->design->id),
            'A design that still owns images must survive a delete attempt.',
        );

        $this->designImage->delete();

        Livewire::actingAs($this->createAdmin())
            ->test(DesignManager::class)
            ->call('delete', $this->design->id)
            ->assertOk();

        $this->assertDatabaseMissing('designs', ['id' => $this->design->id]);

        $item->refresh();
        $this->assertNull($item->design_id);
        $this->assertSame('طرح سوخت تست', $item->design_name_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);

        Livewire::actingAs($this->createAdmin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('طرح سوخت تست')
            ->assertSee('NAAAAAAAAAAAAAAA1');
    }

    public function test_customer_paid_fuel_order_does_not_break_after_catalog_deletion(): void
    {
        $user = $this->createCustomer();
        $order = $this->createPaidFuelOrder($user, [
            'owner_name' => 'علی رضایی',
            'vin' => 'NAAAAAAAAAAAAAAA1',
            'plate_number' => '11A111',
        ]);

        $this->designImage->delete();
        $this->design->delete();

        $response = $this->actingAs($user)->get(route('orders.show', $order));

        $response->assertOk();
        $response->assertSee('طرح سوخت تست');
    }

    public function test_a_paid_order_blocks_deleting_its_product(): void
    {
        $order = $this->createPaidFuelOrder($this->createAdmin());

        // product_id is restrictOnDelete, so the commercial identity of a paid
        // order can never be removed from the catalog.
        $this->expectException(QueryException::class);

        $this->fuelProduct->delete();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function createDesignAsset(string $prefix, bool $allowed = true): array
    {
        $design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح سوخت تست',
            'slug' => $prefix.'-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = $this->createImageFor($design, $prefix);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => $allowed,
        ]);

        return [$design, $image];
    }

    private function createImageFor(Design $design, string $prefix): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/'.$prefix.'-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Builds a second active image that is itself allowed for the color, so a
     * sibling remains a purchasable option. Without this, the existing
     * "last active image" and "only allowed option" purchaseability guards
     * would block deletion and mask the file-durability behaviour under test.
     */
    private function createReplaceableImageFor(Design $design, string $prefix): DesignImage
    {
        $image = $this->createImageFor($design, $prefix);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        return $image;
    }

    private function createStoreProduct(): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول فروشگاهی',
            'slug' => 'onyx45-store-'.uniqid(),
            'base_price' => 250000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 300000,
            'is_active' => true,
        ]);

        return $product;
    }

    private function createBankProduct(): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی یکپارچگی',
            'slug' => 'onyx45-bank-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        return $product;
    }

    private function storePayload(Product $product, int $quantity): array
    {
        return [
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'design_id' => null,
            'design_image_id' => null,
            'quantity' => $quantity,
            'customization_json' => [],
        ];
    }

    private function fuelCustomization(array $overrides = []): array
    {
        return array_merge([
            'chip_info' => 'small',
        ], $overrides);
    }

    private function addFuel(array $overrides = [], int $quantity = 1): void
    {
        app(CartService::class)->addItem([
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => $quantity,
            'customization_json' => $this->fuelCustomization($overrides),
        ]);
    }

    private function addBank(Product $product, array $customization, int $quantity = 1): void
    {
        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => $quantity,
            'customization_json' => $customization,
        ]);
    }

    private function cartLines(): array
    {
        return array_values(app(CartService::class)->getCart()['items']);
    }

    /**
     * Checkout must refuse a session cart that never passed server-side
     * validation, and must refuse it before any order or payment row exists.
     */
    private function submitCheckoutExpectingRejection(): void
    {
        try {
            app(CartService::class)->createDraftOrder([
                'customer_name' => 'حسین',
                'customer_phone' => '09120000000',
            ]);
            $this->fail('Expected checkout to reject the tampered cart.');
        } catch (InvalidArgumentException) {
            // Expected: the authoritative checkout boundary refused the cart.
            // CartPriceChangedException also extends InvalidArgumentException.
        }

        $this->assertSame(0, Order::count(), 'Checkout must reject before persisting an order.');
    }

    private function vins(array $items): array
    {
        return array_map(fn (array $i): string => (string) ($i['customization_json']['vin'] ?? ''), $items);
    }

    private function createCustomer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    private function createAdmin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    private function createPaidFuelOrder(User $user, array $customization = []): Order
    {
        $order = new Order([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
            'shipping_address' => 'تهران',
        ]);

        $order->user_id = $user->id;
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->total_price = $this->unitPrice;
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
            'unit_price_snapshot' => $this->unitPrice,
            'quantity' => 1,
            'final_price' => $this->unitPrice,
            'customization_json' => $customization,
        ]);

        return $order;
    }
}
