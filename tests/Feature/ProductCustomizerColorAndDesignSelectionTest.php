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
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizerColorAndDesignSelectionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private Color $colorWithoutDesigns;
    private Color $colorWithDesigns;
    private Design $activeDesign;
    private DesignImage $activeVariant;

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
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-metal-card',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        // Color 1: Cheaper, but has NO compatible designs
        $this->colorWithoutDesigns = Color::create([
            'name' => 'قرمز',
            'code_hex' => '#FF0000',
            'color_code' => '#FF0000',
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->colorWithoutDesigns->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        // Color 2: Higher price, but HAS active compatible designs
        $this->colorWithDesigns = Color::create([
            'name' => 'سفید صدفی',
            'code_hex' => '#FFFFFF',
            'color_code' => '#FFFFFF',
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->product->id,
            'color_id' => $this->colorWithDesigns->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        // Create active design and variant
        $this->activeDesign = Design::create([
            'name' => 'طرح ورزشی اول',
            'slug' => 'sports-design-1',
            'cate_design_id' => $category->id,
            'is_active' => true,
        ]);

        $this->activeVariant = DesignImage::create([
            'design_id' => $this->activeDesign->id,
            'color_id' => $this->colorWithDesigns->id,
            'image_path' => 'designs/sports-1.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Only Color 2 is compatible with this variant
        DesignColorCompatibility::create([
            'design_image_id' => $this->activeVariant->id,
            'card_color_id' => $this->colorWithDesigns->id,
            'is_allowed' => true,
        ]);
    }

    public function test_default_color_resolver_picks_color_with_active_compatible_designs(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->product->id,
        ])
            ->assertSet('color_id', $this->colorWithDesigns->id)
            ->assertDontSee('طرحی در این دسته‌بندی با رنگ انتخابی موجود نیست')
            ->assertSee($this->activeDesign->name);
    }

    public function test_user_manual_color_selection_is_not_overwritten_by_default_resolver(): void
    {
        // When user explicitly selects color 1 (via parameter or interaction)
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->product->id,
            'initialColorId' => $this->colorWithoutDesigns->id,
        ])
            ->assertSet('color_id', $this->colorWithoutDesigns->id);
    }

    public function test_cannot_advance_to_step_2_when_design_id_is_null_in_catalog_mode(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->product->id,
            'initialColorId' => $this->colorWithoutDesigns->id,
        ])
            ->assertSet('design_id', null)
            ->call('setStep', 2)
            ->assertSet('step', 1)
            ->assertHasErrors(['design_id'])
            ->assertSee('لطفاً ابتدا یک طرح از میان طرح‌های آماده انتخاب کنید');
    }

    public function test_successful_design_selection_advances_to_step_2_and_adds_to_cart(): void
    {
        Livewire::test(ProductCustomizer::class, [
            'productId' => $this->product->id,
        ])
            ->assertSet('color_id', $this->colorWithDesigns->id)
            ->set('design_id', $this->activeDesign->id)
            ->call('setStep', 2)
            ->assertSet('step', 2)
            ->assertHasNoErrors()
            ->set('bankCard.card_number', '6037991912345679') // Valid Luhn checksum
            ->set('bankCard.card_holder_name', 'محمد حسینی')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertEquals($this->product->id, $cart['items'][0]['product_id']);
        $this->assertEquals($this->colorWithDesigns->id, $cart['items'][0]['color_id']);
        $this->assertEquals($this->activeDesign->id, $cart['items'][0]['design_id']);
        $this->assertEquals(700000, $cart['items'][0]['unit_price_snapshot']);
    }
}
