<?php

namespace Tests\Feature;

use App\Enums\DesignImageLayoutModeEnum;
use App\Enums\ProductTypeEnum;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DesignLayoutModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Color $color;
    private Design $design;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);

        $category = CateDesign::create([
            'name' => 'General',
            'slug' => 'general',
            'is_active' => true,
        ]);

        $this->color = Color::create([
            'name' => 'مشکی مات',
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'تست طرح شاهین',
            'slug' => 'test-falcon',
            'is_active' => true,
        ]);
    }

    public function test_design_image_defaults_to_centered_layout_mode(): void
    {
        $image = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/falcon-centered.png',
            'is_active' => true,
        ]);

        $image->refresh();

        $this->assertSame(DesignImageLayoutModeEnum::CENTERED, $image->layout_mode);
        $this->assertSame(DesignImageLayoutModeEnum::CENTERED, $image->getLayoutMode());
        $this->assertFalse($image->isFullCard());
    }

    public function test_design_image_can_be_stored_with_full_card_layout_mode(): void
    {
        $image = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/falcon-full.png',
            'layout_mode' => DesignImageLayoutModeEnum::FULL_CARD,
            'is_active' => true,
        ]);

        $image->refresh();

        $this->assertSame(DesignImageLayoutModeEnum::FULL_CARD, $image->layout_mode);
        $this->assertSame(DesignImageLayoutModeEnum::FULL_CARD, $image->getLayoutMode());
        $this->assertTrue($image->isFullCard());
    }

    public function test_design_wizard_validates_and_persists_layout_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\DesignWizard::class, ['designId' => $this->design->id])
            ->set('step', 2)
            ->set('colorId', $this->color->id)
            ->set('imagePath', 'designs/wizard-full.png')
            ->set('layoutMode', 'full_card')
            ->call('saveImage')
            ->assertHasNoErrors();

        $saved = DesignImage::where('image_path', 'designs/wizard-full.png')->first();
        $this->assertNotNull($saved);
        $this->assertSame(DesignImageLayoutModeEnum::FULL_CARD, $saved->layout_mode);

        // Test editing layout mode back to centered
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\DesignWizard::class, ['designId' => $this->design->id])
            ->set('step', 2)
            ->call('editImage', $saved->id)
            ->assertSet('layoutMode', 'full_card')
            ->set('layoutMode', 'centered')
            ->call('saveImage')
            ->assertHasNoErrors();

        $saved->refresh();
        $this->assertSame(DesignImageLayoutModeEnum::CENTERED, $saved->layout_mode);
    }

    public function test_design_wizard_rejects_invalid_layout_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\DesignWizard::class, ['designId' => $this->design->id])
            ->set('step', 2)
            ->set('colorId', $this->color->id)
            ->set('imagePath', 'designs/invalid.png')
            ->set('layoutMode', 'stretched_invalid')
            ->call('saveImage')
            ->assertHasErrors(['layoutMode']);
    }

    public function test_design_image_manager_validates_and_persists_layout_mode(): void
    {
        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Admin\DesignImageManager::class)
            ->set('designId', $this->design->id)
            ->set('colorId', $this->color->id)
            ->set('imagePath', 'designs/manager-full.png')
            ->set('layoutMode', 'full_card')
            ->call('save')
            ->assertHasNoErrors();

        $saved = DesignImage::where('image_path', 'designs/manager-full.png')->first();
        $this->assertNotNull($saved);
        $this->assertSame(DesignImageLayoutModeEnum::FULL_CARD, $saved->layout_mode);
    }

    public function test_cart_service_snapshots_layout_mode_into_order_item(): void
    {
        $product = Product::create([
            'type' => \App\Enums\ProductTypeEnum::BANK->value,
            'customization_workflow' => \App\Enums\CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی تست',
            'slug' => 'bank-card-test-'.uniqid(),
            'base_price' => null,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $fullCardImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/falcon-edge2edge.png',
            'layout_mode' => DesignImageLayoutModeEnum::FULL_CARD,
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $fullCardImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $fullCardImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_number' => '6274000000000000',
                'card_holder_name' => 'HOSSEIN REZAIE',
            ],
        ]);

        $this->assertNotEmpty($cart['items']);
        $item = reset($cart['items']);
        $this->assertSame('full_card', $item['layout_mode_snapshot']);

        $order = $cartService->createDraftOrder([
            'customer_name' => 'خریدار تستی',
            'customer_phone' => '09121111111',
        ]);

        $orderItem = OrderItem::where('order_id', $order->id)->first();
        $this->assertNotNull($orderItem);
        $this->assertSame('full_card', $orderItem->layout_mode_snapshot);
        $this->assertSame('full_card', $orderItem->getLayoutModeSnapshot());
    }

    public function test_order_item_backward_compatibility_fallback(): void
    {
        $order = Order::forceCreate([
            'customer_name' => 'خریدار قدیمی',
            'customer_phone' => '09121111111',
            'total_price' => 100000,
        ]);

        $product = Product::create([
            'type' => \App\Enums\ProductTypeEnum::STANDARD->value,
            'name' => 'محصول قدیمی',
            'slug' => 'old-product-'.uniqid(),
            'base_price' => 100000,
            'is_active' => true,
        ]);

        $legacyItem = OrderItem::forceCreate([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => 'محصول قدیمی',
            'unit_price_snapshot' => 100000,
            'quantity' => 1,
            'final_price' => 100000,
            'customization_json' => [],
        ]);

        $this->assertSame('centered', $legacyItem->getLayoutModeSnapshot());

        // In-memory null fallback check
        $legacyItem->layout_mode_snapshot = null;
        $this->assertSame('centered', $legacyItem->getLayoutModeSnapshot());
    }

    public function test_product_customizer_renders_centered_and_full_card_layouts(): void
    {
        $product = Product::create([
            'type' => \App\Enums\ProductTypeEnum::STANDARD->value,
            'customization_workflow' => \App\Enums\CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت فلزی ویژه',
            'slug' => 'special-card-'.uniqid(),
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        $centeredImage = DesignImage::create([
            'design_id' => $this->design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/eagle-center.png',
            'layout_mode' => DesignImageLayoutModeEnum::CENTERED,
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $centeredImage->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        // Centered layout mode in customizer
        $component = Livewire::test(\App\Livewire\Catalog\ProductCustomizer::class, ['productId' => $product->id])
            ->call('selectColor', $this->color->id)
            ->call('selectDesign', $this->design->id)
            ->assertSee('object-contain')
            ->assertDontSee('object-cover');

        // Now switch image to full_card
        $centeredImage->update(['layout_mode' => DesignImageLayoutModeEnum::FULL_CARD]);

        Livewire::test(\App\Livewire\Catalog\ProductCustomizer::class, ['productId' => $product->id])
            ->call('selectColor', $this->color->id)
            ->call('selectDesign', $this->design->id)
            ->assertSee('object-cover')
            ->assertSee('object-center');
    }
}
