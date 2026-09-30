<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\DesignImageManager;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * N-Onyx-51 F3 - replacing a design image through the standalone admin image
 * manager must not leak the previous file off the public disk.
 *
 * DesignWizard already ran the order-aware cleanup on replacement, but
 * DesignImageManager::save() only wrote the new path, so every replacement made
 * through that form orphaned the old asset on disk.
 *
 * The cleanup must stay order-aware: a paid order keeps its purchased asset
 * alive through order_items.design_image_path_snapshot even after the catalog
 * row is gone, so the file may not be unlinked. Nothing in this file asserts
 * runtime purchase authority or the file-reference policy itself, which
 * StoredFileCleanupTest already owns.
 */
class DesignImageManagerFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: DesignImage, 1: string} the image under test and its path
     */
    private function readyCatalog(): array
    {
        $category = CateDesign::create([
            'name' => 'دسته',
            'slug' => 'f3-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $color = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح',
            'slug' => 'f3-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/f3-original.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        // A live, fully priced product so the F2 blocker permits the edit and the
        // test isolates F3 rather than tripping over the visibility guard.
        $product = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'محصول فول',
            'slug' => 'f3-product-'.uniqid(),
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        // A second active image keeps the design visible across the replacement.
        DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/f3-sibling.png',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Storage::disk('public')->put($image->image_path, 'original-bytes');

        return [$image, $image->image_path];
    }

    /**
     * A paid order whose snapshot is the only thing keeping $path alive.
     */
    private function placePaidOrderPointingAt(string $path): Order
    {
        [$purchased] = $this->readyCatalog();

        $order = new Order;
        $order->forceFill([
            'customer_name' => 'مشتری',
            'customer_phone' => '09120000000',
            'status' => OrderStatusEnum::COMPLETED->value,
            'payment_status' => PaymentStatusEnum::PAID->value,
            'total_price' => 500000,
        ])->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => Product::query()->value('id'),
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'product_name_snapshot' => 'محصول فول',
            'color_id' => $purchased->color_id,
            'color_name_snapshot' => 'مشکی',
            'design_id' => $purchased->design_id,
            'design_name_snapshot' => 'طرح',
            'design_image_id' => $purchased->id,
            'design_image_path_snapshot' => $path,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => [],
        ]);

        return $order;
    }

    private function replacePathVia(DesignImage $image, string $newPath): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('imagePath', $newPath)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($newPath, $image->fresh()->image_path, 'The new path must be persisted.');
    }

    public function test_replacing_an_unreferenced_path_through_the_image_manager_frees_the_old_file(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();
        Storage::disk('public')->assertExists($oldPath);

        $newPath = 'designs/f3-replacement.png';
        Storage::disk('public')->put($newPath, 'replacement-bytes');

        $this->replacePathVia($image, $newPath);

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_replacing_a_path_a_paid_order_bought_keeps_the_old_file(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();
        $this->placePaidOrderPointingAt($oldPath);

        $newPath = 'designs/f3-replacement-paid.png';
        Storage::disk('public')->put($newPath, 'replacement-bytes');

        $this->replacePathVia($image, $newPath);

        // A paid order snapshot must keep the purchased asset on disk.
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_replacing_a_path_keeps_a_file_still_referenced_by_another_image(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();
        $twin = DesignImage::create([
            'design_id' => $image->design_id,
            'color_id' => $image->color_id,
            'image_path' => $oldPath,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $newPath = 'designs/f3-replacement-shared.png';
        Storage::disk('public')->put($newPath, 'replacement-bytes');

        $this->replacePathVia($image, $newPath);

        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame($oldPath, $twin->fresh()->image_path);
    }

    public function test_rewriting_the_same_path_does_not_trigger_any_cleanup(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();

        $this->replacePathVia($image, $oldPath);

        Storage::disk('public')->assertExists($oldPath);
        $this->assertSame($oldPath, $image->fresh()->image_path);
    }

    public function test_a_refused_save_does_not_free_the_previous_file(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();

        // Missing colour fails validation, so no write happens at all.
        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('colorId', null)
            ->set('imagePath', 'designs/f3-never-written.png')
            ->call('save')
            ->assertHasErrors('colorId');

        $this->assertSame($oldPath, $image->fresh()->image_path);
        Storage::disk('public')->assertExists($oldPath);
    }

    public function test_a_f2_blocked_save_does_not_free_the_previous_file(): void
    {
        Storage::fake('public');

        [$image, $oldPath] = $this->readyCatalog();

        // Drop the sibling so this is the only active image, then try to
        // deactivate it. The F2 blocker refuses before the update runs, so the
        // F3 cleanup must not fire either.
        DesignImage::query()
            ->where('design_id', $image->design_id)
            ->where('id', '!=', $image->id)
            ->delete();

        Livewire::actingAs(User::factory()->create(['role' => 'admin']))
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->set('isActive', false)
            ->set('imagePath', 'designs/f3-blocked.png')
            ->call('save');

        $this->assertTrue((bool) $image->fresh()->is_active);
        $this->assertSame($oldPath, $image->fresh()->image_path);
        Storage::disk('public')->assertExists($oldPath);
    }
}
