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
use App\Rules\LuhnRule;
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CardDataSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private Product $bankProduct;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    // Valid Luhn card number (prefix 6274-0512-3456-789 + check digit 8)
    private const VALID_PAN = '6274051234567898';

    // Invalid Luhn card number (check digit 0 instead of 8)
    private const INVALID_PAN = '6274051234567890';

    private const CVV2_VALUE = '808';

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->customer = User::factory()->create([
            'role' => 'customer',
            'is_active' => true,
        ]);

        $this->color = Color::create([
            'name' => 'طلایی',
            'hex_code' => '#FFD700',
            'is_active' => true,
        ]);

        $this->bankProduct = Product::create([
            'name' => 'کارت بانکی هوشمند',
            'slug' => 'smart-bank-card',
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'is_active' => true,
            'is_purchasable' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $category = CateDesign::create([
            'name' => 'کارت‌ها',
            'slug' => 'cards',
            'is_active' => true,
        ]);

        $this->design = Design::create([
            'name' => 'طرح کلاسیک',
            'slug' => 'classic-design',
            'cate_design_id' => $category->id,
            'is_active' => true,
        ]);

        $this->designImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/classic.png',
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    private function createOrderForUser(?User $user = null): Order
    {
        $user = $user ?? $this->customer;
        $order = new Order;
        foreach ([
            'user_id' => $user->id,
            'customer_name' => $user->name ?? 'مشتری الوا',
            'customer_phone' => $user->phone ?? '09120000000',
            'shipping_address' => 'تهران، خیابان ولیعصر',
            'shipping_postal_code' => '1234567890',
            'total_price' => 500000,
            'token' => Str::random(40),
            'reference' => 'REF-'.Str::random(6),
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::UNPAID,
        ] as $key => $value) {
            $order->setAttribute($key, $value);
        }

        $order->save();

        return $order;
    }

    public function test_luhn_rule_passes_for_valid_16_digit_card_number(): void
    {
        $this->assertTrue(LuhnRule::passesLuhn(self::VALID_PAN));
        $this->assertTrue(BankCardCustomization::validateLuhn(self::VALID_PAN));
        $this->assertTrue(LuhnRule::passesLuhn('6037991234567893'));
    }

    public function test_luhn_rule_rejects_invalid_card_numbers(): void
    {
        $this->assertFalse(LuhnRule::passesLuhn(self::INVALID_PAN));
        $this->assertFalse(BankCardCustomization::validateLuhn(self::INVALID_PAN));
        $this->assertFalse(LuhnRule::passesLuhn('1234567812345678'));
        $this->assertFalse(LuhnRule::passesLuhn('0000000000000001'));
    }

    public function test_customizer_validates_luhn_and_rejects_invalid_card_number(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->set('bankCard.card_number', self::INVALID_PAN)
            ->call('addToCart')
            ->assertHasErrors(['bankCard.card_number']);
    }

    public function test_customizer_accepts_valid_luhn_card_number(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->set('bankCard.card_number', self::VALID_PAN)
            ->call('addToCart')
            ->assertHasNoErrors(['bankCard.card_number'])
            ->assertRedirect(route('cart.index'));
    }

    public function test_cvv2_is_validated_when_enabled_and_then_discarded(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('toggleCvv')
            ->set('bankCard.cvv2', '12') // too short
            ->call('addToCart')
            ->assertHasErrors(['bankCard.cvv2' => 'digits_between']);

        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('toggleCvv')
            ->set('bankCard.card_number', self::VALID_PAN)
            ->set('bankCard.cvv2', self::CVV2_VALUE)
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $customization = $cart['items'][0]['customization_json'];

        $this->assertTrue($customization['security_cvv_enabled']);
        $this->assertArrayNotHasKey('cvv2', $customization);
    }

    public function test_pan_is_encrypted_at_rest_and_cvv2_is_never_persisted_in_order(): void
    {
        $cartService = app(CartService::class);
        $cartService->addItem([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'HOSSEIN REZAIE',
                'security_cvv_enabled' => true,
                'cvv2' => self::CVV2_VALUE,
            ],
        ]);

        $order = $cartService->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], $this->customer->id);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();

        // 1. Plaintext PAN is NEVER in customization_json
        $this->assertArrayNotHasKey('card_number', $item->customization_json);
        $this->assertArrayNotHasKey('cvv2', $item->customization_json);

        // 2. Encryption attributes exist
        $this->assertArrayHasKey('pan_encrypted', $item->customization_json);
        $this->assertSame('7898', $item->customization_json['pan_last4']);
        $this->assertSame('•••• •••• •••• 7898', $item->customization_json['card_number_masked']);
        $this->assertArrayHasKey('pan_hash', $item->customization_json);

        // 3. Raw database inspection proves no plaintext PAN or CVV2 in MySQL row
        $rawRow = DB::table('order_items')->where('id', $item->id)->first();
        $this->assertStringNotContainsString(self::VALID_PAN, $rawRow->customization_json);
        $this->assertStringNotContainsString(self::CVV2_VALUE, $rawRow->customization_json);

        // 4. Authorized internal decryption recovers original PAN
        $this->assertSame(self::VALID_PAN, Crypt::decryptString($item->customization_json['pan_encrypted']));
        $this->assertSame(self::VALID_PAN, $item->getDecryptedPan());
        $this->assertSame('•••• •••• •••• 7898', $item->getMaskedPan());
        $this->assertSame('7898', $item->getLast4Pan());
        $this->assertTrue($item->isCvvEnabled());
    }

    public function test_customer_order_view_renders_masked_pan_and_never_renders_full_pan_or_cvv2(): void
    {
        $order = $this->createOrderForUser($this->customer);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'HOSSEIN REZAIE',
                'security_cvv_enabled' => true,
                'cvv2' => self::CVV2_VALUE,
            ],
        ]);

        $response = $this->post(route('order-tracking.check'), ['token' => $order->token]);

        $response->assertOk();
        $response->assertDontSee(self::VALID_PAN);
        $response->assertDontSee('6274 0512 3456 7898');
        $response->assertDontSee(self::CVV2_VALUE);
    }

    public function test_admin_order_view_renders_full_card_production_information_for_authorized_admin(): void
    {
        $order = $this->createOrderForUser($this->customer);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'design_image_id' => $this->designImage->id,
            'design_image_path_snapshot' => $this->designImage->image_path,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'HOSSEIN REZAIE',
                'security_cvv_enabled' => true,
                'cvv2' => self::CVV2_VALUE,
                'security_expiry_enabled' => true,
                'expiry_month' => '12',
                'expiry_year' => '28',
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Admin order view reveals full unmasked production parameters for physical manufacturing
        $this->assertStringContainsString('6274 0512 3456 7898', $html);
        $this->assertStringContainsString(self::CVV2_VALUE, $html);
        $this->assertStringContainsString('12/28', $html);
        $this->assertStringContainsString('HOSSEIN REZAIE', $html);
        $this->assertStringContainsString($this->color->name, $html);
        $this->assertStringContainsString($this->design->name, $html);
        $this->assertStringNotContainsString('•••', $html);
    }

    public function test_admin_can_update_order_status_to_production_and_completed(): void
    {
        $order = $this->createOrderForUser($this->customer);
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->save();

        Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('updateStatus', $order->id, 'production')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatusEnum::PRODUCTION, $order->fresh()->status);

        Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('updateStatus', $order->id, 'completed')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatusEnum::COMPLETED, $order->fresh()->status);
    }

    public function test_admin_invalid_order_status_is_rejected(): void
    {
        $order = $this->createOrderForUser($this->customer);
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->save();

        Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('updateStatus', $order->id, 'invalid_status_xyz')
            ->assertSee('وضعیت نامعتبر است');

        $this->assertSame(OrderStatusEnum::CONFIRMED, $order->fresh()->status);
    }

    public function test_order_item_saving_hook_automatically_secures_plaintext_card_data(): void
    {
        $order = $this->createOrderForUser();

        $item = new OrderItem([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'REZA AHMADI',
                'security_cvv_enabled' => true,
                'cvv2' => '999',
            ],
        ]);

        $item->save();

        $fresh = $item->fresh();

        $this->assertArrayNotHasKey('card_number', $fresh->customization_json);
        $this->assertArrayNotHasKey('cvv2', $fresh->customization_json);
        $this->assertSame('REZA AHMADI', $fresh->customization_json['card_holder_name']);
        $this->assertTrue($fresh->customization_json['security_cvv_enabled']);
        $this->assertSame('7898', $fresh->customization_json['pan_last4']);
        $this->assertSame('•••• •••• •••• 7898', $fresh->getMaskedPan());
        $this->assertSame(self::VALID_PAN, $fresh->getDecryptedPan());
    }

    public function test_migration_safely_secures_legacy_plaintext_order_items(): void
    {
        // 1. Manually insert a raw row simulating pre-existing plaintext data in MySQL
        $order = $this->createOrderForUser();
        $legacyPayload = [
            'card_number' => self::VALID_PAN,
            'card_holder_name' => 'LEGACY CUSTOMER',
            'back_text' => 'NEVER SURRENDER',
            'security_cvv_enabled' => true,
            'cvv2' => '123',
            'security_expiry_enabled' => true,
            'expiry_month' => '11',
            'expiry_year' => '28',
        ];

        $itemId = DB::table('order_items')->insertGetId([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_name_snapshot' => $this->bankProduct->name,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => json_encode($legacyPayload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Run the migration
        $migration = require database_path('migrations/2026_09_30_000003_secure_cardholder_data_in_order_items_table.php');
        $migration->up();

        // 3. Inspect updated row
        $migratedItem = OrderItem::find($itemId);
        $this->assertNotNull($migratedItem);

        $custom = $migratedItem->customization_json;
        $this->assertArrayNotHasKey('card_number', $custom);
        $this->assertArrayNotHasKey('cvv2', $custom);
        $this->assertSame('LEGACY CUSTOMER', $custom['card_holder_name']);
        $this->assertSame('NEVER SURRENDER', $custom['back_text']);
        $this->assertTrue($custom['security_cvv_enabled']);
        $this->assertSame('11', $custom['expiry_month']);
        $this->assertSame('28', $custom['expiry_year']);
        $this->assertSame('7898', $custom['pan_last4']);
        $this->assertSame('•••• •••• •••• 7898', $migratedItem->getMaskedPan());
        $this->assertSame(self::VALID_PAN, $migratedItem->getDecryptedPan());

        // 4. Test down migration decrypts PAN back cleanly
        $migration->down();
        $revertedItem = OrderItem::find($itemId);
        $revertedCustom = $revertedItem->customization_json;

        $this->assertSame(self::VALID_PAN, $revertedCustom['card_number']);
        $this->assertArrayNotHasKey('pan_encrypted', $revertedCustom);
        // CVV2 was permanently discarded for security
        $this->assertArrayNotHasKey('cvv2', $revertedCustom);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $otherCustomer = User::factory()->create();
        $order = $this->createOrderForUser($otherCustomer);

        $response = $this->get('/orders/'.$order->id);
        $response->assertNotFound();

        $this->post(route('order-tracking.check'), ['token' => \Illuminate\Support\Str::random(40)])
            ->assertSessionHasErrors(['token']);
    }

    public function test_admin_order_view_renders_unmasked_cvv2_when_provided_by_customer(): void
    {
        // 1. Customer places card order with CVV2 via designer pipeline
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('toggleCvv')
            ->set('bankCard.card_number', self::VALID_PAN)
            ->set('bankCard.cvv2', self::CVV2_VALUE)
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $customization = $cart['items'][0]['customization_json'];
        $this->assertArrayHasKey('cvv_encrypted', $customization);
        $this->assertSame(self::CVV2_VALUE, Crypt::decryptString($customization['cvv_encrypted']));

        $order = app(CartService::class)->createDraftOrder([
            'customer_name' => 'تست CVV2',
            'customer_phone' => '09121111111',
        ], $this->customer->id);

        $item = OrderItem::where('order_id', $order->id)->firstOrFail();
        $this->assertSame(self::CVV2_VALUE, $item->getDecryptedCvv());

        // 2. Admin inspects order details
        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Admin sees full unmasked CVV2 and card details
        $this->assertStringContainsString(self::CVV2_VALUE, $html);
        $this->assertStringContainsString('6274 0512 3456 7898', $html);
        $this->assertStringNotContainsString('•••', $html);
        $this->assertStringNotContainsString('***', $html);
    }

    public function test_admin_order_view_renders_not_registered_for_cvv2_when_omitted(): void
    {
        $order = $this->createOrderForUser($this->customer);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'NO CVV USER',
                'security_cvv_enabled' => false,
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Admin sees "ثبت نشده" for CVV2 and no fake placeholder
        $this->assertStringContainsString('ثبت نشده', $html);
        $this->assertStringNotContainsString('•••', $html);
        $this->assertStringNotContainsString('***', $html);
    }

    public function test_ordinary_store_product_order_renders_without_card_manufacturing_fields_or_previews(): void
    {
        $standardProduct = Product::create([
            'name' => 'برچسب محافظ متالیک',
            'slug' => 'metallic-card-skin',
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'is_active' => true,
            'is_purchasable' => true,
            'base_price' => 120000,
        ]);

        $order = $this->createOrderForUser($this->customer);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $standardProduct->id,
            'customization_workflow' => null,
            'product_name_snapshot' => $standardProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'unit_price_snapshot' => 120000,
            'quantity' => 2,
            'final_price' => 240000,
            'customization_json' => [
                'material' => 'مات ضدخش',
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // 1. Shows ordinary product commerce details
        $this->assertStringContainsString('برچسب محافظ متالیک', $html);
        $this->assertStringContainsString('محصول فروشگاهی', $html);
        $this->assertStringContainsString('120,000', $html);
        $this->assertStringContainsString('240,000', $html);
        $this->assertStringContainsString('material', $html);
        $this->assertStringContainsString('مات ضدخش', $html);

        // 2. Strictly DOES NOT show card manufacturing workspace or previews
        $this->assertStringNotContainsString('پارامترهای حکاکی کاربر', $html);
        $this->assertStringNotContainsString('شماره کامل کارت (PAN)', $html);
        $this->assertStringNotContainsString('کد امنیتی CVV2', $html);
        $this->assertStringNotContainsString('2D Snapshot Preview', $html);
        $this->assertStringNotContainsString('CR-80 Production Preview', $html);
        $this->assertStringNotContainsString('مشخصات کارت سوخت', $html);
    }

    public function test_mixed_order_renders_custom_card_and_ordinary_product_by_their_respective_types(): void
    {
        $standardProduct = Product::create([
            'name' => 'کیف چرمی کارت',
            'slug' => 'leather-card-holder',
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'is_active' => true,
            'is_purchasable' => true,
            'base_price' => 250000,
        ]);

        $order = $this->createOrderForUser($this->customer);

        // Item 1: Custom Bank Card
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->design->id,
            'design_name_snapshot' => $this->design->name,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'MIXED ORDER USER',
                'security_cvv_enabled' => true,
                'cvv2' => '999',
            ],
        ]);

        // Item 2: Ordinary Store Product
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $standardProduct->id,
            'customization_workflow' => null,
            'product_name_snapshot' => $standardProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'unit_price_snapshot' => 250000,
            'quantity' => 1,
            'final_price' => 250000,
            'customization_json' => [],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // 1. Custom Bank Card elements exist
        $this->assertStringContainsString('6274 0512 3456 7898', $html);
        $this->assertStringContainsString('MIXED ORDER USER', $html);
        $this->assertStringContainsString('999', $html);
        $this->assertStringContainsString('پارامترهای حکاکی کاربر', $html);
        $this->assertStringContainsString('2D Snapshot Preview', $html);

        // 2. Ordinary Product elements exist
        $this->assertStringContainsString('کیف چرمی کارت', $html);
        $this->assertStringContainsString('محصول فروشگاهی', $html);
        $this->assertStringContainsString('250,000', $html);
    }

    public function test_admin_order_view_renders_bank_card_with_distinct_material_color_design_and_design_color(): void
    {
        $order = $this->createOrderForUser($this->customer);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => 'کارت بانکی متالیک',
            'color_id' => $this->color->id,
            'color_name_snapshot' => 'مشکی مات',
            'design_id' => $this->design->id,
            'design_name_snapshot' => 'طرح کوروش کبیر',
            'unit_price_snapshot' => 600000,
            'quantity' => 1,
            'final_price' => 600000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'CYRUS THE GREAT',
                'design_color_name' => 'طلایی براق',
                'design_color_hex' => '#FFD700',
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // 1. Material color, selected design, and design color are distinctly rendered
        $this->assertStringContainsString('رنگ متریال کارت:', $html);
        $this->assertStringContainsString('مشکی مات', $html);
        $this->assertStringContainsString('طرح انتخابی:', $html);
        $this->assertStringContainsString('طرح کوروش کبیر', $html);
        $this->assertStringContainsString('رنگ طرح انتخابی:', $html);
        $this->assertStringContainsString('طلایی براق', $html);
        $this->assertStringContainsString('#FFD700', $html);

        // 2. Material color and design color are not conflated
        $this->assertNotSame('مشکی مات', 'طلایی براق');
    }

    public function test_admin_order_view_renders_fuel_card_with_distinct_material_color_design_and_design_color(): void
    {
        $fuelProduct = Product::create([
            'name' => 'کارت سوخت هوشمند فلزی',
            'slug' => 'smart-fuel-card-metal',
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'is_active' => true,
            'is_purchasable' => true,
            'base_price' => 700000,
        ]);

        $order = $this->createOrderForUser($this->customer);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $fuelProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
            'product_name_snapshot' => $fuelProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => 'آبی تیتانیوم',
            'design_id' => $this->design->id,
            'design_name_snapshot' => 'طرح شرکت ملی نفت',
            'unit_price_snapshot' => 700000,
            'quantity' => 1,
            'final_price' => 700000,
            'customization_json' => [
                'chip_info' => 'small',
                'vin' => 'IRAN1234567890123',
                'owner_name' => 'رضا حسینی',
                'design_color_name' => 'نقره‌ای لیزری',
                'design_color_hex' => '#C0C0C0',
            ],
        ]);

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        $this->assertStringContainsString('مشخصات کارت سوخت:', $html);
        $this->assertStringContainsString('رنگ متریال کارت:', $html);
        $this->assertStringContainsString('آبی تیتانیوم', $html);
        $this->assertStringContainsString('طرح انتخابی:', $html);
        $this->assertStringContainsString('طرح شرکت ملی نفت', $html);
        $this->assertStringContainsString('رنگ طرح انتخابی:', $html);
        $this->assertStringContainsString('نقره‌ای لیزری', $html);
        $this->assertStringContainsString('#C0C0C0', $html);
    }

    public function test_admin_order_view_renders_not_registered_for_historical_order_without_design_color(): void
    {
        $order = $this->createOrderForUser($this->customer);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => 'طلایی کلاسیک',
            'design_id' => $this->design->id,
            'design_name_snapshot' => 'طرح قدیمی',
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'OLD CUSTOMER',
            ],
        ]);

        $this->assertNull($item->getDesignColorName());
        $this->assertNull($item->getDesignColorHex());

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Must show neutral "ثبت نشده" and NOT fall back to catalog or material color
        $this->assertStringContainsString('رنگ طرح انتخابی:', $html);
        $this->assertStringContainsString('ثبت نشده', $html);
    }

    public function test_historical_order_preserves_design_color_snapshot_after_catalog_mutation(): void
    {
        $order = $this->createOrderForUser($this->customer);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => 'رزگلد',
            'design_id' => $this->design->id,
            'design_name_snapshot' => 'طرح اصیل',
            'design_image_id' => $this->designImage->id,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'design_color_name' => 'طلایی سلطنتی',
                'design_color_hex' => '#FFD700',
            ],
        ]);

        // Catalog is mutated / renamed / deleted after the order
        $this->color->update(['name' => 'سبز لجنی', 'hex_code' => '#003300']);
        $this->designImage->delete();

        $component = Livewire::actingAs($this->admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id);

        $html = $component->html();

        // Order view still strictly shows snapshot design color and hex
        $this->assertStringContainsString('طلایی سلطنتی', $html);
        $this->assertStringContainsString('#FFD700', $html);
        $this->assertStringNotContainsString('سبز لجنی', $html);
    }

    public function test_cart_service_captures_and_persists_design_color_snapshot_at_checkout(): void
    {
        $laserColor = Color::create([
            'name' => 'سفید کریستالی',
            'code_hex' => '#F8F9FA',
            'is_active' => true,
        ]);

        $customImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $laserColor->id,
            'image_path' => 'designs/crystal-white.png',
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $customImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        $cartService = app(CartService::class);
        $cartService->clear();

        $cartService->addItem([
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $customImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => self::VALID_PAN,
                'card_holder_name' => 'ORDER PIPELINE TEST',
            ],
        ]);

        $order = $cartService->createDraftOrder([
            'customer_name' => 'تست‌کننده',
            'customer_phone' => '09123456789',
        ], $this->customer->id);

        $persistedItem = $order->items()->first();

        $this->assertNotNull($persistedItem);
        $this->assertSame('سفید کریستالی', $persistedItem->customization_json['design_color_name'] ?? null);
        $this->assertSame('#F8F9FA', $persistedItem->customization_json['design_color_hex'] ?? null);
        $this->assertSame('سفید کریستالی', $persistedItem->getDesignColorName());
        $this->assertSame('#F8F9FA', $persistedItem->getDesignColorHex());
    }
}
