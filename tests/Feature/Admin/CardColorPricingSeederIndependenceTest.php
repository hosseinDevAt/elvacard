<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
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

    public function test_card_options_are_available_even_with_empty_database_without_seeders(): void
    {
        // Zero products exist in the database initially
        $this->assertSame(0, Product::count());

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('create')
            ->assertSee('کارت بانکی')
            ->assertSee('کارت سوخت');

        $options = $component->get('productOptions');
        $this->assertCount(2, $options);
        $this->assertSame('کارت بانکی', $options[0]['name']);
        $this->assertSame('کارت سوخت', $options[1]['name']);

        // Both card products exist now
        $this->assertTrue(
            Product::query()->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)->exists()
        );
        $this->assertTrue(
            Product::query()->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)->exists()
        );
    }

    public function test_admin_can_save_price_for_fuel_card_independently(): void
    {
        $color = Color::create(['name' => 'مشکی', 'code_hex' => '#000000', 'is_active' => true]);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        $fuelCard = $component->get('cardProducts')['fuel_card'];

        $component
            ->call('create')
            ->set('productId', $fuelCard->id)
            ->set('colorId', $color->id)
            ->set('price', 450000)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_color_prices', [
            'product_id' => $fuelCard->id,
            'color_id' => $color->id,
            'price' => 450000,
            'is_active' => true,
        ]);
    }

    public function test_bank_and_fuel_prices_for_same_color_are_isolated_and_independent(): void
    {
        $black = Color::create(['name' => 'مشکی مات', 'code_hex' => '#111111', 'is_active' => true]);

        $component = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class);

        $bankCard = $component->get('cardProducts')['bank_card'];
        $fuelCard = $component->get('cardProducts')['fuel_card'];

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

    public function test_inactive_fuel_card_product_is_not_hidden_from_options(): void
    {
        $fuel = Product::create([
            'type' => \App\Enums\ProductTypeEnum::FUEL,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
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

    public function test_auto_created_base_products_are_not_purchasable_without_active_price(): void
    {
        $bank = ProductColorPriceManager::ensureCardProduct(CustomizationWorkflowEnum::BANK_CARD);
        $fuel = ProductColorPriceManager::ensureCardProduct(CustomizationWorkflowEnum::FUEL_CARD);

        // Neither product has any active ProductColorPrice rows yet
        $this->assertSame(0, $bank->activeColorPrices()->count());
        $this->assertSame(0, $fuel->activeColorPrices()->count());

        // Must not be purchasable under ProductPurchaseabilityService
        $this->assertFalse(\App\Services\Customization\ProductPurchaseabilityService::isPurchasable($bank));
        $this->assertFalse(\App\Services\Customization\ProductPurchaseabilityService::isPurchasable($fuel));

        // Must not appear in Product::scopePurchasable query
        $this->assertFalse(Product::query()->purchasable()->whereKey($bank->id)->exists());
        $this->assertFalse(Product::query()->purchasable()->whereKey($fuel->id)->exists());
    }

    public function test_ensure_card_product_does_not_mutate_unrelated_store_products_with_same_slug(): void
    {
        // Suppose an unrelated standard store product was created with slug 'bank-card'
        $storeProduct = Product::create([
            'type' => \App\Enums\ProductTypeEnum::STANDARD,
            'customization_workflow' => null,
            'name' => 'کیف کارت چرمی',
            'slug' => 'bank-card',
            'base_price' => 250000,
            'is_active' => true,
        ]);

        $cardProduct = ProductColorPriceManager::ensureCardProduct(CustomizationWorkflowEnum::BANK_CARD);

        // Store product must be completely untouched
        $storeProductFresh = $storeProduct->fresh();
        $this->assertSame(\App\Enums\ProductTypeEnum::STANDARD, $storeProductFresh->type);
        $this->assertNull($storeProductFresh->customization_workflow);
        $this->assertSame('کیف کارت چرمی', $storeProductFresh->name);
        $this->assertSame('bank-card', $storeProductFresh->slug);

        // Card product must be a separate record with its own unique slug
        $this->assertNotSame($storeProduct->id, $cardProduct->id);
        $this->assertSame(\App\Enums\ProductTypeEnum::BANK, $cardProduct->type);
        $this->assertSame(CustomizationWorkflowEnum::BANK_CARD, $cardProduct->customization_workflow);
        $this->assertStringStartsWith('bank-card-', $cardProduct->slug);
    }
}
