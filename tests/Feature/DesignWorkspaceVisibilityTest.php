<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignWorkspaceVisibilityEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\DesignCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class DesignWorkspaceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CateDesign $category;
    private Color $color;
    private Product $bankProduct;
    private Product $fuelProduct;
    private Design $bankOnlyDesign;
    private Design $fuelOnlyDesign;
    private Design $bothDesign;
    private Design $inactiveDesign;
    private DesignImage $bankOnlyImage;
    private DesignImage $fuelOnlyImage;
    private DesignImage $bothImage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->category = CateDesign::create([
            'name' => 'دسته‌بندی تست',
            'slug' => 'test-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#000000',
            'color_code' => '#000000',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->bankProduct = Product::create([
            'name' => 'کارت فلزی بانکی',
            'slug' => 'bank-card-'.uniqid(),
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
            'slug' => 'fuel-card-'.uniqid(),
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

        // 1. Bank-only design
        $this->bankOnlyDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح مخصوص بانک',
            'slug' => 'bank-only-'.uniqid(),
            'workspace_visibility' => DesignWorkspaceVisibilityEnum::BANK->value,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $this->bankOnlyImage = $this->createDesignImageWithCompatibility($this->bankOnlyDesign, 'designs/bank-only.svg');

        // 2. Fuel-only design
        $this->fuelOnlyDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح مخصوص سوخت',
            'slug' => 'fuel-only-'.uniqid(),
            'workspace_visibility' => DesignWorkspaceVisibilityEnum::FUEL->value,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        $this->fuelOnlyImage = $this->createDesignImageWithCompatibility($this->fuelOnlyDesign, 'designs/fuel-only.svg');

        // 3. Both workspaces design
        $this->bothDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح مشترک هر دو',
            'slug' => 'both-design-'.uniqid(),
            'workspace_visibility' => DesignWorkspaceVisibilityEnum::BOTH->value,
            'is_active' => true,
            'sort_order' => 3,
        ]);
        $this->bothImage = $this->createDesignImageWithCompatibility($this->bothDesign, 'designs/both.svg');

        // 4. Inactive design
        $this->inactiveDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح غیرفعال',
            'slug' => 'inactive-design-'.uniqid(),
            'workspace_visibility' => DesignWorkspaceVisibilityEnum::BOTH->value,
            'is_active' => false,
            'sort_order' => 4,
        ]);
        $this->createDesignImageWithCompatibility($this->inactiveDesign, 'designs/inactive.svg');
    }

    private function createDesignImageWithCompatibility(Design $design, string $path): DesignImage
    {
        Storage::disk('public')->put($path, '<svg></svg>');

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color->id,
            'image_path' => $path,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_id' => $design->id,
            'card_color_id' => $this->color->id,
            'design_image_id' => $image->id,
            'is_allowed' => true,
        ]);

        return $image;
    }

    /**
     * Requirement: Bank-only design is visible and selectable in Bank workspace, but NOT in Fuel workspace.
     */
    public function test_bank_only_design_is_visible_in_bank_workspace_and_not_in_fuel_workspace(): void
    {
        $catalogService = app(DesignCatalogService::class);
        $allowedImages = collect([$this->bankOnlyImage->id, $this->fuelOnlyImage->id, $this->bothImage->id]);

        // 1. Bank workspace:
        $bankPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        );
        $bankDesignIds = collect($bankPage->items())->pluck('id')->all();

        $this->assertContains($this->bankOnlyDesign->id, $bankDesignIds);
        $this->assertNotContains($this->fuelOnlyDesign->id, $bankDesignIds);
        $this->assertTrue($catalogService->isDesignInCatalog(
            $this->bankOnlyDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        ));

        // 2. Fuel workspace:
        $fuelPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        );
        $fuelDesignIds = collect($fuelPage->items())->pluck('id')->all();

        $this->assertNotContains($this->bankOnlyDesign->id, $fuelDesignIds);
        $this->assertFalse($catalogService->isDesignInCatalog(
            $this->bankOnlyDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        ));
    }

    /**
     * Requirement: Fuel-only design is visible and selectable in Fuel workspace, but NOT in Bank workspace.
     */
    public function test_fuel_only_design_is_visible_in_fuel_workspace_and_not_in_bank_workspace(): void
    {
        $catalogService = app(DesignCatalogService::class);
        $allowedImages = collect([$this->bankOnlyImage->id, $this->fuelOnlyImage->id, $this->bothImage->id]);

        // 1. Fuel workspace:
        $fuelPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        );
        $fuelDesignIds = collect($fuelPage->items())->pluck('id')->all();

        $this->assertContains($this->fuelOnlyDesign->id, $fuelDesignIds);
        $this->assertTrue($catalogService->isDesignInCatalog(
            $this->fuelOnlyDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        ));

        // 2. Bank workspace:
        $bankPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        );
        $bankDesignIds = collect($bankPage->items())->pluck('id')->all();

        $this->assertNotContains($this->fuelOnlyDesign->id, $bankDesignIds);
        $this->assertFalse($catalogService->isDesignInCatalog(
            $this->fuelOnlyDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        ));
    }

    /**
     * Requirement: Both-workspace design is visible and selectable in both Bank and Fuel workspaces.
     */
    public function test_both_workspaces_design_is_visible_in_both_workspaces(): void
    {
        $catalogService = app(DesignCatalogService::class);
        $allowedImages = collect([$this->bankOnlyImage->id, $this->fuelOnlyImage->id, $this->bothImage->id]);

        // Bank workspace
        $bankPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        );
        $this->assertContains($this->bothDesign->id, collect($bankPage->items())->pluck('id')->all());
        $this->assertTrue($catalogService->isDesignInCatalog(
            $this->bothDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::BANK_CARD
        ));

        // Fuel workspace
        $fuelPage = $catalogService->paginate(
            $this->category->id,
            $this->color->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        );
        $this->assertContains($this->bothDesign->id, collect($fuelPage->items())->pluck('id')->all());
        $this->assertTrue($catalogService->isDesignInCatalog(
            $this->bothDesign->id,
            $this->category->id,
            $allowedImages,
            CustomizationWorkflowEnum::FUEL_CARD
        ));
    }

    /**
     * Requirement: Inactive design is not visible in any workspace regardless of visibility setting.
     */
    public function test_inactive_design_is_not_visible_in_any_workspace(): void
    {
        $catalogService = app(DesignCatalogService::class);

        // Bank
        $this->assertFalse($catalogService->isDesignInCatalog(
            $this->inactiveDesign->id,
            $this->category->id,
            null,
            CustomizationWorkflowEnum::BANK_CARD
        ));

        // Fuel
        $this->assertFalse($catalogService->isDesignInCatalog(
            $this->inactiveDesign->id,
            $this->category->id,
            null,
            CustomizationWorkflowEnum::FUEL_CARD
        ));
    }

    /**
     * Requirement: Tampered request cannot select unauthorized design in Customizer or add it to cart.
     */
    public function test_tampered_request_cannot_select_or_purchase_unauthorized_design(): void
    {
        // 1. Livewire selectDesign tampering: bank customizer cannot select fuel-only design
        $component = Livewire::test(ProductCustomizer::class, [
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'productId' => $this->bankProduct->id,
        ]);
        $component->call('selectDesign', $this->fuelOnlyDesign->id);
        $this->assertNotSame($this->fuelOnlyDesign->id, $component->get('design_id'));

        // 2. Initial slug deep link tampering: bank customizer with fuel-only slug falls back safely
        $tamperedComponent = Livewire::test(ProductCustomizer::class, [
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'productId' => $this->bankProduct->id,
            'initialDesignSlug' => $this->fuelOnlyDesign->slug,
        ]);
        $this->assertNotSame($this->fuelOnlyDesign->id, $tamperedComponent->get('design_id'));

        // 3. CartService authoritative barrier: adding fuel-only design to bank card workflow is rejected
        $cartService = app(CartService::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected design is not available.');

        $cartService->addItem([
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->fuelOnlyDesign->id,
            'design_image_id' => $this->fuelOnlyImage->id,
            'quantity' => 1,
            'customization_json' => [],
        ]);
    }

    /**
     * Requirement: Adding bank-only design to fuel card workflow is also rejected.
     */
    public function test_adding_bank_only_design_to_fuel_cart_is_rejected(): void
    {
        $cartService = app(CartService::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Selected design is not available.');

        $cartService->addItem([
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->bankOnlyDesign->id,
            'design_image_id' => $this->bankOnlyImage->id,
            'quantity' => 1,
            'customization_json' => [
                'chip_info' => 'small',
            ],
        ]);
    }

    /**
     * Requirement: Admin can set and change workspace visibility in DesignWizard.
     */
    public function test_admin_can_set_and_change_workspace_visibility_via_design_wizard(): void
    {
        // 1. Validation requires valid visibility
        Livewire::actingAs($this->admin)
            ->test(DesignWizard::class)
            ->set('cateDesignId', $this->category->id)
            ->set('name', 'طرح ویزارد تست')
            ->set('workspaceVisibility', 'invalid_value')
            ->call('next')
            ->assertHasErrors(['workspaceVisibility' => 'in']);

        // 2. Create new design with bank-only visibility
        $wizard = Livewire::actingAs($this->admin)
            ->test(DesignWizard::class)
            ->set('cateDesignId', $this->category->id)
            ->set('name', 'طرح بانکی ویزارد')
            ->set('workspaceVisibility', 'bank')
            ->call('next')
            ->assertHasNoErrors();

        $createdDesignId = $wizard->get('designId');
        $this->assertNotNull($createdDesignId);

        $created = Design::find($createdDesignId);
        $this->assertSame(DesignWorkspaceVisibilityEnum::BANK, $created->workspace_visibility);

        // 3. Edit existing design to fuel-only
        Livewire::actingAs($this->admin)
            ->test(DesignWizard::class, ['designId' => $createdDesignId])
            ->assertSet('workspaceVisibility', 'bank')
            ->set('workspaceVisibility', 'fuel')
            ->call('next')
            ->assertHasNoErrors();

        $created->refresh();
        $this->assertSame(DesignWorkspaceVisibilityEnum::FUEL, $created->workspace_visibility);
    }

    /**
     * Requirement: Admin can set and change workspace visibility in DesignManager.
     */
    public function test_admin_can_set_and_change_workspace_visibility_via_design_manager(): void
    {
        $manager = Livewire::actingAs($this->admin)
            ->test(DesignManager::class)
            ->call('edit', $this->bankOnlyDesign->id)
            ->assertSet('workspaceVisibility', 'bank')
            ->set('workspaceVisibility', 'both')
            ->call('save')
            ->assertHasNoErrors();

        $this->bankOnlyDesign->refresh();
        $this->assertSame(DesignWorkspaceVisibilityEnum::BOTH, $this->bankOnlyDesign->workspace_visibility);
    }

    /**
     * Requirement: Historical designs with null workspace_visibility default to both and remain visible.
     */
    public function test_legacy_designs_with_null_visibility_default_to_both_and_remain_visible(): void
    {
        $legacyDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح قدیمی بدون مقدار',
            'slug' => 'legacy-design-'.uniqid(),
            'workspace_visibility' => null,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $legacyImage = $this->createDesignImageWithCompatibility($legacyDesign, 'designs/legacy.svg');

        $catalogService = app(DesignCatalogService::class);
        $allowed = collect([$legacyImage->id]);

        // Must be visible in Bank
        $this->assertTrue($catalogService->isDesignInCatalog(
            $legacyDesign->id,
            $this->category->id,
            $allowed,
            CustomizationWorkflowEnum::BANK_CARD
        ));

        // Must be visible in Fuel
        $this->assertTrue($catalogService->isDesignInCatalog(
            $legacyDesign->id,
            $this->category->id,
            $allowed,
            CustomizationWorkflowEnum::FUEL_CARD
        ));
    }

    /**
     * Requirement: Historical order with snapshots remains intact when design visibility changes.
     */
    public function test_historical_order_with_snapshot_remains_intact_when_design_visibility_changes(): void
    {
        // 1. Order placed with bothDesign for a bank card
        $order = new Order([
            'customer_name' => 'مشتری تست',
            'customer_phone' => '09121111111',
        ]);
        $order->user_id = $this->admin->id;
        $order->status = OrderStatusEnum::PENDING;
        $order->payment_status = PaymentStatusEnum::UNPAID;
        $order->total_price = 500000;
        $order->save();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->bankProduct->id,
            'product_name_snapshot' => $this->bankProduct->name,
            'color_id' => $this->color->id,
            'color_name_snapshot' => $this->color->name,
            'design_id' => $this->bothDesign->id,
            'design_name_snapshot' => $this->bothDesign->name,
            'design_image_id' => $this->bothImage->id,
            'design_image_path_snapshot' => $this->bothImage->image_path,
            'quantity' => 1,
            'unit_price_snapshot' => 500000,
            'final_price' => 500000,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD,
            'customization_json' => [
                'card_holder_name' => 'HISTORICAL HOLDER',
            ],
        ]);

        // 2. Admin changes design visibility to fuel only
        $this->bothDesign->update(['workspace_visibility' => DesignWorkspaceVisibilityEnum::FUEL->value]);
        $this->bothDesign->refresh();
        $this->assertSame(DesignWorkspaceVisibilityEnum::FUEL, $this->bothDesign->workspace_visibility);

        // 3. Historical order item remains completely intact and valid
        $freshItem = $item->fresh();
        $this->assertSame($this->bothDesign->id, $freshItem->design_id);
        $this->assertSame('طرح مشترک هر دو', $freshItem->design_name_snapshot);
        $this->assertSame('designs/both.svg', $freshItem->design_image_path_snapshot);

        // Order item can still be saved for unrelated updates
        $freshItem->quantity = 2;
        $freshItem->save();
        $this->assertSame(2, $freshItem->fresh()->quantity);
    }
}
