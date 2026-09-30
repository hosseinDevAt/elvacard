<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreProductPricingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function color(string $name = 'طلایی'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function storeProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف فروشگاهی',
            'slug' => 'store-pricing-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ], $overrides));
    }

    private function cardProduct(CustomizationWorkflowEnum $workflow): Product
    {
        return Product::create([
            'type' => $workflow === CustomizationWorkflowEnum::BANK_CARD ? ProductTypeEnum::BANK->value : ProductTypeEnum::FUEL->value,
            'customization_workflow' => $workflow->value,
            'name' => $workflow === CustomizationWorkflowEnum::BANK_CARD ? 'کارت بانکی فروش' : 'کارت سوخت فروش',
            'slug' => ($workflow === CustomizationWorkflowEnum::BANK_CARD ? 'bank-' : 'fuel-').'store-pricing-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function variant(Product $product, Color $color, int $price, bool $isActive = true): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    public function test_store_products_appear_in_the_products_list(): void
    {
        $this->storeProduct(['name' => 'کیف چرمی فروشگاه']);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertOk()
            ->assertSee('کیف چرمی فروشگاه');
    }

    public function test_store_products_do_not_appear_in_card_pricing_list(): void
    {
        $store = $this->storeProduct(['name' => 'کالای فروشگاهی جدا']);
        $this->variant($store, $this->color('مسی'), 250000);

        $bank = $this->cardProduct(CustomizationWorkflowEnum::BANK_CARD);
        $this->variant($bank, $this->color('آبی بانکی'), 700000);

        $fuel = $this->cardProduct(CustomizationWorkflowEnum::FUEL_CARD);
        $this->variant($fuel, $this->color('سبز سوختی'), 480000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertSee($bank->name)
            ->assertSee($fuel->name)
            ->assertDontSee($store->name);
    }

    public function test_card_pricing_save_rejects_a_store_product(): void
    {
        $store = $this->storeProduct();
        $color = $this->color();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $store->id)
            ->set('colorId', $color->id)
            ->set('price', 300000)
            ->call('save')
            ->assertSessionMissing('success');

        $this->assertSame(0, ProductColorPrice::where('product_id', $store->id)->count());
    }

    public function test_card_pricing_edit_rejects_a_store_product_row(): void
    {
        $store = $this->storeProduct();
        $color = $this->color();
        $row = $this->variant($store, $color, 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('edit', $row->id)
            ->assertSessionMissing('success')
            ->assertSet('editingId', null)
            ->assertSet('showForm', false);
    }

    public function test_card_pricing_delete_rejects_a_store_product_row(): void
    {
        $store = $this->storeProduct();
        $color = $this->color();
        $row = $this->variant($store, $color, 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $row->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('product_color_prices', ['id' => $row->id]);
    }

    public function test_card_pricing_image_actions_reject_store_product_galleries(): void
    {
        $store = $this->storeProduct();
        $color = $this->color();
        $this->variant($store, $color, 300000);

        $image = ProductImage::create([
            'product_id' => $store->id,
            'color_id' => $color->id,
            'image_path' => 'products/store.png',
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('setPrimaryImage', $image->id)
            ->assertSessionMissing('success');

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('deleteImage', $image->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('product_images', ['id' => $image->id]);
    }

    public function test_card_pricing_ignores_a_store_product_reference_from_the_query_string(): void
    {
        $store = $this->storeProduct();

        Livewire::actingAs($this->admin())
            ->withQueryParams(['product' => (string) $store->id])
            ->test(ProductColorPriceManager::class)
            ->assertSet('productId', null);
    }

    public function test_card_pricing_product_selector_exposes_only_card_products(): void
    {
        $store = $this->storeProduct(['name' => 'کالای انتخابی فروشگاه']);
        $bank = $this->cardProduct(CustomizationWorkflowEnum::BANK_CARD);
        $fuel = $this->cardProduct(CustomizationWorkflowEnum::FUEL_CARD);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        $options = array_column($component->get('productOptions'), 'id');

        $this->assertContains($bank->id, $options);
        $this->assertContains($fuel->id, $options);
        $this->assertNotContains($store->id, $options);
    }

    public function test_product_manager_exposes_simple_pricing_model(): void
    {
        $this->storeProduct(['name' => 'محصول عادی فروش', 'base_price' => 150000]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertSee('عادی')
            ->assertSee('150,000');
    }

    public function test_product_manager_exposes_variable_pricing_model_and_discount_price(): void
    {
        $product = $this->storeProduct(['name' => 'محصول متغیر فروش', 'base_price' => 100000]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertSee('متغیر')
            ->assertSee('از 300,000 تومان');
    }

    public function test_store_simple_price_is_saved_from_product_manager(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'کالای عادی جدید')
            ->set('basePrice', 250000)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(250000, Product::firstOrFail()->base_price);
    }

    public function test_store_variant_price_is_saved_edited_and_deleted_from_product_manager(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $colorA = $this->color('طلایی');
        $colorB = $this->color('نقره‌ای');

        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id);

        $component->set('variantColorId', $colorA->id)
            ->set('variantPrice', 300000)
            ->set('variantIsActive', true)
            ->call('saveVariant')
            ->assertSessionMissing('error')
            ->assertHasNoErrors();

        $rowA = ProductColorPrice::where('product_id', $product->id)->where('color_id', $colorA->id)->firstOrFail();
        $this->assertSame(300000, $rowA->price);

        $component->set('variantColorId', $colorB->id)
            ->set('variantPrice', 350000)
            ->call('saveVariant')
            ->assertHasNoErrors();

        $rowB = ProductColorPrice::where('product_id', $product->id)->where('color_id', $colorB->id)->firstOrFail();
        $this->assertSame(350000, $rowB->price);

        $component->set('editingVariantId', $rowB->id)
            ->set('variantColorId', $colorB->id)
            ->set('variantPrice', 380000)
            ->set('variantIsActive', true)
            ->call('saveVariant')
            ->assertHasNoErrors();

        $this->assertSame(380000, $rowB->fresh()->price);

        $component->call('deleteVariant', $rowB->id)
            ->assertSessionMissing('error');

        $this->assertDatabaseMissing('product_color_prices', ['id' => $rowB->id]);
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->count());
    }

    public function test_store_variant_duplicate_color_is_rejected(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $color = $this->color();
        $this->variant($product, $color, 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('variantColorId', $color->id)
            ->set('variantPrice', 500000)
            ->call('saveVariant')
            ->assertSessionMissing('success');

        $this->assertSame(300000, ProductColorPrice::where('product_id', $product->id)->firstOrFail()->price);
    }

    public function test_store_variant_management_is_rejected_for_card_products(): void
    {
        $card = $this->cardProduct(CustomizationWorkflowEnum::BANK_CARD);
        $color = $this->color();
        $this->variant($card, $color, 500000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $card->id)
            ->set('variantColorId', $color->id)
            ->set('variantPrice', 600000)
            ->call('saveVariant')
            ->assertSessionMissing('success');

        $this->assertSame(1, ProductColorPrice::where('product_id', $card->id)->count());

        $row = ProductColorPrice::where('product_id', $card->id)->firstOrFail();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('deleteVariant', $row->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('product_color_prices', ['id' => $row->id]);
    }

    public function test_create_form_exposes_pricing_type_selector_and_defaults_to_simple(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('pricingType', 'simple')
            ->assertSee('نوع قیمت‌گذاری')
            ->assertSee('محصول عادی')
            ->assertSee('محصول متغیر');
    }

    public function test_create_simple_store_product_via_pricing_type_selector(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('pricingType', 'simple')
            ->set('name', 'کالای عادی از انتخاب نوع قیمت')
            ->set('basePrice', 275000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $product = Product::firstOrFail();
        $this->assertSame(275000, $product->base_price);
        $this->assertSame(0, ProductColorPrice::where('product_id', $product->id)->count());
    }

    public function test_create_variable_store_product_saves_without_base_price_and_variants(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('pricingType', 'variable')
            ->set('name', 'کالای متغیر جدید')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertNull($product->customization_workflow);
        $this->assertNull($product->base_price);
        $this->assertSame(0, ProductColorPrice::where('product_id', $product->id)->count());
        $this->assertFalse($product->is_active);
    }

    public function test_switching_a_simple_product_to_variable_clears_the_persisted_base_price(): void
    {
        $product = $this->storeProduct(['base_price' => 100000, 'is_active' => false]);
        $this->variant($product, $this->color(), 300000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'variable')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(
            $product->fresh()->base_price,
            'A variable product must not keep a base price: CartService falls back to it whenever a request omits color_id.'
        );
    }

    public function test_a_variable_product_never_charges_its_old_base_price(): void
    {
        $product = $this->storeProduct(['base_price' => 100000, 'is_active' => false]);
        $this->variant($product, $this->color(), 300000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->expectException(\InvalidArgumentException::class);

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => null,
            'quantity' => 1,
        ]);
    }

    public function test_activation_of_variable_product_without_active_variant_is_rejected(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('pricingType', 'variable')
            ->set('name', 'کالای متغیر فعال بدون رنگ')
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('pricingType');

        $this->assertSame(0, Product::count(), 'A rejected activation must not persist the product.');
    }

    public function test_activation_of_variable_product_with_only_inactive_variants_is_rejected(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $this->variant($product, $this->color(), 300000, false);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', $product->name)
            ->set('pricingType', 'variable')
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors('pricingType');

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_activation_of_variable_product_with_active_variant_needs_no_base_price(): void
    {
        $product = $this->storeProduct(['is_active' => false, 'base_price' => null]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', $product->name)
            ->set('pricingType', 'variable')
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($product->fresh()->is_active);
        $this->assertNull($product->fresh()->base_price);
    }

    public function test_edit_loads_simple_product_as_simple(): void
    {
        $product = $this->storeProduct(['base_price' => 150000]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'simple')
            ->assertSet('basePrice', 150000);
    }

    public function test_edit_loads_variable_product_as_variable(): void
    {
        $product = $this->storeProduct(['base_price' => 100000]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'variable')
            ->assertSee('متغیرهای محصول');
    }

    public function test_switching_variable_product_to_simple_is_rejected_without_data_loss(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('pricingType', 'simple')
            ->assertHasErrors('pricingType')
            ->assertSet('pricingType', 'variable');

        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->count());
        $this->assertSame(300000, ProductColorPrice::where('product_id', $product->id)->firstOrFail()->price);
    }

    public function test_save_rejects_variable_product_whose_rows_exist_when_forced_simple(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'variable')
            ->set('pricingType', 'simple')
            ->assertHasErrors('pricingType');

        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->count());
    }

    public function test_create_after_editing_variable_product_resets_pricing_type_and_variant_state(): void
    {
        $product = $this->storeProduct(['is_active' => false]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'variable')
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('pricingType', 'simple')
            ->assertSet('showVariantForm', false)
            ->assertSet('showForm', true);
    }

    public function test_card_product_edit_keeps_simple_pricing_without_selector(): void
    {
        $bank = $this->cardProduct(CustomizationWorkflowEnum::BANK_CARD);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $bank->id)
            ->assertSet('pricingType', 'simple')
            ->assertDontSee('نوع قیمت‌گذاری');
    }

    public function test_save_edit_variable_product_via_selector_keeps_variants_and_model(): void
    {
        $product = $this->storeProduct(['base_price' => 100000, 'is_active' => false]);
        $this->variant($product, $this->color(), 300000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('pricingType', 'variable')
            ->set('name', 'ویرایش متغیر با انتخاب نوع قیمت')
            ->set('pricingType', 'variable')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ویرایش متغیر با انتخاب نوع قیمت', $product->fresh()->name);
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->count());

        // The variants and the variable pricing model are what this test guards.
        // The base price must be cleared: CartService seeds unit_price from
        // base_price whenever a request omits color_id, so a surviving base price
        // on a variable product is a second, admin-unaware price source.
        $this->assertNull($product->fresh()->base_price);
    }

    public function test_pricing_type_radios_form_one_named_group_with_live_binding(): void
    {
        $html = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->html();

        $this->assertSame(2, substr_count($html, '<input type="radio" name="pricingType" wire:model.live="pricingType"'));
        $this->assertStringContainsString('value="simple"', $html);
        $this->assertStringContainsString('value="variable"', $html);
        $this->assertStringContainsString('accent-yellow-500', $html);
    }

    public function test_simple_radio_is_authoritatively_checked_by_default(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('pricingType', 'simple');

        $checked = $this->checkedPricingRadios($component->html());

        $this->assertTrue($checked['simple']);
        $this->assertFalse($checked['variable']);
    }

    public function test_switching_radio_updates_livewire_state_and_conditional_ui_immediately(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSee('قیمت پایه (تومان)')
            ->assertDontSee('قیمت‌گذاری متغیر');

        $component->set('pricingType', 'variable')
            ->assertSet('pricingType', 'variable')
            ->assertSee('قیمت‌گذاری متغیر')
            ->assertSee('محصول را ذخیره کنید')
            ->assertSee('قیمت متغیر')
            ->assertDontSee('قیمت پایه (تومان)');

        $checked = $this->checkedPricingRadios($component->html());
        $this->assertFalse($checked['simple']);
        $this->assertTrue($checked['variable']);

        $component->set('pricingType', 'simple')
            ->assertSet('pricingType', 'simple')
            ->assertSee('قیمت پایه (تومان)')
            ->assertDontSee('قیمت‌گذاری متغیر');

        $checked = $this->checkedPricingRadios($component->html());
        $this->assertTrue($checked['simple']);
        $this->assertFalse($checked['variable']);
    }

    public function test_only_one_pricing_type_radio_is_checked_in_every_state(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create');

        foreach (['simple', 'variable', 'simple', 'variable', 'simple'] as $selection) {
            $component->set('pricingType', $selection)
                ->assertSet('pricingType', $selection);

            $checked = $this->checkedPricingRadios($component->html());
            $this->assertNotSame(
                $checked['simple'],
                $checked['variable'],
                "Exactly one pricing radio must be checked for {$selection}."
            );
        }
    }

    public function test_edit_form_checks_the_radio_matching_the_stored_pricing_model(): void
    {
        $simple = $this->storeProduct(['base_price' => 150000]);

        $html = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $simple->id)
            ->html();

        $this->assertTrue($this->checkedPricingRadios($html)['simple']);

        $variable = $this->storeProduct(['base_price' => 100000]);
        $this->variant($variable, $this->color(), 300000);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $variable->id)
            ->assertSet('pricingType', 'variable');

        $checked = $this->checkedPricingRadios($component->html());
        $this->assertFalse($checked['simple']);
        $this->assertTrue($checked['variable']);
    }

    public function test_pricing_type_cards_are_whole_labels_with_hover_and_selected_ring(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('pricingType', 'simple');

        $html = $component->html();

        // Every option is a whole clickable <label> card whose hover feedback
        // is border/background only (no transforms), so nothing shifts.
        $this->assertSame(2, substr_count($html, 'cursor-pointer transition-colors duration-150 group'));
        $this->assertStringContainsString('hover:border-yellow-400 hover:bg-yellow-50/60', $html);
        $this->assertSame(2, substr_count($html, 'group-hover:text-yellow-900'));
        $this->assertStringNotContainsString('scale-', $html);

        // Exactly the selected card carries the ring highlight at any time.
        $this->assertSame(1, substr_count($html, 'ring-1 ring-yellow-500/60'));

        $component->set('pricingType', 'variable');
        $this->assertSame(1, substr_count($component->html(), 'ring-1 ring-yellow-500/60'));
    }

    public function test_switching_moves_the_highlighted_card_to_the_selected_radio(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSet('pricingType', 'simple');

        $this->assertTrue($this->selectedCardContains($component->html(), 'value="simple"'));
        $this->assertFalse($this->selectedCardContains($component->html(), 'value="variable"'));

        $component->set('pricingType', 'variable');

        $this->assertFalse($this->selectedCardContains($component->html(), 'value="simple"'));
        $this->assertTrue($this->selectedCardContains($component->html(), 'value="variable"'));
    }

    public function test_creating_variable_product_opens_edit_mode_with_variant_section(): void
    {
        $component = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('pricingType', 'variable')
            ->set('name', 'کالای متغیر با جریان ایجاد')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();

        // The core of the new flow: instead of dropping back to the list, the
        // admin lands in edit mode of the just-created product with the
        // variant-pricing section ready — and the pricing model stays variable
        // even though no color rows exist yet.
        $component
            ->assertSet('editingId', $product->id)
            ->assertSet('pricingType', 'variable')
            ->assertSet('showForm', true)
            ->assertSee('متغیرهای محصول')
            ->assertSee('+ افزودن رنگ و قیمت')
            ->assertSee('ذخیره تغییرات')
            ->assertSee('این محصول هنوز قابل فعال‌سازی نیست؛ حداقل یک رنگ فعال با قیمت معتبر اضافه کنید');

        $this->assertNull($product->base_price);
        $this->assertSame(0, ProductColorPrice::where('product_id', $product->id)->count());
    }

    public function test_submit_button_label_is_context_aware(): void
    {
        // Creating a simple product: plain create label.
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->assertSee('ایجاد محصول')
            ->assertDontSee('ایجاد محصول و تنظیم قیمت‌ها')
            ->assertDontSee('ذخیره تغییرات');

        // Choosing the variable model promises the variant-price step.
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('pricingType', 'variable')
            ->assertSee('ایجاد محصول و تنظیم قیمت‌ها')
            ->assertDontSee('ذخیره تغییرات');

        // Editing an existing product: plain save wording.
        $product = $this->storeProduct(['base_price' => 150000]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSee('ذخیره تغییرات')
            ->assertDontSee('ایجاد محصول و تنظیم قیمت‌ها');
    }

    public function test_activation_hint_tracks_active_variant_presence(): void
    {
        $hint = 'حداقل یک رنگ فعال با قیمت معتبر اضافه کنید';

        $withoutActive = $this->storeProduct(['is_active' => true, 'base_price' => null]);
        $this->variant($withoutActive, $this->color(), 250000, false);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $withoutActive->id)
            ->assertSet('pricingType', 'variable')
            ->assertSee($hint);

        $withActive = $this->storeProduct(['is_active' => false]);
        $this->variant($withActive, $this->color(), 200000);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $withActive->id)
            ->assertSet('pricingType', 'variable')
            ->assertDontSee($hint);
    }

    private function checkedPricingRadios(string $html): array
    {
        return [
            'simple' => (bool) preg_match('/<input type="radio" name="pricingType" wire:model\.live="pricingType" value="simple"[^>]*\bchecked\b/', $html),
            'variable' => (bool) preg_match('/<input type="radio" name="pricingType" wire:model\.live="pricingType" value="variable"[^>]*\bchecked\b/', $html),
        ];
    }

    private function selectedCardContains(string $html, string $radioValue): bool
    {
        if (! preg_match_all('/<label class="flex items-start gap-3 rounded-lg border p-4 cursor-pointer transition-colors duration-150 group[^>]*>.*?<\/label>/s', $html, $labels)) {
            return false;
        }

        foreach ($labels[0] as $label) {
            if (str_contains($label, 'ring-1 ring-yellow-500/60') && str_contains($label, $radioValue)) {
                return true;
            }
        }

        return false;
    }
}
