<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FuelCardColorPricingBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createColor(string $suffix = ''): Color
    {
        return Color::create([
            'name' => 'رنگ تست'.$suffix,
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createProduct(string $slug, ProductTypeEnum $type, ?CustomizationWorkflowEnum $workflow = null, bool $isActive = true): Product
    {
        return Product::create([
            'type' => $type->value,
            'customization_workflow' => $workflow?->value,
            'name' => 'محصول '.$slug,
            'slug' => $slug,
            'base_price' => 100000,
            'is_active' => $isActive,
        ]);
    }

    private function createFuelProduct(string $slug = 'fuel-boundary'): Product
    {
        return $this->createProduct($slug, ProductTypeEnum::FUEL, CustomizationWorkflowEnum::FUEL_CARD, false);
    }

    private function createBankProduct(string $slug = 'bank-boundary'): Product
    {
        return $this->createProduct($slug, ProductTypeEnum::BANK, CustomizationWorkflowEnum::BANK_CARD);
    }

    private function createPrice(Product $product, Color $color, int $price, bool $isActive): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => $isActive,
        ]);
    }

    private function fillPriceForm(Product $product, Color $color, int $price, bool $isActive, ?int $editingId = null)
    {
        return Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('editingId', $editingId)
            ->set('productId', $product->id)
            ->set('colorId', $color->id)
            ->set('price', $price)
            ->set('isActive', $isActive);
    }

    private function typeForWorkflow(string $workflow): string
    {
        return match ($workflow) {
            CustomizationWorkflowEnum::BANK_CARD->value => ProductTypeEnum::BANK->value,
            CustomizationWorkflowEnum::FUEL_CARD->value => ProductTypeEnum::FUEL->value,
            default => ProductTypeEnum::STANDARD->value,
        };
    }

    private function fillProductForm(Product $product, string $workflow, bool $isActive)
    {
        return Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', $this->typeForWorkflow($workflow))
            ->set('customizationWorkflow', $workflow)
            ->set('name', $product->name)
            ->set('isActive', $isActive);
    }

    public function test_fuel_active_count_only_counts_active_rows_and_can_exclude_an_id(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');

        $active = $this->createPrice($product, $colorA, 800000, true);
        $inactive = $this->createPrice($product, $colorB, 900000, false);

        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
        $this->assertSame(0, ProductColorPrice::fuelActiveCount($product->id, $active->id), 'Editing the active row must not count itself.');
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id, $inactive->id), 'An inactive row must never be counted as active.');
    }

    public function test_fuel_product_accepts_its_first_active_color_price(): void
    {
        $product = $this->createFuelProduct();
        $color = $this->createColor();

        $this->fillPriceForm($product, $color, 800000, true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
        $this->assertSame(800000, ProductColorPrice::where('product_id', $product->id)->first()->price);
    }

    public function test_fuel_product_rejects_second_active_color_price(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $this->createPrice($product, $colorA, 800000, true);

        $this->fillPriceForm($product, $colorB, 900000, true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertCount(1, ProductColorPrice::where('product_id', $product->id)->get());
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id), 'A rejected second active row must not be created.');
        $this->assertDatabaseMissing('product_color_prices', ['product_id' => $product->id, 'color_id' => $colorB->id]);
    }

    public function test_fuel_product_allows_one_active_with_multiple_inactive_rows(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $colorC = $this->createColor('C');
        $this->createPrice($product, $colorA, 800000, true);
        $this->createPrice($product, $colorB, 900000, false);

        $this->fillPriceForm($product, $colorC, 950000, false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertCount(3, ProductColorPrice::where('product_id', $product->id)->get());
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_fuel_product_rejects_toggling_inactive_row_to_active(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $this->createPrice($product, $colorA, 800000, true);
        $inactive = $this->createPrice($product, $colorB, 900000, false);

        $this->fillPriceForm($product, $colorB, 900000, true, $inactive->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($inactive->fresh()->is_active, 'The inactive row must stay inactive when another active row exists.');
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_fuel_product_allows_editing_the_sole_active_row(): void
    {
        $product = $this->createFuelProduct();
        $color = $this->createColor();
        $row = $this->createPrice($product, $color, 800000, true);

        $this->fillPriceForm($product, $color, 820000, true, $row->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(820000, $row->fresh()->price);
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_deactivating_sole_active_fuel_row_is_rejected(): void
    {
        $product = $this->createFuelProduct();
        $color = $this->createColor();
        $row = $this->createPrice($product, $color, 800000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('editingId', $row->id)
            ->set('productId', $product->id)
            ->set('colorId', $color->id)
            ->set('price', 800000)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($row->fresh()->is_active, 'The sole active fuel row must survive a rejected deactivation.');
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_fuel_product_allows_deactivating_a_non_sole_active_row(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $this->createPrice($product, $colorA, 800000, true);
        $inactive = $this->createPrice($product, $colorB, 900000, false);

        $this->fillPriceForm($product, $colorB, 900000, false, $inactive->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($inactive->fresh()->is_active);
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_deleting_sole_active_fuel_row_is_rejected(): void
    {
        $product = $this->createFuelProduct();
        $color = $this->createColor();
        $row = $this->createPrice($product, $color, 800000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $row->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_color_prices', ['id' => $row->id]);
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_deleting_a_non_sole_active_fuel_row_is_permitted(): void
    {
        $product = $this->createFuelProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $this->createPrice($product, $colorA, 800000, true);
        $inactive = $this->createPrice($product, $colorB, 900000, false);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $inactive->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_color_prices', ['id' => $inactive->id]);
        $this->assertSame(1, ProductColorPrice::fuelActiveCount($product->id));
    }

    public function test_bank_active_color_row_deletion_is_unchanged(): void
    {
        $product = $this->createBankProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $rejected = $this->createPrice($product, $colorA, 700000, true);
        $this->createPrice($product, $colorB, 750000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $rejected->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_color_prices', ['id' => $rejected->id]);
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_commerce_active_color_row_deletion_is_unchanged(): void
    {
        $product = $this->createProduct('commerce-delete-boundary', ProductTypeEnum::STANDARD);
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $rejected = $this->createPrice($product, $colorA, 300000, true);
        $this->createPrice($product, $colorB, 350000, true);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('delete', $rejected->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_color_prices', ['id' => $rejected->id]);
        $this->assertSame(1, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_product_manager_rejects_switching_to_fuel_when_two_active_prices_exist(): void
    {
        $bankProduct = $this->createBankProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $this->createPrice($bankProduct, $colorA, 700000, true);
        $this->createPrice($bankProduct, $colorB, 750000, true);

        $this->fillProductForm($bankProduct, CustomizationWorkflowEnum::FUEL_CARD->value, false)
            ->call('save')
            ->assertHasErrors('customizationWorkflow');

        $this->assertSame(
            CustomizationWorkflowEnum::BANK_CARD->value,
            $bankProduct->fresh()->getRawOriginal('customization_workflow'),
            'A rejected switch must not mutate the stored workflow.'
        );
    }

    public function test_product_manager_allows_switching_to_fuel_with_exactly_one_active_price(): void
    {
        $bankProduct = $this->createBankProduct();
        $color = $this->createColor();
        $this->createPrice($bankProduct, $color, 700000, true);

        $this->fillProductForm($bankProduct, CustomizationWorkflowEnum::FUEL_CARD->value, false)
            ->call('save')
            ->assertHasNoErrors();

        $product = $bankProduct->fresh();
        $this->assertSame('fuel_card', $product->getRawOriginal('customization_workflow'));
        $this->assertFalse($product->is_active);
    }

    public function test_product_manager_rejects_fuel_activation_without_purchasable_design(): void
    {
        $product = $this->createFuelProduct();
        $color = $this->createColor();
        $this->createPrice($product, $color, 800000, true);

        $this->fillProductForm($product, CustomizationWorkflowEnum::FUEL_CARD->value, true)
            ->call('save')
            ->assertHasErrors(['customizationWorkflow' => 'کارت سوخت به حداقل یک طرح قابل خرید نیاز دارد؛ طرح باید فعال، در دسته فعال، و دارای تصویر فعال مجاز برای رنگ کارت باشد.']);

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_bank_product_keeps_multiple_active_colors_and_price_rows(): void
    {
        $product = $this->createBankProduct();
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $colorC = $this->createColor('C');
        $this->createPrice($product, $colorA, 700000, true);
        $this->createPrice($product, $colorB, 750000, true);

        $this->fillPriceForm($product, $colorC, 800000, true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_commerce_product_without_workflow_keeps_prior_color_price_behavior(): void
    {
        $product = $this->createProduct('commerce-boundary', ProductTypeEnum::STANDARD);
        $colorA = $this->createColor('A');
        $colorB = $this->createColor('B');
        $colorC = $this->createColor('C');
        $this->createPrice($product, $colorA, 300000, true);
        $this->createPrice($product, $colorB, 350000, true);

        $this->fillPriceForm($product, $colorC, 400000, true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, ProductColorPrice::where('product_id', $product->id)->where('is_active', true)->count());
    }

    public function test_registry_activates_both_card_workflows(): void
    {
        $this->assertSame(
            [CustomizationWorkflowEnum::BANK_CARD, CustomizationWorkflowEnum::FUEL_CARD],
            CustomizationWorkflowRegistry::ACTIVE_WORKFLOWS
        );
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD));
        $this->assertTrue(CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD));
        $this->assertFalse(CustomizationWorkflowRegistry::isActive(null));
    }
}
