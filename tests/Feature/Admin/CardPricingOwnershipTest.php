<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * N-Onyx-44 / B-1: a Card price row and its gallery images belong to exactly
 * one Bank/Fuel product.
 *
 * ProductManager already enforces this for Store variants (see
 * ProductVariantOwnershipTest). The Card pricing surface is the mirror image
 * of that flow and originally had no ownership scoping at all: every action
 * only asked "is this row's product a card product?", never "does this row
 * belong to the product currently open in the form?". A crafted Livewire call
 * could therefore re-parent another card's price row, repoint its gallery, or
 * delete it.
 *
 * Each case asserts persisted state, because that is the property that
 * actually matters: a rejected action must leave the other product untouched.
 */
class CardPricingOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function color(string $name): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function cardProduct(string $name, string $workflow = CustomizationWorkflowEnum::BANK_CARD->value): Product
    {
        return Product::create([
            'type' => $workflow === CustomizationWorkflowEnum::FUEL_CARD->value
                ? ProductTypeEnum::FUEL->value
                : ProductTypeEnum::BANK->value,
            'customization_workflow' => $workflow,
            'name' => $name,
            'slug' => 'card-ownership-'.uniqid(),
            'is_active' => false,
        ]);
    }

    /**
     * Card product A is the one open in the form; card product B owns the price
     * row and the gallery image every test below tries to reach from A.
     *
     * @return array{0: Product, 1: Product, 2: ProductColorPrice, 3: ProductImage}
     */
    private function twoCardsWithAssetsOnTheSecond(): array
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/owned-by-b.png', 'image of card b');

        $cardA = $this->cardProduct('کارت الف');
        $cardB = $this->cardProduct('کارت ب');
        $colorB = $this->color('آبی');

        $priceB = ProductColorPrice::create([
            'product_id' => $cardB->id,
            'color_id' => $colorB->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $imageB = ProductImage::create([
            'product_id' => $cardB->id,
            'color_id' => $colorB->id,
            'image_path' => 'products/owned-by-b.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        return [$cardA, $cardB, $priceB, $imageB];
    }

    // ── Scenario 1: editing another card's row ───────────────────────────

    public function test_edit_price_of_another_card_is_rejected(): void
    {
        [$cardA, $cardB, $priceB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->call('edit', $priceB->id)
            ->assertSet('editingId', null)
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $cardB->id,
        ]);
    }

    // ── Scenario 2: deleting another card's row ─────────────────────────

    public function test_delete_price_of_another_card_is_rejected(): void
    {
        [$cardA, $cardB, $priceB, $imageB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->call('delete', $priceB->id);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $cardB->id,
        ]);
        $this->assertDatabaseHas('product_images', ['id' => $imageB->id]);
    }

    // ── Scenario 3: changing another card's price ────────────────────────

    public function test_save_cannot_reparent_or_reprice_another_cards_row(): void
    {
        [$cardA, $cardB, $priceB] = $this->twoCardsWithAssetsOnTheSecond();
        $colorA = $this->color('طلایی');

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            // a crafted call aims the form at card B's row while card A is open
            ->set('editingId', $priceB->id)
            ->set('colorId', $colorA->id)
            ->set('price', 999000)
            ->set('isActive', true)
            ->call('save');

        // the row still belongs to card B, with its original colour and price
        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $cardB->id,
            'color_id' => $priceB->color_id,
            'price' => 500000,
        ]);

        // and nothing was created for card A
        $this->assertDatabaseMissing('product_color_prices', [
            'product_id' => $cardA->id,
        ]);
    }

    public function test_save_cannot_reprice_another_cards_row_via_crafted_editing_id(): void
    {
        [$cardA, , $priceB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->set('editingId', $priceB->id)
            ->set('colorId', $priceB->color_id)
            ->set('price', 1)
            ->set('isActive', true)
            ->call('save');

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $priceB->product_id,
            'price' => 500000,
        ]);
    }

    // ── Scenario 4: another card's gallery ───────────────────────────────

    public function test_set_primary_image_of_another_card_is_rejected(): void
    {
        [$cardA, , , $imageB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->call('setPrimaryImage', $imageB->id);

        $this->assertFalse((bool) $imageB->fresh()->is_primary);
    }

    public function test_delete_image_of_another_card_is_rejected(): void
    {
        [$cardA, $cardB, , $imageB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->call('deleteImage', $imageB->id);

        $this->assertDatabaseHas('product_images', [
            'id' => $imageB->id,
            'product_id' => $cardB->id,
        ]);
        Storage::disk('public')->assertExists('products/owned-by-b.png');
    }

    // ── Scenario 5: mutation without a valid current product ─────────────

    public function test_price_actions_are_rejected_while_no_card_is_open(): void
    {
        [$cardA, , $priceB, $imageB] = $this->twoCardsWithAssetsOnTheSecond();

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            // the form is closed: the selected product is cleared
            ->set('productId', null)
            ->set('editingId', $priceB->id)
            ->set('colorId', $priceB->color_id)
            ->set('price', 111000)
            ->call('save')
            ->call('edit', $priceB->id)
            ->call('delete', $priceB->id)
            ->call('setPrimaryImage', $imageB->id)
            ->call('deleteImage', $imageB->id);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $priceB->product_id,
            'price' => 500000,
        ]);
        $this->assertDatabaseHas('product_images', ['id' => $imageB->id]);
        Storage::disk('public')->assertExists('products/owned-by-b.png');
    }

    public function test_price_actions_on_a_store_product_are_rejected(): void
    {
        [, , $priceB] = $this->twoCardsWithAssetsOnTheSecond();

        $storeProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف فروشگاهی',
            'slug' => 'store-not-a-card-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ]);

        // A Store product can never become a valid target for card pricing.
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $storeProduct->id)
            ->set('editingId', $priceB->id)
            ->set('colorId', $priceB->color_id)
            ->set('price', 1)
            ->call('save')
            ->call('edit', $priceB->id)
            ->call('delete', $priceB->id);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $priceB->product_id,
            'price' => 500000,
        ]);
    }

    public function test_crafted_product_id_cannot_target_a_store_products_variant(): void
    {
        [, , $priceB] = $this->twoCardsWithAssetsOnTheSecond();

        $storeProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف فروشگاهی',
            'slug' => 'store-variant-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ]);

        $storeVariant = ProductColorPrice::create([
            'product_id' => $storeProduct->id,
            'color_id' => $this->color('قرمز')->id,
            'price' => 300000,
            'is_active' => true,
        ]);

        // The card surface must never reach a Store product's variant row.
        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $priceB->product_id)
            ->call('edit', $storeVariant->id)
            ->call('delete', $storeVariant->id);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $storeVariant->id,
            'product_id' => $storeProduct->id,
            'price' => 300000,
        ]);
    }

    // ── The open card's own rows stay fully manageable ───────────────────

    public function test_the_open_cards_own_price_row_is_still_editable(): void
    {
        Storage::fake('public');

        $card = $this->cardProduct('کارت الف');
        $color = $this->color('آبی');
        $price = ProductColorPrice::create([
            'product_id' => $card->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $card->id)
            ->call('edit', $price->id)
            ->assertSet('editingId', $price->id)
            ->assertSet('showForm', true)
            ->set('price', 750000)
            ->call('save');

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $price->id,
            'product_id' => $card->id,
            'price' => 750000,
        ]);
    }

    public function test_the_open_cards_own_price_row_can_still_be_deleted(): void
    {
        Storage::fake('public');

        $card = $this->cardProduct('کارت الف');
        $color = $this->color('آبی');
        $price = ProductColorPrice::create([
            'product_id' => $card->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $card->id)
            ->call('delete', $price->id);

        $this->assertDatabaseMissing('product_color_prices', ['id' => $price->id]);
    }

    public function test_the_open_cards_own_images_remain_manageable(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/own-a.png', 'card a image');

        $card = $this->cardProduct('کارت الف');
        $color = $this->color('آبی');

        $image = ProductImage::create([
            'product_id' => $card->id,
            'color_id' => $color->id,
            'image_path' => 'products/own-a.png',
            'sort_order' => 1,
            'is_primary' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $card->id)
            ->call('setPrimaryImage', $image->id);

        $this->assertTrue((bool) $image->fresh()->is_primary);
    }

    public function test_browse_mode_lists_card_rows_but_refuses_to_mutate_them(): void
    {
        // The cross-product list stays browsable, yet a row is only reachable
        // for mutation once its own card has been selected.
        [$cardA, $cardB, $priceB, $imageB] = $this->twoCardsWithAssetsOnTheSecond();

        $manager = Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->set('productId', $cardA->id)
            ->set('productId', null)
            ->assertSee($cardB->name)
            ->call('edit', $priceB->id)
            ->call('delete', $priceB->id)
            ->call('setPrimaryImage', $imageB->id)
            ->call('deleteImage', $imageB->id)
            ->assertSet('editingId', null)
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'product_id' => $cardB->id,
            'price' => 500000,
        ]);
        $this->assertDatabaseHas('product_images', ['id' => $imageB->id]);

        // Choosing the row's own card is what establishes the context; after
        // that the very same actions are allowed.
        $manager->call('selectProduct', $cardB->id)
            ->assertSet('productId', $cardB->id)
            ->call('edit', $priceB->id)
            ->assertSet('editingId', $priceB->id)
            ->assertSet('productId', $cardB->id);
    }

    public function test_selecting_a_store_product_is_rejected(): void
    {
        [, , $priceB] = $this->twoCardsWithAssetsOnTheSecond();

        $storeProduct = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کیف فروشگاهی',
            'slug' => 'store-select-'.uniqid(),
            'base_price' => 200000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('selectProduct', $storeProduct->id)
            ->assertSet('productId', null)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('product_color_prices', [
            'id' => $priceB->id,
            'price' => 500000,
        ]);
    }
}
