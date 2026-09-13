<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizerTest extends TestCase
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
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی کلاسیک',
            'slug' => 'classic-metal-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        $this->color = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
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

    public function test_customizer_initializes_with_clean_default_state(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->assertSet('card_holder_name', '')
            ->assertSet('back_text', '')
            ->assertSet('security_cvv_enabled', false)
            ->assertSet('security_expiry_enabled', false)
            ->assertSet('step', 1)
            ->assertSet('activeView', 'front');
    }

    public function test_toggles_operate_independently(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Toggle CVV only
        $component->call('toggleCvv')
            ->assertSet('security_cvv_enabled', true)
            ->assertSet('security_expiry_enabled', false);

        // Toggle Expiry only
        $component->call('toggleExpiry')
            ->assertSet('security_cvv_enabled', true)
            ->assertSet('security_expiry_enabled', true);

        // Untoggle CVV
        $component->call('toggleCvv')
            ->assertSet('security_cvv_enabled', false)
            ->assertSet('security_expiry_enabled', true);
    }

    public function test_add_to_cart_with_clean_defaults_produces_clean_snapshot(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);

        $item = $cart['items'][0];
        $customization = $item['customization_json'];

        $this->assertFalse($customization['security_cvv_enabled']);
        $this->assertFalse($customization['security_expiry_enabled']);
        $this->assertArrayNotHasKey('card_holder_name', $customization);
        $this->assertArrayNotHasKey('back_text', $customization);
        $this->assertArrayNotHasKey('expiry_month', $customization);
        $this->assertArrayNotHasKey('expiry_year', $customization);
    }

    public function test_add_to_cart_stores_only_user_typed_customizations(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_holder_name', ' HOSSEIN REZAIE ')
            ->set('back_text', ' BORN TO LEAD ')
            ->call('toggleExpiry')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $item = $cart['items'][0];
        $customization = $item['customization_json'];

        $this->assertSame('HOSSEIN REZAIE', $customization['card_holder_name']);
        $this->assertSame('BORN TO LEAD', $customization['back_text']);
        $this->assertTrue($customization['security_expiry_enabled']);
        $this->assertFalse($customization['security_cvv_enabled']);
        $this->assertArrayNotHasKey('expiry_month', $customization);
        $this->assertArrayNotHasKey('expiry_year', $customization);
    }

    public function test_cart_service_whitelist_filters_unauthorized_keys(): void
    {
        $cartService = app(CartService::class);

        $payload = [
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_holder_name' => 'ALI REZA',
                'security_cvv_enabled' => true,
                'sample_card_number' => '6274000000000000',
                'sample_cvv' => '123',
                'expiry_month' => '05',
                'expiry_year' => '29',
                'qr_code_enabled' => true,
                'qr_code_path' => 'customizations/qr_codes/legacy.png',
                'random_injected_field' => 'hacked',
            ],
        ];

        $result = $cartService->addItem($payload);
        $item = $result['items'][0];
        $customization = $item['customization_json'];

        $this->assertSame('ALI REZA', $customization['card_holder_name']);
        $this->assertTrue($customization['security_cvv_enabled']);
        $this->assertArrayNotHasKey('sample_card_number', $customization);
        $this->assertArrayNotHasKey('sample_cvv', $customization);
        $this->assertArrayNotHasKey('expiry_month', $customization);
        $this->assertArrayNotHasKey('expiry_year', $customization);
        $this->assertArrayNotHasKey('qr_code_enabled', $customization);
        $this->assertArrayNotHasKey('qr_code_path', $customization);
        $this->assertArrayNotHasKey('random_injected_field', $customization);
    }

    public function test_card_number_must_be_exactly_16_digits(): void
    {
        $invalidCardNumbers = ['1234567890', '123456789012345', '62740000000000001', '6274-0512-3456-789'];

        foreach ($invalidCardNumbers as $cardNumber) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->set('card_number', $cardNumber)
                ->call('addToCart')
                ->assertHasErrors(['card_number' => 'digits']);
        }
    }

    public function test_card_number_is_canonicalized_before_storage(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_number', ' 6274 0512 3456 7890 ')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $customization = $cart['items'][0]['customization_json'];

        $this->assertSame('6274051234567890', $customization['card_number']);
    }

    public function test_card_number_digits_only_rejects_letters(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_number', '6274-0512-3456-789X')
            ->call('addToCart')
            ->assertHasErrors(['card_number' => 'digits']);
    }

    public function test_cvv2_must_be_3_to_4_digits_when_enabled(): void
    {
        foreach (['12', '12A', '12345', 'ABC', '12 3'] as $cvv) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleCvv')
                ->set('cvv2', $cvv)
                ->call('addToCart')
                ->assertHasErrors(['cvv2' => 'digits_between']);
        }
    }

    public function test_cvv2_accepts_3_or_4_digits(): void
    {
        foreach (['123', '8080'] as $cvv) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleCvv')
                ->set('cvv2', $cvv)
                ->call('addToCart')
                ->assertRedirect(route('cart.index'));

            $cart = app(CartService::class)->getCart();
            $this->assertSame($cvv, $cart['items'][0]['customization_json']['cvv2']);

            app(CartService::class)->clear();
        }
    }

    public function test_cvv2_not_validated_when_toggle_disabled(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('cvv2', '12')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'))
            ->assertHasNoErrors(['cvv2']);

        $cart = app(CartService::class)->getCart();
        $this->assertArrayNotHasKey('cvv2', $cart['items'][0]['customization_json']);
    }

    public function test_expiry_month_must_be_between_01_and_12(): void
    {
        foreach (['00', '13', '99', 'A1'] as $month) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleExpiry')
                ->set('expiry_month', $month)
                ->set('expiry_year', (string) ((int) date('y') + 2))
                ->call('addToCart')
                ->assertHasErrors(['expiry_month' => 'regex']);
        }
    }

    public function test_expiry_year_must_be_in_valid_range(): void
    {
        $currentShort = (int) date('y');
        $validYear = (string) ($currentShort + 5);
        $pastYear = (string) ($currentShort - 2);
        $tooFarYear = (string) ($currentShort + 12);

        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('toggleExpiry')
            ->set('expiry_month', '05')
            ->set('expiry_year', $validYear)
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        foreach ([$pastYear, $tooFarYear, '1', '2029'] as $year) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleExpiry')
                ->set('expiry_month', '05')
                ->set('expiry_year', $year)
                ->call('addToCart')
                ->assertHasErrors(['expiry_year']);
        }
    }

    public function test_expiry_not_validated_when_toggle_disabled(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('expiry_month', '00')
            ->set('expiry_year', '00')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'))
            ->assertHasNoErrors(['expiry_month', 'expiry_year']);

        $cart = app(CartService::class)->getCart();
        $this->assertArrayNotHasKey('expiry_month', $cart['items'][0]['customization_json']);
        $this->assertArrayNotHasKey('expiry_year', $cart['items'][0]['customization_json']);
    }

    public function test_component_has_no_user_position_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->assertSet('positions', null);

        // The interactive drag state is gone: no positions property remains.
        $this->assertArrayNotHasKey('positions', get_object_vars($component->instance()));
    }

    public function test_fixed_slots_are_defined_for_all_back_card_elements(): void
    {
        $slots = ProductCustomizer::fixedSlots();

        foreach (['card_number', 'card_holder_name', 'back_text', 'cvv2', 'expiry'] as $element) {
            $this->assertArrayHasKey($element, $slots);
            $this->assertArrayHasKey('x', $slots[$element]);
            $this->assertArrayHasKey('y', $slots[$element]);
            $this->assertGreaterThanOrEqual(0.0, $slots[$element]['x']);
            $this->assertLessThanOrEqual(1.0, $slots[$element]['x']);
            $this->assertGreaterThanOrEqual(0.0, $slots[$element]['y']);
            $this->assertLessThanOrEqual(1.0, $slots[$element]['y']);
        }
    }

    public function test_cart_service_drops_positions_from_customization(): void
    {
        $cartService = app(CartService::class);

        $payload = [
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_holder_name' => 'ALI REZA',
                'positions' => [
                    'card_holder_name' => ['x' => 0.3, 'y' => 0.4],
                    'card_number' => ['x' => 0.5, 'y' => 0.5],
                ],
            ],
        ];

        $result = $cartService->addItem($payload);
        $customization = $result['items'][0]['customization_json'];

        $this->assertSame('ALI REZA', $customization['card_holder_name']);
        // Legacy/forged positions are never carried into new snapshots.
        $this->assertArrayNotHasKey('positions', $customization);
    }

    public function test_snapshot_has_no_positions_when_configuration_is_complete(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_number', '1234657897897897')
            ->set('card_holder_name', 'HOSSEIN REZAIE')
            ->set('back_text', 'BORN TO LEAD')
            ->call('toggleCvv')
            ->set('cvv2', '808')
            ->call('toggleExpiry')
            ->set('expiry_month', '05')
            ->set('expiry_year', (string) ((int) date('y') + 3))
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];

        $this->assertSame('1234657897897897', $customization['card_number']);
        $this->assertSame('HOSSEIN REZAIE', $customization['card_holder_name']);
        $this->assertSame('BORN TO LEAD', $customization['back_text']);
        $this->assertSame('808', $customization['cvv2']);
        $this->assertSame('05', $customization['expiry_month']);
        $this->assertArrayNotHasKey('qr_code_enabled', $customization);
        $this->assertArrayNotHasKey('qr_code_path', $customization);
        $this->assertArrayNotHasKey('positions', $customization);
    }

    public function test_snapshot_never_contains_qr_code_keys(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_number', '1234657897897897')
            ->set('card_holder_name', 'HOSSEIN REZAIE')
            ->set('back_text', 'BORN TO LEAD')
            ->call('toggleCvv')
            ->set('cvv2', '808')
            ->call('toggleExpiry')
            ->set('expiry_month', '05')
            ->set('expiry_year', (string) ((int) date('y') + 3))
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);

        $customization = $cart['items'][0]['customization_json'];

        foreach (array_keys($customization) as $key) {
            $this->assertStringNotContainsString('qr_code', strtolower((string) $key));
        }
    }

    public function test_cart_service_rejects_invalid_card_number_and_cvv2(): void
    {
        $cartService = app(CartService::class);

        $payload = [
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '1234-5678', // not 16 digits
                'security_cvv_enabled' => true,
                'cvv2' => '12XY', // not 3-4 digits
            ],
        ];

        $result = $cartService->addItem($payload);
        $customization = $result['items'][0]['customization_json'];

        $this->assertArrayNotHasKey('card_number', $customization);
        $this->assertArrayNotHasKey('cvv2', $customization);
    }

    public function test_cart_service_canonicalizes_card_number_with_separators(): void
    {
        $cartService = app(CartService::class);

        $payload = [
            'product_id' => $this->product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274 0512-3456 7890',
            ],
        ];

        $result = $cartService->addItem($payload);
        $customization = $result['items'][0]['customization_json'];

        $this->assertSame('6274051234567890', $customization['card_number']);
    }

    public function test_end_to_end_snapshot_is_persisted_without_regeneration(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_holder_name', 'HOSSEIN REZAIE')
            ->set('card_number', ' 6274 0512 3456 7890 ')
            ->set('back_text', 'BORN TO LEAD')
            ->call('toggleCvv')
            ->set('cvv2', '808')
            ->call('toggleExpiry')
            ->set('expiry_month', '05')
            ->set('expiry_year', (string) ((int) date('y') + 3))
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cartService = app(CartService::class);
        $cart = $cartService->getCart();
        $snapshotInCart = $cart['items'][0]['customization_json'];

        $customerData = [
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ];
        $order = $cartService->createDraftOrder($customerData);

        $orderItem = OrderItem::query()->where('order_id', $order->id)->first();
        $this->assertNotNull($orderItem);

        $this->assertSame($snapshotInCart, $orderItem->customization_json);

        $this->assertSame('6274051234567890', $orderItem->customization_json['card_number']);
        $this->assertSame('808', $orderItem->customization_json['cvv2']);
        $this->assertSame('05', $orderItem->customization_json['expiry_month']);
        $this->assertArrayNotHasKey('positions', $orderItem->customization_json);
    }

    public function test_card_number_above_16_digits_is_rejected(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('card_number', '627405123456789012')
            ->call('addToCart')
            ->assertHasErrors(['card_number' => 'digits']);
    }

    public function test_cvv2_above_4_digits_is_rejected(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('toggleCvv')
            ->set('cvv2', '80805')
            ->call('addToCart')
            ->assertHasErrors(['cvv2' => 'digits_between']);
    }

    public function test_present_card_number_groups_digits_by_four(): void
    {
        $this->assertSame('6274 0512 3456 7890', ProductCustomizer::presentCardNumber('6274051234567890'));
        $this->assertSame('6274 0512 34', ProductCustomizer::presentCardNumber('6274051234'));
        $this->assertSame('6274 0512 3456 7890', ProductCustomizer::presentCardNumber('6274-0512-3456-7890'));
        $this->assertSame('', ProductCustomizer::presentCardNumber(''));
    }

    public function test_present_card_number_preserves_digit_order(): void
    {
        // The group order must never be reversed by RTL/bidi rendering logic:
        // 1234 stays first, 7897 stays last.
        $this->assertSame('1234 6578 9789 7897', ProductCustomizer::presentCardNumber('1234657897897897'));
    }

    public function test_display_card_number_is_presentation_only_and_never_stored(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->set('card_number', '6274051234567890');
        $this->assertSame('6274 0512 3456 7890', $component->get('displayCardNumber'));

        $component->set('card_number', '6274 0512 3456 7890')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $customization = $cart['items'][0]['customization_json'];

        // The snapshot must keep the canonical form; presentation never leaks in.
        $this->assertSame('6274051234567890', $customization['card_number']);
        $this->assertStringNotContainsString(' ', $customization['card_number']);
        $this->assertSame('6274 0512 3456 7890', ProductCustomizer::presentCardNumber($customization['card_number']));
    }

    public function test_display_card_number_computed_property_formats_live_input(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component->set('card_number', '6274');
        $this->assertSame('6274', $component->get('displayCardNumber'));

        $component->set('card_number', '62740512');
        $this->assertSame('6274 0512', $component->get('displayCardNumber'));

        $component->set('card_number', '627405123456');
        $this->assertSame('6274 0512 3456', $component->get('displayCardNumber'));
    }
}
