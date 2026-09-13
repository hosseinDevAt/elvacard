<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Livewire\Forms\BankCardWorkspace;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Form;
use Livewire\Livewire;
use ReflectionClass;
use Tests\TestCase;

class BankCardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Color $color;

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

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح شیر',
            'slug' => 'lion-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/lion-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);
    }

    private function mountWorkspace(): BankCardWorkspace
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        return $component->get('bankCard');
    }

    public function test_is_a_livewire_form_bound_to_product_customizer(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $this->assertTrue($component->get('bankCard') instanceof Form);
        $this->assertTrue($component->get('bankCard') instanceof BankCardWorkspace);
    }

    public function test_default_state(): void
    {
        $component = Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id]);

        $component
            ->assertSet('bankCard.card_number', '')
            ->assertSet('bankCard.card_holder_name', '')
            ->assertSet('bankCard.back_text', '')
            ->assertSet('bankCard.cvv2', '')
            ->assertSet('bankCard.expiry_month', '')
            ->assertSet('bankCard.expiry_year', '')
            ->assertSet('bankCard.security_cvv_enabled', false)
            ->assertSet('bankCard.security_expiry_enabled', false);
    }

    public function test_accepts_valid_card_number(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_number', '6274051234567890')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];
        $this->assertSame('6274051234567890', $customization['card_number']);
    }

    public function test_rejects_invalid_card_number(): void
    {
        foreach (['1234567890', '123456789012345', '62740000000000001', '1234567890123456X', '6274 0512 3456 789'] as $cardNumber) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->set('bankCard.card_number', $cardNumber)
                ->call('addToCart')
                ->assertHasErrors(['bankCard.card_number' => 'digits']);
        }
    }

    public function test_persian_digits_are_canonicalized(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_number', '۶۲۷۴ ۰۵۱۲-۳۴۵۶ ۷۸۹۰')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];
        $this->assertSame('6274051234567890', $customization['card_number']);
    }

    public function test_arabic_digits_are_canonicalized(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_number', '٦٢٧٤٠٥١٢٣٤٥٦٧٨٩٠')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];
        $this->assertSame('6274051234567890', $customization['card_number']);
    }

    public function test_cvv_disabled_by_default_and_not_persisted(): void
    {
        $form = $this->mountWorkspace();
        $this->assertFalse($form->security_cvv_enabled);

        $result = $form->customizationJson();
        $this->assertFalse($result['security_cvv_enabled']);
        $this->assertArrayNotHasKey('cvv2', $result);

        $this->assertArrayNotHasKey('cvv2', $form->rules());
    }

    public function test_cvv_enabled_accepts_three_or_four_digits(): void
    {
        foreach (['123', '8080'] as $cvv) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleCvv')
                ->set('bankCard.cvv2', $cvv)
                ->call('addToCart')
                ->assertRedirect(route('cart.index'));

            $this->assertSame($cvv, app(CartService::class)->getCart()['items'][0]['customization_json']['cvv2']);
            app(CartService::class)->clear();
        }
    }

    public function test_invalid_cvv_rejected_when_enabled(): void
    {
        foreach (['12', '12A', '12345', 'ABC'] as $cvv) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleCvv')
                ->set('bankCard.cvv2', $cvv)
                ->call('addToCart')
                ->assertHasErrors(['bankCard.cvv2' => 'digits_between']);
        }
    }

    public function test_expiry_disabled_by_default_and_not_persisted(): void
    {
        $form = $this->mountWorkspace();
        $this->assertFalse($form->security_expiry_enabled);

        $result = $form->customizationJson();
        $this->assertFalse($result['security_expiry_enabled']);
        $this->assertArrayNotHasKey('expiry_month', $result);
        $this->assertArrayNotHasKey('expiry_year', $result);

        $this->assertArrayNotHasKey('expiry_month', $form->rules());
        $this->assertArrayNotHasKey('expiry_year', $form->rules());
    }

    public function test_expiry_enabled_accepts_valid_month_and_year(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->call('toggleExpiry')
            ->set('bankCard.expiry_month', '05')
            ->set('bankCard.expiry_year', (string) ((int) date('y') + 2))
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];
        $this->assertSame('05', $customization['expiry_month']);
        $this->assertSame((string) ((int) date('y') + 2), $customization['expiry_year']);
    }

    public function test_invalid_expiry_month_rejected(): void
    {
        foreach (['00', '13', '99', 'A1'] as $month) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleExpiry')
                ->set('bankCard.expiry_month', $month)
                ->set('bankCard.expiry_year', (string) ((int) date('y') + 2))
                ->call('addToCart')
                ->assertHasErrors(['bankCard.expiry_month' => 'regex']);
        }
    }

    public function test_invalid_expiry_year_rejected(): void
    {
        $currentShort = (int) date('y');

        foreach ([$currentShort - 2, $currentShort + 12, '1', '2029'] as $year) {
            Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
                ->call('toggleExpiry')
                ->set('bankCard.expiry_month', '05')
                ->set('bankCard.expiry_year', (string) $year)
                ->call('addToCart')
                ->assertHasErrors(['bankCard.expiry_year']);
        }
    }

    public function test_holder_name_longer_than_100_is_rejected(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_holder_name', str_repeat('A', 101))
            ->call('addToCart')
            ->assertHasErrors(['bankCard.card_holder_name' => 'max']);
    }

    public function test_back_text_longer_than_255_is_rejected(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.back_text', str_repeat('B', 256))
            ->call('addToCart')
            ->assertHasErrors(['bankCard.back_text' => 'max']);
    }

    public function test_customization_json_is_a_customer_owned_whitelist(): void
    {
        $form = $this->mountWorkspace();

        $form->card_number = '6274051234567890';
        $form->card_holder_name = ' HOSSEIN REZAIE ';
        $form->back_text = ' BORN TO LEAD ';
        $form->toggleCvv();
        $form->cvv2 = ' 808 ';
        $form->toggleExpiry();
        $form->expiry_month = '05';
        $form->expiry_year = (string) ((int) date('y') + 3);

        $this->assertSame([
            'security_cvv_enabled' => true,
            'security_expiry_enabled' => true,
            'card_number' => '6274051234567890',
            'card_holder_name' => 'HOSSEIN REZAIE',
            'back_text' => 'BORN TO LEAD',
            'cvv2' => '808',
            'expiry_month' => '05',
            'expiry_year' => (string) ((int) date('y') + 3),
        ], $form->customizationJson());
    }

    public function test_customization_json_excludes_commerce_and_presentation_data(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->product->id])
            ->set('bankCard.card_number', '1234657897897897')
            ->set('bankCard.card_holder_name', 'HOSSEIN REZAIE')
            ->set('bankCard.back_text', 'BORN TO LEAD')
            ->call('toggleCvv')
            ->set('bankCard.cvv2', '808')
            ->call('toggleExpiry')
            ->set('bankCard.expiry_month', '05')
            ->set('bankCard.expiry_year', (string) ((int) date('y') + 3))
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $customization = app(CartService::class)->getCart()['items'][0]['customization_json'];

        foreach (['product_id', 'color_id', 'design_id', 'design_image_id', 'product_name', 'color_name', 'design_name', 'design_image_path', 'price', 'positions', 'qr_code', 'qr_code_enabled', 'qr_code_path'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $customization);
        }

        foreach (array_keys($customization) as $key) {
            $this->assertStringNotContainsString('qr_code', strtolower((string) $key));
            $this->assertStringNotContainsString('position', strtolower((string) $key));
        }
    }

    public function test_canonicalize_normalizes_card_number(): void
    {
        $form = $this->mountWorkspace();

        $form->card_number = ' ۶۲۷۴-۰۵۱۲ ۳۴۵۶ ۷۸۹۰ ';
        $form->canonicalize();

        $this->assertSame('6274051234567890', $form->card_number);
    }

    public function test_toggling_cvv_clears_cvv2_when_disabled(): void
    {
        $form = $this->mountWorkspace();

        $form->toggleCvv();
        $this->assertTrue($form->security_cvv_enabled);

        $form->cvv2 = '808';
        $form->toggleCvv();
        $this->assertFalse($form->security_cvv_enabled);
        $this->assertSame('', $form->cvv2);
    }

    public function test_toggling_expiry_clears_month_and_year_when_disabled(): void
    {
        $form = $this->mountWorkspace();

        $form->toggleExpiry();
        $this->assertTrue($form->security_expiry_enabled);

        $form->expiry_month = '05';
        $form->expiry_year = '29';
        $form->toggleExpiry();
        $this->assertFalse($form->security_expiry_enabled);
        $this->assertSame('', $form->expiry_month);
        $this->assertSame('', $form->expiry_year);
    }

    public function test_rules_delegate_to_bank_card_customization(): void
    {
        $form = $this->mountWorkspace();

        $this->assertSame(
            BankCardCustomization::rulesFor(false, false),
            $form->rules()
        );

        $form->toggleCvv();
        $form->toggleExpiry();

        $this->assertSame(
            BankCardCustomization::rulesFor(true, true),
            $form->rules()
        );
    }

    public function test_messages_delegate_to_bank_card_customization(): void
    {
        $form = $this->mountWorkspace();

        $this->assertSame(BankCardCustomization::messages(), $form->messages());
    }

    public function test_product_customizer_no_longer_owns_bank_card_state_or_implementations(): void
    {
        $reflection = new ReflectionClass(ProductCustomizer::class);

        foreach (['card_number', 'card_holder_name', 'back_text', 'cvv2', 'expiry_month', 'expiry_year', 'security_cvv_enabled', 'security_expiry_enabled'] as $property) {
            $this->assertFalse($reflection->hasProperty($property), 'ProductCustomizer must not declare ['.$property.'].');
        }

        $this->assertTrue($reflection->hasProperty('bankCard'));
        $this->assertFalse($reflection->hasMethod('canonicalizeCardNumber'));
        $this->assertFalse($reflection->hasMethod('sanitizeCardCustomization'));
    }
}
