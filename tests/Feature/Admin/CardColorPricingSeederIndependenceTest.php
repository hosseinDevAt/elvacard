<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CardColorPricingSeederIndependenceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    private function bankCardProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی',
            'slug' => 'bank-card-'.uniqid(),
            'base_price' => 850000,
            'is_active' => true,
        ], $attributes));
    }

    private function fuelCardProduct(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت',
            'slug' => 'fuel-card-'.uniqid(),
            'base_price' => 450000,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * 1. Rendering the pricing page with an empty products table does not create products.
     */
    public function test_1_rendering_pricing_page_with_empty_products_table_does_not_create_products(): void
    {
        $this->assertSame(0, Product::count());

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertStatus(200);

        $this->assertSame(0, Product::count());
    }

    /**
     * 2. Rendering the page does not create or modify pricing records.
     */
    public function test_2_rendering_page_does_not_create_or_modify_pricing_records(): void
    {
        $this->assertSame(0, ProductColorPrice::count());

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertStatus(200);

        $this->assertSame(0, ProductColorPrice::count());
    }

    /**
     * 3. Repeated page renders and computed-property access do not create database records.
     */
    public function test_3_repeated_page_renders_and_computed_property_access_do_not_create_database_records(): void
    {
        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductColorPrice::count());

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        // Access computed properties multiple times
        $cards = $component->get('cardProducts');
        $this->assertNull($cards['bank_card']);
        $this->assertNull($cards['fuel_card']);

        $options = $component->get('productOptions');
        $this->assertSame([], $options);

        $colors = $component->get('colorOptions');
        $this->assertIsArray($colors);

        // Re-render multiple times
        $component->call('$refresh');
        $component->call('$refresh');

        $this->assertSame(0, Product::count());
        $this->assertSame(0, ProductColorPrice::count());
    }

    /**
     * 4. Missing bank-card and fuel-card products are handled with a clear empty state.
     */
    public function test_4_missing_bank_card_and_fuel_card_products_are_handled_with_clear_empty_state(): void
    {
        // When no card products exist: informative banner with link to admin.products
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertSee('محصول کارت بانکی یا کارت سوخت یافت نشد')
            ->assertSee('مدیریت محصولات')
            ->assertSee(route('admin.products'));

        // Calling create() with no products flashes an error and does not open form
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->assertSet('showForm', false)
            ->assertSee('ابتدا باید محصول کارت بانکی یا کارت سوخت را از بخش مدیریت محصولات ایجاد کنید.');

        // When only Bank Card exists: it informs that Fuel Card is missing
        $this->bankCardProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertDontSee('محصول کارت بانکی یا کارت سوخت یافت نشد')
            ->assertSee('کارت سوخت')
            ->assertSee('هنوز ایجاد نشده است');
    }

    /**
     * 5. Existing eligible products appear in the interface.
     */
    public function test_5_existing_eligible_products_appear_in_interface(): void
    {
        $bank = $this->bankCardProduct(['name' => 'کارت بانکی هوشمند']);
        $fuel = $this->fuelCardProduct(['name' => 'کارت سوخت دیجیتال']);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->assertDontSee('محصول کارت بانکی یا کارت سوخت یافت نشد');

        $options = $component->get('productOptions');
        $this->assertCount(2, $options);
        $this->assertSame($bank->id, $options[0]['id']);
        $this->assertSame('کارت بانکی هوشمند', $options[0]['name']);
        $this->assertSame($fuel->id, $options[1]['id']);
        $this->assertSame('کارت سوخت دیجیتال', $options[1]['name']);

        // When opening create form, both appear as radio options
        $component->call('create')
            ->assertSet('showForm', true)
            ->assertSee('کارت بانکی هوشمند')
            ->assertSee('کارت سوخت دیجیتال');
    }

    /**
     * 6. Ineligible product types and workflows are excluded.
     */
    public function test_6_ineligible_product_types_and_workflows_are_excluded(): void
    {
        // Standard commerce product (workflow = null)
        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف چرمی کارت',
            'slug' => 'leather-card-holder',
            'base_price' => 300000,
            'is_active' => true,
        ]);

        // Mismatched product (standard type with bank_card workflow)
        Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'محصول نامعتبر نوع استاندارد با گردش کار کارت',
            'slug' => 'invalid-card',
            'base_price' => 300000,
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        $options = $component->get('productOptions');
        $this->assertEmpty($options);

        $this->assertNull(ProductColorPriceManager::findCardProduct(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertNull(ProductColorPriceManager::findCardProduct(CustomizationWorkflowEnum::FUEL_CARD));
    }

    /**
     * 7. Explicit authorized admin action can create or update a color price for an existing eligible product.
     */
    public function test_7_explicit_authorized_admin_action_can_create_or_update_color_price_for_existing_product(): void
    {
        $bank = $this->bankCardProduct();
        $fuel = $this->fuelCardProduct();
        $color = Color::create(['name' => 'مشکی براق', 'code_hex' => '#000000', 'is_active' => true]);

        // Admin explicitly creates a price row for bank card
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('productId', $bank->id)
            ->set('colorId', $color->id)
            ->set('price', 850000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $bank->id,
            'color_id' => $color->id,
            'price' => 850000,
            'is_active' => true,
        ]);

        $priceRow = ProductColorPrice::query()
            ->where('product_id', $bank->id)
            ->where('color_id', $color->id)
            ->firstOrFail();

        // Admin explicitly updates the price
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $bank->id)
            ->call('edit', $priceRow->id)
            ->set('price', 990000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(990000, $priceRow->fresh()->price);

        // Also test fuel card price independence
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('productId', $fuel->id)
            ->set('colorId', $color->id)
            ->set('price', 450000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $fuel->id,
            'color_id' => $color->id,
            'price' => 450000,
        ]);

        // Bank card price remains 990000
        $this->assertSame(990000, $priceRow->fresh()->price);
    }

    /**
     * 8. Unauthorized users cannot modify prices.
     */
    public function test_8_unauthorized_users_cannot_modify_prices(): void
    {
        // 1. Guest user is forbidden
        Livewire::test(ProductColorPriceManager::class)
            ->assertStatus(403);

        // 2. Regular customer is forbidden
        Livewire::actingAs($this->customer())
            ->test(ProductColorPriceManager::class)
            ->assertStatus(403);

        // No pricing records were created
        $this->assertSame(0, ProductColorPrice::count());
    }

    /**
     * 9. Existing price records remain unchanged unless the admin explicitly updates them.
     */
    public function test_9_existing_price_records_remain_unchanged_unless_admin_explicitly_updates_them(): void
    {
        $bank = $this->bankCardProduct();
        $color = Color::create(['name' => 'طلایی', 'code_hex' => '#ffd700', 'is_active' => true]);

        $existingPrice = ProductColorPrice::create([
            'product_id' => $bank->id,
            'color_id' => $color->id,
            'price' => 777000,
            'is_active' => true,
        ]);

        // Rendering, refreshing, opening create form must never change the existing price
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->call('$refresh');

        $this->assertSame(777000, $existingPrice->fresh()->price);
        $this->assertSame(1, ProductColorPrice::count());
    }

    /**
     * 10. The implementation works without relying on seeders.
     */
    public function test_10_pricing_implementation_works_without_relying_on_seeders(): void
    {
        // No seeders run. Database contains 0 records initially.
        $this->assertSame(0, Product::count());
        $this->assertSame(0, Color::count());
        $this->assertSame(0, ProductColorPrice::count());

        // Admin creates products and colors through regular application models
        $bank = $this->bankCardProduct(['name' => 'کارت بانکی جدید']);
        $color = Color::create(['name' => 'نقره‌ای', 'code_hex' => '#c0c0c0', 'is_active' => true]);

        // Pricing component seamlessly manages prices without seeders
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('productId', $bank->id)
            ->set('colorId', $color->id)
            ->set('price', 650000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $bank->id,
            'color_id' => $color->id,
            'price' => 650000,
            'is_active' => true,
        ]);
    }

    public function test_inactive_fuel_card_product_is_not_hidden_from_options(): void
    {
        $bank = $this->bankCardProduct();
        $fuel = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت غیرفعال',
            'slug' => 'fuel-card-custom',
            'base_price' => 450000,
            'is_active' => false, // Inactive in product manager
        ]);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        $options = $component->get('productOptions');

        $this->assertCount(2, $options);
        $fuelOption = collect($options)->firstWhere('workflow', 'fuel_card');
        $this->assertNotNull($fuelOption);
        $this->assertSame($fuel->id, $fuelOption['id']);
    }

    public function test_bank_and_fuel_prices_for_same_color_are_isolated_and_independent(): void
    {
        $black = Color::create(['name' => 'مشکی مات', 'code_hex' => '#111111', 'is_active' => true]);

        $bankCard = $this->bankCardProduct();
        $fuelCard = $this->fuelCardProduct();

        // 1. Save Bank Card Black price = 850,000
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('productId', $bankCard->id)
            ->set('colorId', $black->id)
            ->set('price', 850000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        // 2. Save Fuel Card Black price = 450,000
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->set('productId', $fuelCard->id)
            ->set('colorId', $black->id)
            ->set('price', 450000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        // 3. Confirm both records exist in DB independently
        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $bankCard->id,
            'color_id' => $black->id,
            'price' => 850000,
        ]);

        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $fuelCard->id,
            'color_id' => $black->id,
            'price' => 450000,
        ]);

        // 4. Update Bank Card price to 950,000 and ensure Fuel Card remains 450,000
        $bankPriceRow = ProductColorPrice::where('product_id', $bankCard->id)->where('color_id', $black->id)->first();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $bankCard->id)
            ->call('edit', $bankPriceRow->id)
            ->set('price', 950000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(950000, $bankPriceRow->fresh()->price);
        $this->assertSame(450000, ProductColorPrice::where('product_id', $fuelCard->id)->where('color_id', $black->id)->first()->price);
    }
}
