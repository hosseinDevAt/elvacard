<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignWorkspaceVisibilityEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\CateDesignManager;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DesignCategoryWorkspaceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Color $color;

    private Product $bankProduct;

    private Product $fuelProduct;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->color = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'color_code' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->bankProduct = Product::create([
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-card-' . uniqid(),
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->bankProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'color_id' => $this->color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $this->fuelProduct = Product::create([
            'name' => 'کارت سوخت فلزی',
            'slug' => 'fuel-card-' . uniqid(),
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'base_price' => 600000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $this->fuelProduct->id,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'color_id' => $this->color->id,
            'price' => 600000,
            'is_active' => true,
        ]);
    }

    private function createDesignWithImage(CateDesign $category, string $slug, string $visibility = 'both', bool $isActive = true): Design
    {
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح ' . $slug,
            'slug' => $slug,
            'workspace_visibility' => $visibility,
            'is_active' => $isActive,
            'sort_order' => 1,
        ]);

        if ($isActive) {
            $image = DesignImage::create([
                'design_id' => $design->id,
                'color_id' => $this->color->id,
                'image_path' => 'designs/' . $slug . '.png',
                'is_active' => true,
                'sort_order' => 1,
            ]);

            DesignColorCompatibility::create([
                'design_image_id' => $image->id,
                'card_color_id' => $this->color->id,
                'is_allowed' => true,
            ]);
        }

        return $design;
    }

    /**
     * 1. Bank-only category appears in Bank workspace.
     */
    public function test_bank_only_category_appears_in_bank_workspace(): void
    {
        $bankCat = CateDesign::create([
            'name' => 'دسته مخصوص بانک',
            'slug' => 'bank-only-cat',
            'workspace_visibility' => 'bank',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($bankCat, 'bank-design-1', 'bank');

        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        $categoryIds = array_column($component->get('categories'), 'id');
        $this->assertContains($bankCat->id, $categoryIds);
        $component->assertSeeHtml($bankCat->name);
    }

    /**
     * 2. Bank-only category is hidden in Fuel workspace.
     */
    public function test_bank_only_category_is_hidden_in_fuel_workspace(): void
    {
        $bankCat = CateDesign::create([
            'name' => 'دسته مخصوص بانک',
            'slug' => 'bank-only-cat-2',
            'workspace_visibility' => 'bank',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($bankCat, 'bank-design-2', 'both');

        // Also create a fuel category so Fuel workspace has at least one valid category
        $fuelCat = CateDesign::create([
            'name' => 'دسته سوخت',
            'slug' => 'fuel-cat-for-bank-test',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($fuelCat, 'fuel-design-for-bank-test', 'fuel');

        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);

        $categoryIds = array_column($component->get('categories'), 'id');
        $this->assertNotContains($bankCat->id, $categoryIds);
        $component->assertDontSeeHtml($bankCat->name);
    }

    /**
     * 3. Fuel-only category appears in Fuel workspace.
     */
    public function test_fuel_only_category_appears_in_fuel_workspace(): void
    {
        $fuelCat = CateDesign::create([
            'name' => 'دسته مخصوص سوخت',
            'slug' => 'fuel-only-cat',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($fuelCat, 'fuel-design-1', 'fuel');

        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);

        $categoryIds = array_column($component->get('categories'), 'id');
        $this->assertContains($fuelCat->id, $categoryIds);
        $component->assertSeeHtml($fuelCat->name);
    }

    /**
     * 4. Fuel-only category is hidden in Bank workspace.
     */
    public function test_fuel_only_category_is_hidden_in_bank_workspace(): void
    {
        $fuelCat = CateDesign::create([
            'name' => 'دسته مخصوص سوخت',
            'slug' => 'fuel-only-cat-2',
            'workspace_visibility' => 'fuel',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($fuelCat, 'fuel-design-2', 'both');

        // Valid bank category so Bank workspace mounts cleanly
        $bankCat = CateDesign::create([
            'name' => 'دسته بانک فعال',
            'slug' => 'bank-cat-for-fuel-test',
            'workspace_visibility' => 'bank',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($bankCat, 'bank-design-for-fuel-test', 'bank');

        $component = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        $categoryIds = array_column($component->get('categories'), 'id');
        $this->assertNotContains($fuelCat->id, $categoryIds);
        $component->assertDontSeeHtml($fuelCat->name);
    }

    /**
     * 5. Both category appears in both workspaces.
     */
    public function test_both_category_appears_in_both_workspaces(): void
    {
        $bothCat = CateDesign::create([
            'name' => 'دسته مشترک هر دو',
            'slug' => 'both-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($bothCat, 'both-design', 'both');

        $bankComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);
        $this->assertContains($bothCat->id, array_column($bankComponent->get('categories'), 'id'));

        $fuelComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);
        $this->assertContains($bothCat->id, array_column($fuelComponent->get('categories'), 'id'));
    }

    /**
     * 6. Null/legacy category visibility behaves as both.
     */
    public function test_null_legacy_category_visibility_behaves_as_both(): void
    {
        $legacyCat = CateDesign::create([
            'name' => 'دسته قدیمی بدون تعیین محل',
            'slug' => 'legacy-cat',
            'workspace_visibility' => null,
            'is_active' => true,
        ]);
        $this->createDesignWithImage($legacyCat, 'legacy-design', 'both');

        $bankComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);
        $this->assertContains($legacyCat->id, array_column($bankComponent->get('categories'), 'id'));

        $fuelComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->fuelProduct->id,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);
        $this->assertContains($legacyCat->id, array_column($fuelComponent->get('categories'), 'id'));
    }

    /**
     * 7. Categories without eligible designs are hidden.
     */
    public function test_categories_without_eligible_designs_are_hidden(): void
    {
        // Category A: 0 designs
        $emptyCat = CateDesign::create([
            'name' => 'دسته خالی',
            'slug' => 'empty-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        // Category B: Designs exist but are inactive
        $inactiveDesignsCat = CateDesign::create([
            'name' => 'دسته با طرح غیرفعال',
            'slug' => 'inactive-designs-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($inactiveDesignsCat, 'inactive-design', 'both', false);

        // Category C: Both category, but designs are fuel-only
        $fuelOnlyDesignsCat = CateDesign::create([
            'name' => 'دسته با طرح‌های فقط سوخت',
            'slug' => 'fuel-only-designs-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($fuelOnlyDesignsCat, 'fuel-exclusive-design', 'fuel');

        // Valid category so workspace can render
        $validCat = CateDesign::create([
            'name' => 'دسته معتبر بانکی',
            'slug' => 'valid-bank-cat',
            'workspace_visibility' => 'bank',
            'is_active' => true,
        ]);
        $this->createDesignWithImage($validCat, 'valid-bank-design', 'bank');

        $bankComponent = Livewire::test(ProductCustomizer::class, [
            'productId' => $this->bankProduct->id,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
        ]);

        $categoryIds = array_column($bankComponent->get('categories'), 'id');
        $this->assertContains($validCat->id, $categoryIds);
        $this->assertNotContains($emptyCat->id, $categoryIds, 'Empty category must be hidden');
        $this->assertNotContains($inactiveDesignsCat->id, $categoryIds, 'Category with only inactive designs must be hidden');
        $this->assertNotContains($fuelOnlyDesignsCat->id, $categoryIds, 'Category with no bank-eligible designs must be hidden in bank workspace');
    }

    /**
     * 8. Design-level workspace visibility still works independently within a category.
     */
    public function test_design_level_workspace_visibility_works_independently(): void
    {
        $sharedCat = CateDesign::create([
            'name' => 'دسته عمومی',
            'slug' => 'general-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        $bankDesign = $this->createDesignWithImage($sharedCat, 'bank-sub-design', 'bank');
        $fuelDesign = $this->createDesignWithImage($sharedCat, 'fuel-sub-design', 'fuel');
        $bothDesign = $this->createDesignWithImage($sharedCat, 'both-sub-design', 'both');

        // In Bank workspace:
        $bankCatalog = app(DesignCatalogService::class)->paginate(
            $sharedCat->id,
            $this->color->id,
            null,
            CustomizationWorkflowEnum::BANK_CARD
        );
        $bankDesignIds = collect($bankCatalog->items())->pluck('id')->all();
        $this->assertContains($bankDesign->id, $bankDesignIds);
        $this->assertContains($bothDesign->id, $bankDesignIds);
        $this->assertNotContains($fuelDesign->id, $bankDesignIds, 'Fuel-only design must be excluded from bank workspace even in both category');

        // In Fuel workspace:
        $fuelCatalog = app(DesignCatalogService::class)->paginate(
            $sharedCat->id,
            $this->color->id,
            null,
            CustomizationWorkflowEnum::FUEL_CARD
        );
        $fuelDesignIds = collect($fuelCatalog->items())->pluck('id')->all();
        $this->assertContains($fuelDesign->id, $fuelDesignIds);
        $this->assertContains($bothDesign->id, $fuelDesignIds);
        $this->assertNotContains($bankDesign->id, $fuelDesignIds, 'Bank-only design must be excluded from fuel workspace even in both category');
    }

    /**
     * 9. Admin can create a category with a selected visibility.
     */
    public function test_admin_can_create_category_with_selected_visibility(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CateDesignManager::class)
            ->call('create')
            ->assertSet('workspaceVisibility', 'both')
            ->set('name', 'دسته‌بندی جدید سوخت')
            ->set('workspaceVisibility', 'fuel')
            ->call('save')
            ->assertHasNoErrors();

        $saved = CateDesign::where('name', 'دسته‌بندی جدید سوخت')->first();
        $this->assertNotNull($saved);
        $this->assertEquals(DesignWorkspaceVisibilityEnum::FUEL, $saved->workspace_visibility);
    }

    /**
     * 10. Admin can edit an existing category's visibility.
     */
    public function test_admin_can_edit_existing_category_visibility(): void
    {
        $this->actingAs($this->admin);

        $cat = CateDesign::create([
            'name' => 'دسته‌بندی جهت ویرایش',
            'slug' => 'editable-cat',
            'workspace_visibility' => 'both',
            'is_active' => true,
        ]);

        Livewire::test(CateDesignManager::class)
            ->call('edit', $cat->id)
            ->assertSet('workspaceVisibility', 'both')
            ->set('workspaceVisibility', 'bank')
            ->call('save')
            ->assertHasNoErrors();

        $cat->refresh();
        $this->assertEquals(DesignWorkspaceVisibilityEnum::BANK, $cat->workspace_visibility);
    }
}
