<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\MenuItemTypeEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomDesignEntryFlowTest extends TestCase
{
    use RefreshDatabase;

    private Product $bankProduct;

    private Product $fuelProduct;

    private int $fuelCategoryId;

    private int $fuelDesignId;

    private Product $commerceProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bankProduct = $this->createPurchasableBankProduct();

        $this->fuelProduct = $this->createPurchasableFuelProduct();

        $this->commerceProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'کیف چرمی لوکس',
            'slug' => 'luxury-leather-bag',
            'base_price' => 250000,
            'is_active' => true,
        ]);
    }

    public function test_route_urls_and_names_are_stable(): void
    {
        $this->assertSame('/design', route('custom-card.design', [], false));
        $this->assertSame('/design/bank', route('custom-card.bank', [], false));
        $this->assertSame('/design/fuel', route('custom-card.fuel', [], false));
    }

    public function test_design_landing_is_a_bank_fuel_chooser_without_store_ctas(): void
    {
        $response = $this->get(route('custom-card.design'));

        $response->assertOk();
        $response->assertSee('href="'.route('custom-card.bank').'"', false);
        $response->assertSee('href="'.route('custom-card.fuel').'"', false);
        $response->assertSee('طراحی کارت بانکی');
        $response->assertSee('طراحی کارت سوخت');
        $response->assertDontSee('انتخاب محصول');
        $response->assertDontSee('مشاهده طرح‌ها');
    }

    public function test_bank_entry_renders_the_standalone_designer(): void
    {
        $response = $this->get(route('custom-card.bank'));

        $response->assertOk();
        $response->assertSee('طراحی کارت بانکی');
        $response->assertSee($this->bankProduct->name);
        $response->assertSee('اطلاعات و انتخاب طرح روی کارت');
        $response->assertSee('مرحله بعد: اطلاعات پشت کارت');
    }

    public function test_bank_designer_still_reaches_cart(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->bankProduct->id])
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertSame($this->bankProduct->id, (int) $cart['items'][0]['product_id']);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($this->bankProduct->name);
    }

    public function test_bank_entry_404s_without_a_purchasable_bank_product(): void
    {
        $this->bankProduct->update(['is_active' => false]);

        $this->get(route('custom-card.bank'))->assertNotFound();
    }

    public function test_fuel_entry_renders_the_standalone_designer(): void
    {
        $response = $this->get(route('custom-card.fuel'));

        $response->assertOk();
        $response->assertSee('طراحی کارت سوخت');
        $response->assertSee($this->fuelProduct->name);
        $response->assertSee('اطلاعات و انتخاب طرح روی کارت');
        $response->assertSee('مرحله بعد: اطلاعات پشت کارت');
        $response->assertDontSee('به‌زودی فعال می‌شود');
    }

    public function test_fuel_designer_collects_customization_and_reaches_cart(): void
    {
        Livewire::test(ProductCustomizer::class, ['productId' => $this->fuelProduct->id])
            ->call('selectCategory', $this->fuelCategoryId)
            ->call('selectDesign', $this->fuelDesignId)
            ->call('setStep', 2)
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.car_info', 'پژو ۲۰۶ مدل ۱۴۰۰')
            ->set('fuelCard.vin', 'IRABCDEFGH1234567')
            ->set('fuelCard.system_name', 'سامانه هوشمند سوخت')
            ->set('fuelCard.plate_number', '۱۲ م ۳۴۵ ایران')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasNoErrors()
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);

        $customization = $cart['items'][0]['customization_json'];
        $this->assertSame('علی رضایی', $customization['owner_name']);
        $this->assertSame('IRABCDEFGH1234567', $customization['vin']);
        $this->assertSame('سامانه هوشمند سوخت', $customization['system_name']);
        $this->assertArrayNotHasKey('card_number', $customization);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($this->fuelProduct->name);
    }

    public function test_fuel_entry_404s_without_a_purchasable_fuel_product(): void
    {
        $this->fuelProduct->update(['is_active' => false]);

        $this->get(route('custom-card.fuel'))->assertNotFound();
    }

    public function test_store_catalog_and_ordinary_product_purchasing_remain_unchanged(): void
    {
        $this->get(route('catalog.products.index'))
            ->assertOk()
            ->assertSee($this->commerceProduct->name);

        $this->get(route('catalog.products.show', $this->commerceProduct->slug))
            ->assertOk()
            ->assertSee($this->commerceProduct->name);

        $this->post(route('cart.add'), ['product_id' => $this->commerceProduct->id])
            ->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee($this->commerceProduct->name);
    }

    public function test_store_product_page_for_bank_card_still_embeds_the_designer(): void
    {
        $this->get(route('catalog.products.show', $this->bankProduct->slug))
            ->assertOk()
            ->assertSee('اطلاعات و انتخاب طرح روی کارت')
            ->assertSee('مرحله بعد: اطلاعات پشت کارت');
    }

    public function test_header_design_menu_item_resolves_to_the_standalone_landing(): void
    {
        $menu = Menu::create(['name' => 'Header', 'location' => 'header']);

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'item_type' => MenuItemTypeEnum::URL->value,
            'route_key' => 'custom_card_design',
            'title' => 'طراحی کارت اختصاصی',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->assertSame(route('custom-card.design'), $item->resolveUrl());

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('custom-card.design').'" wire:navigate', false)
            ->assertSee('طراحی کارت اختصاصی');
    }

    private function createPurchasableBankProduct(): Product
    {
        $category = CateDesign::create([
            'name' => 'ورزشی',
            'slug' => 'sports',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی اختصاصی',
            'slug' => 'bank-card',
            'base_price' => 850000,
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'color_code' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 850000,
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
            'color_id' => $color->id,
            'image_path' => 'designs/lion-gold.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $product;
    }

    private function createPurchasableFuelProduct(): Product
    {
        $category = CateDesign::create([
            'name' => 'خودرویی',
            'slug' => 'fuel-vehicle',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->fuelCategoryId = $category->id;

        $product = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت اختصاصی',
            'slug' => 'fuel-card',
            'base_price' => 450000,
            'is_active' => true,
        ]);

        $color = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#1a1a1a',
            'color_code' => '#1a1a1a',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 450000,
            'is_active' => true,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح خطوط سوخت',
            'slug' => 'fuel-lines-design',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->fuelDesignId = $design->id;

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/fuel-lines-black.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $product;
    }
}
