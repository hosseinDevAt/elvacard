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
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use App\Support\Dates\DateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class BankCardExpiryValidationAndDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;
    private Product $bankProduct;
    private Color $color;
    private Design $design;
    private DesignImage $designImage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        $category = CateDesign::create([
            'name' => 'تست انقضا',
            'slug' => 'expiry-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'مشکی انقضا',
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح انقضا',
            'slug' => 'expiry-design-'.uniqid(),
            'is_active' => true,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/expiry-test.svg',
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_id' => $this->design->id,
            'card_color_id' => $this->color->id,
            'design_image_id' => $this->designImage->id,
            'is_allowed' => true,
        ]);

        $this->bankProduct = Product::create([
            'name' => 'کارت بانکی تست انقضا',
            'slug' => 'bank-expiry-'.uniqid(),
            'type' => ProductTypeEnum::BANK,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'price' => 500000,
            'is_active' => true,
        ]);
    }

    private function createOrder(array $attributes = []): Order
    {
        $order = new Order(array_merge([
            'customer_name' => 'تست خریدار',
            'customer_phone' => '09123456789',
        ], $attributes));
        $order->user_id = $this->customer->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->total_price = 500000;
        $order->save();

        return $order;
    }

    private function createOrderItem(Order $order, array $customization = []): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 500000,
            'final_price' => 500000,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'customization_json' => $customization,
        ]);
    }

    /**
     * Requirement 1: Valid Jalali expiry input converts and persists correctly.
     */
    public function test_valid_jalali_expiry_input_converts_and_persists_correctly(): void
    {
        $payload = [
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '02',
                'expiry_year' => '1407',
            ],
        ];

        $sanitized = BankCardCustomization::sanitize($payload);

        $this->assertTrue($sanitized['security_expiry_enabled']);
        $this->assertSame('05', $sanitized['expiry_month']);
        $this->assertSame('28', $sanitized['expiry_year']);

        $order = $this->createOrder();
        $item = $this->createOrderItem($order, $sanitized);

        $fresh = $item->fresh();
        $this->assertTrue($fresh->customization_json['security_expiry_enabled']);
        $this->assertSame('05', $fresh->customization_json['expiry_month']);
        $this->assertSame('28', $fresh->customization_json['expiry_year']);
    }

    /**
     * Requirement 2: A supported Jalali year is preserved through repeated sanitization.
     * Verifies that years 1400, 1407, 1416, and 1430 are not dropped on subsequent passes.
     */
    public function test_supported_jalali_year_is_preserved_through_repeated_sanitization(): void
    {
        $testYears = [
            '1400' => ['expected_g_month' => '05', 'expected_g_year' => '21'],
            '1407' => ['expected_g_month' => '05', 'expected_g_year' => '28'],
            '1416' => ['expected_g_month' => '05', 'expected_g_year' => '37'],
            '1430' => ['expected_g_month' => '05', 'expected_g_year' => '51'],
        ];

        foreach ($testYears as $jalaliYear => $expected) {
            // Pass 1: Fresh Jalali submission
            $pass1 = BankCardCustomization::sanitize([
                'customization_json' => [
                    'security_expiry_enabled' => true,
                    'expiry_month' => '02',
                    'expiry_year' => $jalaliYear,
                ],
            ]);

            $this->assertTrue($pass1['security_expiry_enabled'], "Failed enabled for $jalaliYear");
            $this->assertSame($expected['expected_g_month'], $pass1['expiry_month'], "Failed month pass 1 for $jalaliYear");
            $this->assertSame($expected['expected_g_year'], $pass1['expiry_year'], "Failed year pass 1 for $jalaliYear");

            // Pass 2: Repeated sanitization (as happens during cart revalidation / checkout)
            $pass2 = BankCardCustomization::sanitize([
                'customization_json' => $pass1,
            ]);

            $this->assertTrue($pass2['security_expiry_enabled'], "Failed enabled pass 2 for $jalaliYear");
            $this->assertSame($expected['expected_g_month'], $pass2['expiry_month'] ?? null, "Failed month pass 2 for $jalaliYear");
            $this->assertSame($expected['expected_g_year'], $pass2['expiry_year'] ?? null, "Failed year pass 2 for $jalaliYear (MUST NOT BE DROPPED)");

            // Pass 3: Another repeated pass
            $pass3 = BankCardCustomization::sanitize([
                'customization_json' => $pass2,
            ]);

            $this->assertSame($expected['expected_g_month'], $pass3['expiry_month'] ?? null, "Failed month pass 3 for $jalaliYear");
            $this->assertSame($expected['expected_g_year'], $pass3['expiry_year'] ?? null, "Failed year pass 3 for $jalaliYear");
        }
    }

    /**
     * Requirement 3: Missing month is rejected when expiry is enabled.
     */
    public function test_missing_month_is_rejected_when_expiry_is_enabled(): void
    {
        // 1. Sanitizer does not produce partial payload
        $sanitized = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_year' => '1407',
            ],
        ]);
        $this->assertArrayNotHasKey('expiry_month', $sanitized);
        $this->assertArrayNotHasKey('expiry_year', $sanitized);

        // 2. CartService rejects missing month with clear Persian error
        $cartService = app(CartService::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص یا نامعتبر است؛ انتخاب ماه و سال انقضا الزامی است.');

        $cartService->addItem([
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_year' => '1407',
            ],
        ]);
    }

    /**
     * Requirement 4: Missing year is rejected when expiry is enabled.
     */
    public function test_missing_year_is_rejected_when_expiry_is_enabled(): void
    {
        // 1. Sanitizer does not produce partial payload
        $sanitized = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '02',
            ],
        ]);
        $this->assertArrayNotHasKey('expiry_month', $sanitized);
        $this->assertArrayNotHasKey('expiry_year', $sanitized);

        // 2. CartService rejects missing year with clear Persian error
        $cartService = app(CartService::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص یا نامعتبر است؛ انتخاب ماه و سال انقضا الزامی است.');

        $cartService->addItem([
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '02',
            ],
        ]);
    }

    /**
     * Requirement 5: Invalid month is rejected.
     */
    public function test_invalid_month_is_rejected(): void
    {
        foreach (['00', '13', '99', 'abc'] as $badMonth) {
            $sanitized = BankCardCustomization::sanitize([
                'customization_json' => [
                    'security_expiry_enabled' => true,
                    'expiry_month' => $badMonth,
                    'expiry_year' => '1407',
                ],
            ]);

            $this->assertArrayNotHasKey('expiry_month', $sanitized, "Month $badMonth must not be set");
            $this->assertArrayNotHasKey('expiry_year', $sanitized, "Year must not be set when month $badMonth is invalid");
        }
    }

    /**
     * Requirement 6: Invalid or unsupported Jalali year is rejected.
     */
    public function test_invalid_or_unsupported_jalali_year_is_rejected(): void
    {
        // 1399 is below config min (1400), 1431 is above config max (1430)
        foreach (['1399', '1431', '9999', 'invalid'] as $badYear) {
            $sanitized = BankCardCustomization::sanitize([
                'customization_json' => [
                    'security_expiry_enabled' => true,
                    'expiry_month' => '05',
                    'expiry_year' => $badYear,
                ],
            ]);

            $this->assertArrayNotHasKey('expiry_month', $sanitized, "Month must not be set for year $badYear");
            $this->assertArrayNotHasKey('expiry_year', $sanitized, "Year $badYear must not be set");
        }
    }

    /**
     * Requirement 7: A failed conversion cannot produce a partial expiry payload.
     */
    public function test_failed_conversion_cannot_produce_partial_expiry_payload(): void
    {
        // Out-of-range Jalali year that fails jalaliExpiryToGregorian
        $sanitized = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '02',
                'expiry_year' => '1450',
            ],
        ]);

        $this->assertArrayNotHasKey('expiry_month', $sanitized, 'expiry_month must NEVER be silently retained when conversion fails');
        $this->assertArrayNotHasKey('expiry_year', $sanitized, 'expiry_year must not be present when conversion fails');

        // OrderItem saving hook also blocks partial expiry data
        $order = $this->createOrder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص است.');

        $this->createOrderItem($order, [
            'security_expiry_enabled' => true,
            'expiry_month' => '02',
            // expiry_year is deliberately missing
        ]);
    }

    /**
     * Requirement 8: Expiry disabled does not require or retain expiry month/year.
     */
    public function test_expiry_disabled_does_not_require_or_retain_expiry_month_or_year(): void
    {
        $sanitized = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => false,
                'expiry_month' => '02',
                'expiry_year' => '1407',
            ],
        ]);

        $this->assertFalse($sanitized['security_expiry_enabled']);
        $this->assertArrayNotHasKey('expiry_month', $sanitized);
        $this->assertArrayNotHasKey('expiry_year', $sanitized);

        // CartService accepts it with no validation errors
        $cartService = app(CartService::class);
        $cartService->addItem([
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'security_expiry_enabled' => false,
            ],
        ]);

        $this->assertCount(1, $cartService->getCart()['items']);
    }

    /**
     * Requirement 9: Stored Gregorian expiry values are displayed as the correct Jalali month/year in admin.
     */
    public function test_stored_gregorian_expiry_values_are_displayed_as_correct_jalali_in_admin(): void
    {
        $order = $this->createOrder();

        // Stored Gregorian month 05, year 28 (originating from Jalali 02/1407)
        $item = $this->createOrderItem($order, [
            'card_holder_name' => 'ADMIN TEST BUYER',
            'security_expiry_enabled' => true,
            'expiry_month' => '05',
            'expiry_year' => '28',
        ]);

        $this->assertSame('02/07', $item->getFormattedExpiry());

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Admin must show Jalali 02/07, not Gregorian 05/28
        $this->assertStringContainsString('02/07', $html);
        $this->assertStringNotContainsString('05/28', $html);
    }

    /**
     * Requirement 10: Both admin order views use consistent Jalali formatting.
     */
    public function test_both_admin_order_views_use_consistent_jalali_formatting(): void
    {
        $order = $this->createOrder();

        // Stored Gregorian month 05, year 37 (originating from Jalali 02/1416)
        $item = $this->createOrderItem($order, [
            'card_holder_name' => 'CONSISTENT VIEW TEST',
            'security_expiry_enabled' => true,
            'expiry_month' => '05',
            'expiry_year' => '37',
        ]);

        // The expected Jalali representation is 02/16 (Farvardin/Ordibehesht 1416)
        $expectedJalali = $item->getFormattedExpiry();
        $this->assertSame('02/16', $expectedJalali);

        // 1. View 1: OrderManager Livewire component
        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $this->assertStringContainsString($expectedJalali, $component->html());

        // 2. View 2: orders.show view
        $viewHtml = view('orders.show', [
            'order' => $order->load('items'),
            'canRetryPayment' => false,
        ])->render();

        $this->assertStringContainsString($expectedJalali, $viewHtml);
    }

    /**
     * Requirement: Creating a new bank-card item with expiry enabled and missing year must fail.
     */
    public function test_saving_guard_rejects_new_bank_card_item_with_missing_year(): void
    {
        $order = $this->createOrder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص است.');

        $this->createOrderItem($order, [
            'security_expiry_enabled' => true,
            'expiry_month' => '05',
            // missing expiry_year
        ]);
    }

    /**
     * Requirement: Creating a new bank-card item with expiry enabled and missing month must fail.
     */
    public function test_saving_guard_rejects_new_bank_card_item_with_missing_month(): void
    {
        $order = $this->createOrder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص است.');

        $this->createOrderItem($order, [
            'security_expiry_enabled' => true,
            'expiry_year' => '37',
            // missing expiry_month
        ]);
    }

    /**
     * Requirement: Updating an old incomplete item through an unrelated field must succeed.
     */
    public function test_saving_guard_allows_unrelated_update_on_historical_incomplete_item(): void
    {
        $order = $this->createOrder();

        // Simulate historical item that was saved before the fix with incomplete expiry
        $rawId = DB::table('order_items')->insertGetId([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 500000,
            'final_price' => 500000,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_json' => json_encode([
                'card_holder_name' => 'HISTORICAL BUYER',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
                // expiry_year is null due to historical 1416 bug
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = OrderItem::findOrFail($rawId);
        $this->assertNull($item->customization_json['expiry_year'] ?? null);

        // Perform unrelated update on quantity and unit_price
        $item->quantity = 3;
        $item->final_price = 1500000;
        $item->save();

        $fresh = $item->fresh();
        $this->assertSame(3, $fresh->quantity);
        $this->assertSame(1500000, $fresh->final_price);
        $this->assertSame('05', $fresh->customization_json['expiry_month']);
        $this->assertNull($fresh->customization_json['expiry_year'] ?? null);
    }

    /**
     * Requirement: Updating an old incomplete item while attempting to introduce or modify incomplete expiry data must fail.
     */
    public function test_saving_guard_rejects_modifying_or_introducing_incomplete_expiry_on_existing_item(): void
    {
        $order = $this->createOrder();

        // Historical incomplete item
        $rawId = DB::table('order_items')->insertGetId([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 500000,
            'final_price' => 500000,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_json' => json_encode([
                'card_holder_name' => 'HISTORICAL BUYER',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = OrderItem::findOrFail($rawId);

        // Attempting to modify expiry month without providing a valid year must fail
        $item->customization_json = [
            'card_holder_name' => 'HISTORICAL BUYER',
            'security_expiry_enabled' => true,
            'expiry_month' => '06', // modified month
            // expiry_year remains missing
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('اطلاعات تاریخ انقضای کارت بانکی ناقص است.');

        $item->save();
    }

    /**
     * Requirement: Correcting an old incomplete item by supplying a complete valid expiry must succeed.
     */
    public function test_saving_guard_allows_correcting_historical_incomplete_item_with_valid_expiry(): void
    {
        $order = $this->createOrder();

        // Historical incomplete item
        $rawId = DB::table('order_items')->insertGetId([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 500000,
            'final_price' => 500000,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_json' => json_encode([
                'card_holder_name' => 'HISTORICAL BUYER',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = OrderItem::findOrFail($rawId);

        // Correct the incomplete expiry by supplying a valid month and year
        $item->customization_json = [
            'card_holder_name' => 'HISTORICAL BUYER',
            'security_expiry_enabled' => true,
            'expiry_month' => '05',
            'expiry_year' => '37',
        ];
        $item->save();

        $fresh = $item->fresh();
        $this->assertSame('05', $fresh->customization_json['expiry_month']);
        $this->assertSame('37', $fresh->customization_json['expiry_year']);
        $this->assertSame('02/16', $fresh->getFormattedExpiry());
    }

    /**
     * Requirement: Updating a non-bank-card item must remain unaffected.
     */
    public function test_saving_guard_allows_updating_non_bank_card_items(): void
    {
        $order = $this->createOrder();

        $fuelProduct = Product::create([
            'name' => 'کارت سوخت تست',
            'slug' => 'fuel-expiry-'.uniqid(),
            'type' => ProductTypeEnum::FUEL,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
            'base_price' => 300000,
            'is_active' => true,
        ]);

        $fuelItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $fuelProduct->id,
            'product_name_snapshot' => $fuelProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 300000,
            'final_price' => 300000,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
            'customization_json' => [
                'chip_info' => 'small',
                'fuel_type' => 'gasoline',
            ],
        ]);

        $fuelItem->quantity = 5;
        $fuelItem->save();

        $fresh = $fuelItem->fresh();
        $this->assertSame(5, $fresh->quantity);
        $this->assertSame('small', $fresh->customization_json['chip_info']);
    }

    /**
     * Requirement: Existing valid bank-card expiry data must remain valid.
     */
    public function test_saving_guard_preserves_valid_existing_expiry_data_on_update(): void
    {
        $order = $this->createOrder();

        $item = $this->createOrderItem($order, [
            'card_holder_name' => 'VALID BUYER',
            'security_expiry_enabled' => true,
            'expiry_month' => '05',
            'expiry_year' => '28',
        ]);

        $item->quantity = 2;
        $item->save();

        $fresh = $item->fresh();
        $this->assertSame(2, $fresh->quantity);
        $this->assertSame('05', $fresh->customization_json['expiry_month']);
        $this->assertSame('28', $fresh->customization_json['expiry_year']);
        $this->assertSame('02/07', $fresh->getFormattedExpiry());
    }
}
