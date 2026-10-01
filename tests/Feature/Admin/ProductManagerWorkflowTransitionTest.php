<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * F6 regression suite: ProductManager::save() must evaluate purchaseability
 * against the *resulting* workflow/product state, not the old persisted row.
 *
 * Without the fix a standard product (workflow=null in DB) could transition to
 * bank_card + isActive=true in one save, bypassing all readiness checks.
 */
class ProductManagerWorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ─── helpers ───────────────────────────────────────────────────────────────

    private function standardProduct(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول استاندارد',
            'slug' => 'std-product-'.uniqid(),
            'base_price' => 500000,
            'is_active' => false,
        ]);
    }

    private function bankCardProduct(bool $active = false): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی',
            'slug' => 'bank-card-'.uniqid(),
            'base_price' => 500000,
            'is_active' => $active,
        ]);
    }

    private function color(): Color
    {
        return Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function fullySetupBankCard(Color $color): Product
    {
        $product = $this->bankCardProduct(false);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 600000,
            'is_active' => true,
        ]);

        $category = CateDesign::create([
            'name' => 'دسته طلایی',
            'slug' => 'gold-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح آزمون',
            'slug' => 'test-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/test-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return $product;
    }

    // ─── F6 Tests ──────────────────────────────────────────────────────────────

    /**
     * Core F6 regression: standard → bank_card + isActive=true + NO color/design
     * must be BLOCKED. Previously the stale null workflow caused activationBlockers
     * to return [] and allow the save.
     */
    public function test_standard_to_bank_card_activation_blocked_when_no_color_or_design(): void
    {
        $product = $this->standardProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors(['customizationWorkflow']);

        // Product must remain inactive in the database.
        $this->assertFalse($product->fresh()->is_active);
    }

    /**
     * standard → bank_card + isActive=true + FULL setup (color + design) must be ALLOWED.
     */
    public function test_standard_to_bank_card_activation_allowed_when_fully_setup(): void
    {
        $color = $this->color();
        // Start as bank_card but inactive to set up color/design, then test transition.
        // We simulate the real scenario: product is currently stored as standard but
        // has already had its color/design set up (e.g. admin set them while product
        // was still being configured), and now saves with workflow=bank_card + active=true.
        $product = $this->fullySetupBankCard($color);
        // Revert stored workflow to null to reproduce the bug scenario.
        $product->update(['customization_workflow' => null, 'type' => ProductTypeEnum::STANDARD->value, 'is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('type', ProductTypeEnum::BANK->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors(['customizationWorkflow']);

        $this->assertTrue($product->fresh()->is_active);
        $this->assertSame(
            CustomizationWorkflowEnum::BANK_CARD->value,
            $product->fresh()->customization_workflow->value
        );
    }

    /**
     * An already-valid bank_card product that is re-saved while active must not
     * be affected by the new targetWorkflow param.
     */
    public function test_existing_valid_bank_card_product_save_remains_valid(): void
    {
        $color = $this->color();
        $product = $this->fullySetupBankCard($color);
        $product->update(['is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('name', 'کارت بانکی ویرایش‌شده')
            ->call('save')
            ->assertHasNoErrors(['customizationWorkflow']);

        $this->assertTrue($product->fresh()->is_active);
    }

    /**
     * standard → fuel_card + isActive=true must still be blocked by existing
     * Fuel rules (user must save inactive first, then define a single color+price).
     */
    public function test_standard_to_fuel_card_activation_still_blocked_by_fuel_rules(): void
    {
        $product = $this->standardProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('type', ProductTypeEnum::FUEL->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->set('isActive', true)
            ->call('save')
            ->assertHasErrors(['customizationWorkflow']);

        $this->assertFalse($product->fresh()->is_active);
    }

    /**
     * Normal standard product with simple pricing can be saved active without
     * hitting any customization readiness check.
     */
    public function test_normal_store_product_save_unchanged(): void
    {
        $product = $this->standardProduct();
        $product->update(['base_price' => 100000]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('isActive', true)
            ->set('pricingType', 'simple')
            ->call('save')
            ->assertHasNoErrors(['customizationWorkflow']);

        $this->assertTrue($product->fresh()->is_active);
    }
}
