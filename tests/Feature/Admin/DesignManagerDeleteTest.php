<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\DesignManager;
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

class DesignManagerDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function setupCategoryAndColors(): array
    {
        $category = CateDesign::create([
            'name' => 'دسته‌بندی اصلی',
            'slug' => 'cat-' . uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $goldColor = Color::create([
            'name' => 'طلایی',
            'code_hex' => '#FFD700',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $silverColor = Color::create([
            'name' => 'نقره‌ای',
            'code_hex' => '#C0C0C0',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $blackColor = Color::create([
            'name' => 'مشکی',
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        return [$category, $goldColor, $silverColor, $blackColor];
    }

    public function test_deleting_a_design_with_multiple_images_succeeds_and_removes_owned_records_and_files(): void
    {
        Storage::fake('public');
        [$category, $goldColor, $silverColor, $blackColor] = $this->setupCategoryAndColors();

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح عقاب طلایی',
            'slug' => 'golden-eagle-' . uniqid(),
            'is_active' => false, // Inactive so it doesn't trigger sole-purchasable product blocker
            'sort_order' => 1,
        ]);

        $path1 = 'designs/eagle-gold.png';
        $path2 = 'designs/eagle-silver.png';
        Storage::disk('public')->put($path1, 'image-1-bytes');
        Storage::disk('public')->put($path2, 'image-2-bytes');

        $img1 = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $goldColor->id,
            'image_path' => $path1,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $img2 = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $silverColor->id,
            'image_path' => $path2,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $compat1 = DesignColorCompatibility::create([
            'design_image_id' => $img1->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        $compat2 = DesignColorCompatibility::create([
            'design_image_id' => $img1->id,
            'card_color_id' => $blackColor->id,
            'is_allowed' => true,
        ]);

        $compat3 = DesignColorCompatibility::create([
            'design_image_id' => $img2->id,
            'card_color_id' => $silverColor->id,
            'is_allowed' => true,
        ]);

        // Execute deletion via Livewire
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $design->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'success';
            });

        // 1. Design record is deleted
        $this->assertDatabaseMissing('designs', ['id' => $design->id]);

        // 2. All DesignImage records are deleted
        $this->assertDatabaseMissing('design_images', ['id' => $img1->id]);
        $this->assertDatabaseMissing('design_images', ['id' => $img2->id]);

        // 3. All DesignColorCompatibility records are deleted
        $this->assertDatabaseMissing('design_color_compatibilities', ['id' => $compat1->id]);
        $this->assertDatabaseMissing('design_color_compatibilities', ['id' => $compat2->id]);
        $this->assertDatabaseMissing('design_color_compatibilities', ['id' => $compat3->id]);

        // 4. Physical files owned exclusively by this design are deleted from storage
        Storage::disk('public')->assertMissing($path1);
        Storage::disk('public')->assertMissing($path2);

        // 5. Shared Color records remain intact
        $this->assertDatabaseHas('colors', ['id' => $goldColor->id]);
        $this->assertDatabaseHas('colors', ['id' => $silverColor->id]);
        $this->assertDatabaseHas('colors', ['id' => $blackColor->id]);
    }

    public function test_deleting_a_design_preserves_other_designs_and_their_files(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        // Design A
        $designA = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح الف',
            'slug' => 'design-a-' . uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);
        $pathA = 'designs/design-a.png';
        Storage::disk('public')->put($pathA, 'bytes-a');
        $imgA = DesignImage::create([
            'design_id' => $designA->id,
            'color_id' => $goldColor->id,
            'image_path' => $pathA,
            'is_active' => true,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $imgA->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        // Design B
        $designB = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح ب',
            'slug' => 'design-b-' . uniqid(),
            'is_active' => false,
            'sort_order' => 2,
        ]);
        $pathB = 'designs/design-b.png';
        Storage::disk('public')->put($pathB, 'bytes-b');
        $imgB = DesignImage::create([
            'design_id' => $designB->id,
            'color_id' => $goldColor->id,
            'image_path' => $pathB,
            'is_active' => true,
        ]);
        DesignColorCompatibility::create([
            'design_image_id' => $imgB->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        // Delete Design A
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $designA->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'success';
            });

        // Design A is gone
        $this->assertDatabaseMissing('designs', ['id' => $designA->id]);
        Storage::disk('public')->assertMissing($pathA);

        // Design B and its files are completely intact
        $this->assertDatabaseHas('designs', ['id' => $designB->id]);
        $this->assertDatabaseHas('design_images', ['id' => $imgB->id]);
        Storage::disk('public')->assertExists($pathB);
    }

    public function test_deleting_a_design_preserves_shared_file_if_referenced_by_another_design_image(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        $sharedPath = 'designs/shared-pattern.png';
        Storage::disk('public')->put($sharedPath, 'shared-bytes');

        // Design 1 uses the shared file
        $design1 = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح اول',
            'slug' => 'design-1-' . uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);
        $img1 = DesignImage::create([
            'design_id' => $design1->id,
            'color_id' => $goldColor->id,
            'image_path' => $sharedPath,
            'is_active' => true,
        ]);

        // Design 2 ALSO uses the shared file
        $design2 = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح دوم',
            'slug' => 'design-2-' . uniqid(),
            'is_active' => false,
            'sort_order' => 2,
        ]);
        $img2 = DesignImage::create([
            'design_id' => $design2->id,
            'color_id' => $goldColor->id,
            'image_path' => $sharedPath,
            'is_active' => true,
        ]);

        // Delete Design 1
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $design1->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'success';
            });

        // Design 1 is deleted
        $this->assertDatabaseMissing('designs', ['id' => $design1->id]);
        $this->assertDatabaseMissing('design_images', ['id' => $img1->id]);

        // Shared physical file MUST be preserved because Design 2 still uses it!
        Storage::disk('public')->assertExists($sharedPath);
        $this->assertDatabaseHas('design_images', ['id' => $img2->id]);
    }

    public function test_deleting_a_design_preserves_file_and_historical_snapshots_for_existing_orders(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        $purchasedPath = 'designs/lion-purchased.png';
        Storage::disk('public')->put($purchasedPath, 'lion-bytes');

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح شیر سلطنتی',
            'slug' => 'royal-lion-' . uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $goldColor->id,
            'image_path' => $purchasedPath,
            'is_active' => true,
        ]);

        // Create a historical order that bought this design
        $order = new Order;
        $order->forceFill([
            'customer_name' => 'خریدار نمونه',
            'customer_phone' => '09121111111',
            'status' => OrderStatusEnum::COMPLETED->value,
            'payment_status' => PaymentStatusEnum::PAID->value,
            'total_price' => 500000,
        ])->save();

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'product_name_snapshot' => 'کارت بانکی فلزی',
            'color_id' => $goldColor->id,
            'color_name_snapshot' => 'طلایی',
            'design_id' => $design->id,
            'design_name_snapshot' => 'طرح شیر سلطنتی',
            'design_image_id' => $image->id,
            'design_image_path_snapshot' => $purchasedPath,
            'unit_price_snapshot' => 500000,
            'quantity' => 1,
            'final_price' => 500000,
            'customization_json' => ['card_holder_name' => 'علی تستی'],
        ]);

        // Delete the design
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $design->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'success';
            });

        // Design and image rows are deleted
        $this->assertDatabaseMissing('designs', ['id' => $design->id]);
        $this->assertDatabaseMissing('design_images', ['id' => $image->id]);

        // Historical order item is preserved
        $refreshedItem = $orderItem->fresh();
        $this->assertNotNull($refreshedItem);

        // Foreign keys set null via nullOnDelete constraint
        $this->assertNull($refreshedItem->design_id);
        $this->assertNull($refreshedItem->design_image_id);

        // Critical historical snapshots are intact
        $this->assertSame('طرح شیر سلطنتی', $refreshedItem->design_name_snapshot);
        $this->assertSame($purchasedPath, $refreshedItem->design_image_path_snapshot);
        $this->assertSame('طلایی', $refreshedItem->color_name_snapshot);

        // Physical file must NOT be deleted because the historical order item references it
        Storage::disk('public')->assertExists($purchasedPath);
    }

    public function test_deleting_design_is_blocked_if_it_is_the_only_purchasable_design_for_an_active_product(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        $path = 'designs/exclusive.png';
        Storage::disk('public')->put($path, 'exclusive-bytes');

        // Create active product requiring this design
        $product = Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت انحصاری',
            'slug' => 'exclusive-fuel-' . uniqid(),
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $goldColor->id,
            'price' => 500000,
            'is_active' => true,
        ]);

        // The sole active design
        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح انحصاری سوخت',
            'slug' => 'exclusive-design-' . uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $goldColor->id,
            'image_path' => $path,
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        // Attempting to delete must be blocked by designDeactivationBlocker
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $design->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'error';
            });

        // Design and files must survive
        $this->assertDatabaseHas('designs', ['id' => $design->id]);
        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($path);
    }

    public function test_deleting_non_existent_design_produces_error_toast(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', 999999)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'error' && $params['message'] === 'طرح موردنظر یافت نشد';
            });
    }

    public function test_deleting_design_resets_form_if_it_was_being_edited(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح در حال ویرایش',
            'slug' => 'editing-design-' . uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('edit', $design->id)
            ->assertSet('editingId', $design->id)
            ->assertSet('showForm', true)
            ->call('delete', $design->id)
            ->assertSet('editingId', null)
            ->assertSet('showForm', false)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'success';
            });
    }

    public function test_failed_deletion_produces_visible_error_notification_and_rolls_back(): void
    {
        Storage::fake('public');
        [$category, $goldColor] = $this->setupCategoryAndColors();

        $path = 'designs/fail-test.png';
        Storage::disk('public')->put($path, 'bytes');

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح با خطای تراکنش',
            'slug' => 'failing-design-' . uniqid(),
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $goldColor->id,
            'image_path' => $path,
            'is_active' => true,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $goldColor->id,
            'is_allowed' => true,
        ]);

        // Hook into Design deleting event to simulate a database failure
        Design::deleting(function () {
            throw new \RuntimeException('Database failure simulated');
        });

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $design->id)
            ->assertDispatched('elva-toast', function ($event, $params) {
                return ($params['type'] ?? '') === 'error' && str_contains($params['message'] ?? '', 'خطایی در حذف طرح رخ داد');
            });

        // Verify rollback: design, image, and file still exist!
        $this->assertDatabaseHas('designs', ['id' => $design->id]);
        $this->assertDatabaseHas('design_images', ['id' => $image->id]);
        Storage::disk('public')->assertExists($path);
    }
}
