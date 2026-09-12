<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
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
            ->assertSet('qr_code_enabled', false)
            ->assertSet('step', 1)
            ->assertSet('activeView', 'front');
    }

    public function test_toggles_operate_independently(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        // Toggle CVV only
        $component->call('toggleCvv')
            ->assertSet('security_cvv_enabled', true)
            ->assertSet('security_expiry_enabled', false)
            ->assertSet('qr_code_enabled', false);

        // Toggle Expiry only
        $component->call('toggleExpiry')
            ->assertSet('security_cvv_enabled', true)
            ->assertSet('security_expiry_enabled', true)
            ->assertSet('qr_code_enabled', false);

        // Toggle QR Code
        $component->call('toggleQrCode')
            ->assertSet('security_cvv_enabled', true)
            ->assertSet('security_expiry_enabled', true)
            ->assertSet('qr_code_enabled', true);

        // Untoggle CVV
        $component->call('toggleCvv')
            ->assertSet('security_cvv_enabled', false)
            ->assertSet('security_expiry_enabled', true)
            ->assertSet('qr_code_enabled', true);
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
        $this->assertFalse($customization['qr_code_enabled']);
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
        $this->assertFalse($customization['qr_code_enabled']);
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
        $this->assertArrayNotHasKey('random_injected_field', $customization);
    }
}
