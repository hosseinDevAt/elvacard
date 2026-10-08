<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
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
use App\Rules\IbanRule;
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use App\Services\Customization\CardPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Tests\TestCase;

class BankCardDesignerEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected Product $product;
    protected Color $lightColor;
    protected Color $darkColor;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی لوکس',
            'slug' => 'luxury-bank-card',
            'base_price' => 1500000,
            'is_active' => true,
        ]);

        $this->lightColor = Color::create([
            'name' => 'طلایی مات',
            'code_hex' => '#E5C158',
            'color_code' => '#E5C158',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->darkColor = Color::create([
            'name' => 'مشکی مات',
            'code_hex' => '#111827',
            'color_code' => '#111827',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->lightColor->id,
            'price' => 1500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->darkColor->id,
            'price' => 1500000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح ساده',
            'slug' => 'simple-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImageLight = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->lightColor->id,
            'image_path' => 'designs/light.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImageLight->id,
            'card_color_id' => $this->lightColor->id,
            'is_allowed' => true,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->forceFill(['role' => 'admin'])->save();
    }

    /**
     * Generate a valid Iranian IBAN for testing using ISO 7064 Mod 97-10 check digits.
     * BBAN in Iran is 22 digits. Full IBAN is IR + 2 check digits + 22 BBAN digits = 26 chars.
     */
    protected function generateValidIban(string $bban22 = '0120000000001234567890'): string
    {
        $bban22 = str_pad(substr(preg_replace('/\D/', '', $bban22), 0, 22), 22, '0', STR_PAD_RIGHT);
        $checkDigits = IbanRule::calculateCheckDigits($bban22);

        return 'IR' . $checkDigits . $bban22;
    }

    protected function createOrder(int $total = 1500000): Order
    {
        $order = new Order([
            'customer_name' => 'تست مشتری',
            'customer_phone' => '09123456789',
        ]);
        $order->total_price = $total;
        $order->save();

        return $order;
    }

    protected function createOrderItem(Order $order, array $customizationJson): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_name_snapshot' => $this->product->name,
            'color_id' => $this->lightColor->id,
            'color_name_snapshot' => $this->lightColor->name,
            'unit_price_snapshot' => 1500000,
            'quantity' => 1,
            'final_price' => 1500000,
            'customization_json' => $customizationJson,
        ]);
    }

    // ==========================================
    // 1. IBAN Canonicalization & UX Tests
    // ==========================================

    public function test_valid_iranian_iban_passes_validation_and_is_canonicalized(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');
        $rule = new IbanRule();

        $failed = false;
        $failCallback = function ($message) use (&$failed) {
            $failed = true;
        };

        $rule->validate('iban', $validIban, $failCallback);
        $this->assertFalse($failed, 'Valid IBAN must pass validation');

        $canonicalized = IbanRule::canonicalize($validIban);
        $this->assertSame($validIban, $canonicalized);
        $this->assertTrue(IbanRule::passesMod97($canonicalized));
    }

    public function test_24_digits_without_ir_prefix_is_canonicalized_to_ir_plus_24_digits(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');
        $digitsOnly = substr($validIban, 2); // exactly 24 digits

        $canonicalized = IbanRule::canonicalize($digitsOnly);
        $this->assertSame($validIban, $canonicalized);
        $this->assertTrue(IbanRule::passesMod97($canonicalized));
    }

    public function test_full_iban_with_ir_does_not_duplicate_ir(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');

        $this->assertSame($validIban, IbanRule::canonicalize($validIban));
        $this->assertSame($validIban, IbanRule::canonicalize(strtolower($validIban)));
        $this->assertSame($validIban, IbanRule::canonicalize('IR' . $validIban));
        $this->assertSame($validIban, IbanRule::canonicalize('IR ' . $validIban));
    }

    public function test_iban_with_spaces_dashes_persian_digits_and_lowercase_is_canonicalized(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');
        $digits = substr($validIban, 2);

        // Convert some digits to Persian
        $persianDigits = str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
            $digits
        );

        $messyInput = ' ir - ' . substr($persianDigits, 0, 4) . ' ' . substr($persianDigits, 4);

        $canonicalized = IbanRule::canonicalize($messyInput);
        $this->assertSame($validIban, $canonicalized);
        $this->assertTrue(IbanRule::passesMod97($canonicalized));
    }

    public function test_invalid_mod97_iban_is_rejected(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');
        // Alter last digit to corrupt check digits
        $corruptIban = substr($validIban, 0, -1) . ($validIban[-1] === '5' ? '6' : '5');

        $rule = new IbanRule();
        $failMessage = null;
        $rule->validate('iban', $corruptIban, function ($msg) use (&$failMessage) {
            $failMessage = $msg;
        });

        $this->assertNotNull($failMessage);
        $this->assertStringContainsString('Mod-97', (string) $failMessage);
    }

    public function test_iban_with_wrong_length_is_rejected(): void
    {
        $rule = new IbanRule();
        $failMessage = null;
        $rule->validate('iban', 'IR12345', function ($msg) use (&$failMessage) {
            $failMessage = $msg;
        });

        $this->assertNotNull($failMessage);
        $this->assertStringContainsString('IR', (string) $failMessage);
    }

    public function test_blank_or_null_iban_is_allowed_as_it_is_optional(): void
    {
        $rules = BankCardCustomization::rulesFor(false, false);

        $validator1 = Validator::make(['iban' => ''], $rules);
        $this->assertFalse($validator1->fails());

        $validator2 = Validator::make(['iban' => null], $rules);
        $this->assertFalse($validator2->fails());
    }

    // ==========================================
    // 2. Custom Text Placement Tests
    // ==========================================

    public function test_custom_text_placement_defaults_to_back(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);
        $form = $component->get('bankCard');

        $form->back_text = 'BORN TO LEAD';
        $json = $form->customizationJson();

        $this->assertSame('back', $json['custom_text_placement']);
    }

    public function test_custom_text_placement_front_is_sanitized_and_stored(): void
    {
        $sanitized = BankCardCustomization::sanitize([
            'customization_json' => [
                'back_text' => 'FRONT EMBOSS',
                'custom_text_placement' => 'front',
            ],
        ]);

        $this->assertSame('front', $sanitized['custom_text_placement']);
        $this->assertSame('FRONT EMBOSS', $sanitized['back_text']);
    }

    public function test_invalid_custom_text_placement_is_rejected(): void
    {
        $validator = Validator::make(
            ['back_text' => 'SOME TEXT', 'custom_text_placement' => 'invalid_side'],
            BankCardCustomization::rulesFor(false, false)
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('custom_text_placement', $validator->errors()->toArray());
    }

    // ==========================================
    // 3. UI Component Integration & Preview Tests
    // ==========================================

    public function test_livewire_can_set_iban_and_text_placement_and_persists_to_cart(): void
    {
        $validIban = $this->generateValidIban('0540100000001234567890');

        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_number', '6274051234567898')
            ->set('bankCard.card_holder_name', 'HOSSEIN REZAIE')
            ->set('bankCard.back_text', 'LUXURY CUSTOM')
            ->set('bankCard.custom_text_placement', 'front')
            ->set('bankCard.iban', $validIban)
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertNotEmpty($cart['items']);
        $customization = $cart['items'][0]['customization_json'];

        $this->assertSame($validIban, $customization['iban']);
        $this->assertSame('front', $customization['custom_text_placement']);
        $this->assertSame('LUXURY CUSTOM', $customization['back_text']);
    }

    public function test_order_item_snapshot_preserves_iban_and_text_placement(): void
    {
        $validIban = $this->generateValidIban('0120000000001234567890');

        $order = $this->createOrder();
        $orderItem = $this->createOrderItem($order, [
            'card_number' => '6274051234567898',
            'card_holder_name' => 'ALI KARIMI',
            'back_text' => 'VIP MEMBER',
            'custom_text_placement' => 'front',
            'iban' => $validIban,
        ]);

        $itemSnapshot = $orderItem->fresh()->customization_json;
        $this->assertSame($validIban, $itemSnapshot['iban']);
        $this->assertSame('front', $itemSnapshot['custom_text_placement']);
    }

    public function test_back_preview_renders_iban_value_without_extra_label(): void
    {
        $validIban = $this->generateValidIban('0120000000001234567890');
        $formattedIban = CardPresenter::presentIban($validIban);

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('setStep', 2)
            ->call('setActiveView', 'back')
            ->set('bankCard.iban', $validIban);

        $html = $component->html();

        // Must see the formatted IBAN value
        $this->assertStringContainsString($formattedIban, $html);
        // Must NOT have the extra label "IBAN / شبا" in the preview
        $this->assertStringNotContainsString('IBAN / شبا', $html);
    }

    public function test_front_custom_text_is_not_rendered_on_back_view(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('setStep', 2)
            ->set('bankCard.back_text', 'SECRET FRONT PHRASE')
            ->set('bankCard.custom_text_placement', 'front')
            ->call('setActiveView', 'back');

        // On back view, the text must NOT appear in the preview slots
        $html = $component->html();
        $this->assertStringNotContainsString('SECRET FRONT PHRASE', $html);

        // Switching to front view, the text MUST appear
        $component->call('setActiveView', 'front');
        $htmlFront = $component->html();
        $this->assertStringContainsString('SECRET FRONT PHRASE', $htmlFront);
    }

    // ==========================================
    // 4. Admin Order Manager Tests
    // ==========================================

    public function test_admin_production_spec_displays_iban_and_copy_button(): void
    {
        $validIban = $this->generateValidIban('0120000000001234567890');
        $formattedIban = CardPresenter::presentIban($validIban);

        $order = $this->createOrder();
        $this->createOrderItem($order, [
            'card_number' => '6274051234567898',
            'card_holder_name' => 'ADMIN TEST BUYER',
            'back_text' => 'BORN TO LEAD',
            'custom_text_placement' => 'back',
            'iban' => $validIban,
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('شماره شبا:')
            ->assertSee($formattedIban)
            ->assertSee('کپی');
    }

    public function test_admin_production_spec_displays_custom_text_placement(): void
    {
        $order = $this->createOrder();
        $this->createOrderItem($order, [
            'card_number' => '6274051234567898',
            'card_holder_name' => 'ADMIN TEST BUYER',
            'back_text' => 'EMBOSSED ON FRONT',
            'custom_text_placement' => 'front',
        ]);

        $this->actingAs($this->adminUser);

        Livewire::test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('محل حکاکی جمله دلخواه:')
            ->assertSee('روی کارت')
            ->assertSee('EMBOSSED ON FRONT');
    }

    // ==========================================
    // 5. Layout & Slot Structure Tests
    // ==========================================

    public function test_back_layout_slots_hierarchy_and_no_overlap(): void
    {
        $slots = CardPresenter::fixedSlots();

        $this->assertArrayHasKey('iban', $slots);
        $this->assertArrayHasKey('card_number', $slots);
        $this->assertArrayHasKey('cvv2', $slots);
        $this->assertArrayHasKey('expiry', $slots);
        $this->assertArrayHasKey('custom_text', $slots);
        $this->assertArrayHasKey('back_text', $slots);
        $this->assertArrayHasKey('card_holder_name', $slots);
        $this->assertArrayHasKey('front_custom_text', $slots);

        // Strict vertical order:
        // IBAN (0.24) < Card Number (0.40) < CVV2/Expiry (0.54) < Custom Text (0.68) < Holder Name (0.81)
        $this->assertLessThan($slots['card_number']['y'], $slots['iban']['y'], 'IBAN must be above Card Number');
        $this->assertLessThan($slots['cvv2']['y'], $slots['card_number']['y'], 'Card Number must be above CVV2');
        $this->assertLessThan($slots['custom_text']['y'], $slots['cvv2']['y'], 'CVV2 must be above Custom Text');
        $this->assertLessThan($slots['card_holder_name']['y'], $slots['custom_text']['y'], 'Custom Text must be above Holder Name');

        // Holder Name must be the lowest main element on the back
        $this->assertGreaterThan($slots['iban']['y'], $slots['card_holder_name']['y']);
        $this->assertGreaterThan($slots['card_number']['y'], $slots['card_holder_name']['y']);
        $this->assertGreaterThan($slots['custom_text']['y'], $slots['card_holder_name']['y']);

        // Safe spacing: at least 10% distance between custom_text and card_holder_name to prevent overlap
        $this->assertGreaterThanOrEqual(0.10, $slots['card_holder_name']['y'] - $slots['custom_text']['y']);
    }

    // ==========================================
    // 6. Contrast Engine Tests
    // ==========================================

    public function test_contrast_engine_resolves_dark_text_for_light_backgrounds(): void
    {
        $this->assertSame('dark', CardPresenter::textColorMode('#FFFFFF'));
        $this->assertSame('dark', CardPresenter::textColorMode('#E5C158'));
        $this->assertSame('dark', CardPresenter::textColorMode('#C0C0C0'));
        $this->assertTrue(CardPresenter::isLightBackground('#FFFFFF'));
        $this->assertSame('text-slate-950', CardPresenter::backTextColorClass('#FFFFFF'));
    }

    public function test_contrast_engine_resolves_light_text_for_dark_backgrounds(): void
    {
        $this->assertSame('light', CardPresenter::textColorMode('#000000'));
        $this->assertSame('light', CardPresenter::textColorMode('#111827'));
        $this->assertSame('light', CardPresenter::textColorMode('#1E1E1E'));
        $this->assertFalse(CardPresenter::isLightBackground('#111827'));
        $this->assertSame('text-white', CardPresenter::backTextColorClass('#111827'));
    }

    // ==========================================
    // 7. Legacy Order Backward Compatibility Test
    // ==========================================

    public function test_legacy_order_without_placement_or_iban_renders_safely(): void
    {
        $order = $this->createOrder();
        $orderItem = $this->createOrderItem($order, [
            'card_number' => '6274051234567898',
            'card_holder_name' => 'LEGACY CUSTOMER',
            'back_text' => 'LEGACY ENGRAVING',
        ]);

        $customization = $orderItem->customization_json;
        $placement = $customization['custom_text_placement'] ?? 'back';
        $this->assertSame('back', $placement);

        $iban = $customization['iban'] ?? null;
        $this->assertNull($iban);
    }
}
