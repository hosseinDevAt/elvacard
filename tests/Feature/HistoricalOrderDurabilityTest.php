<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Exceptions\OrderLifecycleConstraintException;
use App\Livewire\Admin\ColorManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\DesignWizard;
use App\Livewire\Admin\OrderManager;
use App\Livewire\Admin\ProductManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\CartService;
use App\Services\FuelCard\FuelCardCustomization;
use App\Services\ManualRefundService;
use App\Services\OrderStateMachine;
use App\Services\StoredFileManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * N-Onyx-46 — Fulfillment & File Durability Hardening
 *
 * The central acceptance criterion: once an order becomes financially
 * relevant, later catalog administration must not make it impossible to
 * understand, fulfill or audit.
 *
 * N-Onyx-45 already proved this for the Fuel design-image asset. This suite
 * extends the proof to the whole catalog/asset boundary: every workflow
 * (Store, Bank, Fuel), every destructive catalog operation, the customer
 * history surface, ownership scoping, and the shared-file / replacement cases
 * that N-Onyx-45 did not cover.
 */
class HistoricalOrderDurabilityTest extends TestCase
{
    use RefreshDatabase;

    private CateDesign $category;

    private Color $color;

    private Design $design;

    private DesignImage $designImage;

    private Product $fuelProduct;

    private Product $bankProduct;

    private Product $storeProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CateDesign::create([
            'name' => 'دسته دوام تاریخی',
            'slug' => 'onyx46-cat-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->color = Color::create([
            'name' => 'آبی دوام',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        [$this->design, $this->designImage] = $this->createDesignAsset('onyx46');

        $this->fuelProduct = $this->createProduct(
            ProductTypeEnum::FUEL,
            CustomizationWorkflowEnum::FUEL_CARD,
            'کارت سوخت دوام',
            450000,
            480000,
        );

        $this->bankProduct = $this->createProduct(
            ProductTypeEnum::BANK,
            CustomizationWorkflowEnum::BANK_CARD,
            'کارت بانکی دوام',
            500000,
            600000,
        );

        // A Store product is ordinary commerce: no workflow, no design.
        $this->storeProduct = $this->createProduct(
            ProductTypeEnum::STANDARD,
            null,
            'محصول فروشگاهی دوام',
            250000,
            300000,
        );
    }

    // =========================================================================
    // PHASE 3 — STRUCTURED DATA DURABILITY
    // =========================================================================

    public function test_a_store_order_snapshot_can_reconstruct_the_purchase(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->storePayload(2));

        $item = $order->items()->sole();

        $this->assertSame('محصول فروشگاهی دوام', $item->product_name_snapshot);
        $this->assertSame('آبی دوام', $item->color_name_snapshot);
        $this->assertSame(300000, (int) $item->unit_price_snapshot);
        $this->assertSame(2, (int) $item->quantity);
        $this->assertSame(600000, (int) $item->final_price);
        $this->assertNull($item->customization_workflow, 'A Store line must snapshot a null workflow.');
        $this->assertSame([], $item->customization_json);
    }

    public function test_a_bank_order_snapshot_can_reconstruct_the_purchase(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->bankPayload());

        $item = $order->items()->sole();

        $this->assertSame('کارت بانکی دوام', $item->product_name_snapshot);
        $this->assertSame('آبی دوام', $item->color_name_snapshot);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame($this->designImage->image_path, $item->design_image_path_snapshot);
        $this->assertSame(600000, (int) $item->unit_price_snapshot);
        $this->assertSame('علی رضایی', $item->customization_json['card_holder_name']);
        $this->assertSame('6274051234567890', $item->customization_json['card_number']);
    }

    public function test_a_fuel_order_snapshot_can_reconstruct_the_purchase(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        $item = $order->items()->sole();

        $this->assertSame('کارت سوخت دوام', $item->product_name_snapshot);
        $this->assertSame('آبی دوام', $item->color_name_snapshot);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame($this->designImage->image_path, $item->design_image_path_snapshot);
        $this->assertSame(480000, (int) $item->unit_price_snapshot);

        $fields = FuelCardCustomization::fulfillmentFields($item->customization_json);

        $this->assertSame(
            ['owner_name', 'car_info', 'vin', 'system_name', 'system_identifier', 'plate_number', 'chip_info'],
            array_keys($fields),
            'All seven Fuel production fields must be reconstructable from the snapshot.',
        );
        $this->assertSame('NAAAAAAAAAAAAAAA1', $fields['vin']['value']);
    }

    // =========================================================================
    // PHASE 4 — CATALOG MUTATION MATRIX
    // =========================================================================

    public function test_deleting_an_unused_design_is_still_allowed(): void
    {
        // The catalog must remain administrable: an unreferenced design is
        // SAFE to remove and must not be blocked by the order guard.
        $unusedDesign = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح بدون تصویر',
            'slug' => 'unused-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $unusedDesign->id)
            ->assertHasNoErrors();

        $this->assertNull(Design::find($unusedDesign->id));
    }

    public function test_a_design_used_by_an_unpaid_order_can_go_without_breaking_the_order(): void
    {
        $order = $this->placeDraftOrder($this->customer(), $this->fuelPayload());
        $item = $order->items()->sole();

        $this->deleteDesignAndItsImage();

        $item->refresh();

        $this->assertNull($item->design_id);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot, 'The unpaid order still identifies what was bought.');
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);
    }

    public function test_a_design_used_by_a_paid_order_can_go_without_breaking_the_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());
        $item = $order->items()->sole();

        $this->deleteDesignAndItsImage();

        $item->refresh();

        $this->assertNull($item->design_id);
        $this->assertNull($item->design_image_id);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame($this->designImage->image_path, $item->design_image_path_snapshot);
    }

    public function test_deleting_a_product_referenced_by_an_order_is_blocked(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->storePayload(1));

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $this->storeProduct->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            Product::find($this->storeProduct->id),
            'A product that appears on an order must not be deletable.',
        );
        $this->assertSame(1, $order->items()->count());
    }

    public function test_deactivating_a_product_does_not_change_a_paid_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->storePayload(2));

        // Price drift plus deactivation is the classic silent history rewrite.
        $this->storeProduct->update(['is_active' => false, 'base_price' => 999]);
        $this->storeProduct->colorPrices()->update(['price' => 1, 'is_active' => false]);

        $item = $order->items()->sole()->fresh();

        $this->assertSame('محصول فروشگاهی دوام', $item->product_name_snapshot);
        $this->assertSame(300000, (int) $item->unit_price_snapshot, 'The historical price must not follow the catalog.');
        $this->assertSame(600000, (int) $item->final_price);
    }

    public function test_deactivating_a_color_does_not_change_a_paid_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        $this->color->update(['is_active' => false, 'name' => 'غیرفعال شد']);

        $item = $order->items()->sole()->fresh();

        $this->assertSame('آبی دوام', $item->color_name_snapshot, 'A renamed or deactivated color must not rewrite history.');
        $this->assertSame($this->color->id, (int) $item->color_id, 'Deactivation must not detach the relation.');
    }

    public function test_deactivating_a_design_or_design_image_does_not_change_a_paid_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        $this->design->update(['is_active' => false, 'name' => 'طرح غیرفعال']);
        $this->designImage->update(['is_active' => false]);

        $item = $order->items()->sole()->fresh();

        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame($this->designImage->image_path, $item->design_image_path_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);
    }

    public function test_deleting_a_color_used_by_a_design_image_is_blocked(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('delete', $this->color->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            Color::find($this->color->id),
            'A color still used by a design image must not be deletable.',
        );
    }

    public function test_deleting_a_design_that_still_owns_images_is_blocked(): void
    {
        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('delete', $this->design->id)
            ->assertHasNoErrors();

        $this->assertNotNull(
            Design::find($this->design->id),
            'A design that still owns images must not be deletable.',
        );
    }

    // =========================================================================
    // PHASE 7 — ADMIN FULFILLMENT READ MODEL
    // =========================================================================

    public function test_bank_admin_fulfillment_reads_the_historical_snapshot_after_catalog_mutation(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->bankPayload());

        $this->designImage->delete();
        $this->design->delete();
        $this->color->update(['name' => 'حذف‌شده', 'is_active' => false]);
        $this->bankProduct->update(['name' => 'حذف‌شده', 'is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('کارت بانکی دوام')
            ->assertSee('آبی دوام')
            ->assertSee('طرح دوام تست')
            ->assertSee('علی رضایی');
    }

    public function test_bank_admin_fulfillment_never_renders_fuel_or_forged_keys(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->bankPayload());

        // A hand-edited Bank row carrying Fuel and foreign keys must not leak
        // them into the Bank fulfillment surface.
        $item = $order->items()->sole();
        $snapshot = $item->customization_json;
        $snapshot['vin'] = 'FORGEDVIN123456789';
        $snapshot['plate_number'] = 'FORGEDPLATE';
        $snapshot['owner_name'] = 'FORGEDOWNER';
        $item->update(['customization_json' => $snapshot]);

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('علی رضایی')
            ->assertDontSee('FORGEDVIN123456789')
            ->assertDontSee('FORGEDPLATE')
            ->assertDontSee('FORGEDOWNER');
    }

    public function test_fuel_admin_fulfillment_keeps_all_seven_fields_after_catalog_mutation(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload([
            'owner_name' => 'حسین اکبری',
            'car_info' => 'پراید سفید',
            'system_name' => 'کارت سوخت ملی',
            'system_identifier' => 'NATIONAL-A',
            'plate_number' => '۱۱ب۱۱۱',
            'chip_info' => 'large',
        ]));

        $this->designImage->delete();
        $this->design->delete();
        $this->color->update(['name' => 'حذف‌شده', 'is_active' => false]);
        $this->fuelProduct->update(['name' => 'حذف‌شده', 'is_active' => false]);

        // Assert against the value the application actually snapshotted: the
        // Fuel pipeline normalizes plate numbers, and durability means whatever
        // was persisted is still what the admin reads.
        $snapshot = $order->items()->sole()->fresh()->customization_json;

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('حسین اکبری')
            ->assertSee('پراید سفید')
            ->assertSee($snapshot['vin'])
            ->assertSee('کارت سوخت ملی')
            ->assertSee('NATIONAL-A')
            ->assertSee($snapshot['plate_number'])
            ->assertSee('بزرگ')
            ->assertSee('طرح دوام تست')
            ->assertSee('کارت سوخت دوام');
    }

    // =========================================================================
    // PHASE 8 — CUSTOMER ORDER HISTORY
    // =========================================================================

    public function test_a_customer_can_still_view_a_historical_fuel_order_after_catalog_deletion(): void
    {
        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->fuelPayload([
            'owner_name' => 'حسین اکبری',
            'plate_number' => '11B111',
            'chip_info' => 'large',
        ]));

        $this->deleteDesignAndItsImage();
        $this->color->update(['name' => 'حذف‌شده', 'is_active' => false]);

        $snapshot = $order->items()->sole()->fresh()->customization_json;

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('کارت سوخت دوام')
            ->assertSee('طرح دوام تست')
            ->assertSee($snapshot['vin'])
            ->assertSee('حسین اکبری')
            ->assertSee($snapshot['plate_number'])
            ->assertSee('بزرگ');
    }

    public function test_a_customer_can_still_view_a_historical_bank_order_after_catalog_deletion(): void
    {
        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->bankPayload());

        $this->deleteDesignAndItsImage();

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('کارت بانکی دوام')
            ->assertSee('طرح دوام تست')
            ->assertSee('علی رضایی');
    }

    public function test_a_customer_can_still_view_a_historical_store_order_after_catalog_mutation(): void
    {
        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->storePayload(2));

        $this->storeProduct->update(['name' => 'حذف‌شده', 'is_active' => false]);
        $this->color->update(['name' => 'حذف‌شده', 'is_active' => false]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('محصول فروشگاهی دوام')
            ->assertSee('آبی دوام')
            ->assertSee(number_format(300000));
    }

    public function test_the_customer_order_history_index_survives_catalog_deletion(): void
    {
        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->fuelPayload());

        $this->deleteDesignAndItsImage();

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->reference);
    }

    public function test_another_customer_cannot_access_a_historical_order(): void
    {
        $owner = $this->customer();
        $order = $this->placePaidOrder($owner, $this->fuelPayload());

        $this->deleteDesignAndItsImage();

        $this->actingAs($this->customer())
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_a_guest_cannot_access_a_customers_historical_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        $this->deleteDesignAndItsImage();

        // The route is authenticated, so a guest is bounced to login and must
        // learn nothing about the order's existence or contents.
        $response = $this->get(route('orders.show', $order));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasNoErrors();
        $this->assertStringNotContainsString($order->reference, $response->getContent() ?: '');
        $this->assertStringNotContainsString(
            'NAAAAAAAAAAAAAAA1',
            $response->getContent() ?: '',
        );
    }

    // =========================================================================
    // PHASE 5 / 6 — PHYSICAL ASSET DURABILITY AND REPLACEMENT SAFETY
    // =========================================================================

    public function test_a_shared_design_image_file_is_not_deleted_when_only_one_row_goes(): void
    {
        Storage::fake('public');

        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        // A second DesignImage row points at the very same physical file. It is
        // registered as an allowed active sibling so the pre-existing
        // "last active image" purchaseability guard does not mask the
        // file-durability behaviour under test.
        $sharedPath = $this->designImage->image_path;
        Storage::disk('public')->put($sharedPath, 'shared-bytes');
        $twin = $this->createReplaceableImageFor($this->design, 'twin');
        $twin->update(['image_path' => $sharedPath]);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $this->designImage->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('design_images', ['id' => $this->designImage->id]);
        $this->assertNotNull(DesignImage::find($twin->id), 'The twin row must survive.');

        // A file still referenced by another design image row must not be
        // unlinked, so the paid order keeps the asset it was bought against.
        Storage::disk('public')->assertExists($sharedPath);

        $this->assertNotNull($order->items()->sole()->fresh());
    }

    // =========================================================================
    // PHASE 6 — FILE REPLACEMENT SAFETY
    // =========================================================================

    public function test_replacing_a_design_image_bought_by_a_paid_order_keeps_the_old_file(): void
    {
        Storage::fake('public');

        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        $oldPath = $this->designImage->image_path;
        Storage::disk('public')->put($oldPath, 'old-design-bytes');

        $newPath = 'designs/onyx46-replacement-'.uniqid().'.png';
        Storage::disk('public')->put($newPath, 'new-design-bytes');

        $this->replaceDesignImagePath($newPath);

        // The catalog row now points at the new file...
        $this->assertSame($newPath, $this->designImage->fresh()->image_path);

        // ...but the paid order still depends on the old physical file, so the
        // replacement cleanup must not have unlinked it.
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($newPath);
        $this->assertSame($oldPath, $order->items()->sole()->fresh()->design_image_path_snapshot);
    }

    public function test_replacing_a_design_image_no_order_ever_bought_still_frees_the_old_file(): void
    {
        Storage::fake('public');

        [$design, $bought] = $this->createDesignAsset('replacement');
        $replaced = $this->createImageFor($design, 'replaced');
        $replacedPath = $replaced->image_path;
        Storage::disk('public')->put($replacedPath, 'orphan-candidate');

        $newPath = 'designs/onyx46-fresh-'.uniqid().'.png';
        Storage::disk('public')->put($newPath, 'new-bytes');

        $this->replaceDesignImagePath($newPath, $replaced->id);

        // Prove the replacement actually happened before judging cleanup, so a
        // silent no-op can never pass this test.
        $this->assertSame($newPath, $replaced->fresh()->image_path);

        // Nothing historical points at the replaced file, so the existing
        // lifecycle must still clean it up rather than leak it forever.
        Storage::disk('public')->assertMissing($replacedPath);
        Storage::disk('public')->assertExists($newPath);
        $this->assertNotNull($bought->fresh());
    }

    public function test_deleting_a_product_image_after_a_paid_order_leaves_the_order_intact(): void
    {
        Storage::fake('public');

        // Store products are ordinary commerce: their gallery is catalog
        // merchandising only. There is no order_items product image column and
        // no order surface renders one, so a paid order must not depend on it.
        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->storePayload(2));
        $before = $order->items()->sole()->fresh();

        $galleryPath = 'store/gallery-'.uniqid().'.png';
        Storage::disk('public')->put($galleryPath, 'gallery-bytes');

        $gallery = ProductImage::create([
            'product_id' => $this->storeProduct->id,
            'color_id' => $this->color->id,
            'image_path' => $galleryPath,
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $this->storeProduct->id)
            ->call('deleteVariantImage', $gallery->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('product_images', ['id' => $gallery->id]);

        $after = $order->items()->sole()->fresh();

        $this->assertSame($before->product_name_snapshot, $after->product_name_snapshot);
        $this->assertSame($before->color_name_snapshot, $after->color_name_snapshot);
        $this->assertSame((int) $before->unit_price_snapshot, (int) $after->unit_price_snapshot);
        $this->assertSame((int) $before->final_price, (int) $after->final_price);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('محصول فروشگاهی دوام');
    }

    public function test_promoting_another_product_image_keeps_the_paid_order_intact(): void
    {
        Storage::fake('public');

        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->storePayload(1));

        $first = ProductImage::create([
            'product_id' => $this->storeProduct->id,
            'color_id' => $this->color->id,
            'image_path' => 'store/primary-'.uniqid().'.png',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $second = ProductImage::create([
            'product_id' => $this->storeProduct->id,
            'color_id' => $this->color->id,
            'image_path' => 'store/secondary-'.uniqid().'.png',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $this->storeProduct->id)
            ->call('setPrimaryVariantImage', $second->id)
            ->assertHasNoErrors();

        $this->assertFalse((bool) $first->fresh()->is_primary);
        $this->assertTrue((bool) $second->fresh()->is_primary);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('محصول فروشگاهی دوام');
    }

    public function test_deleting_a_design_color_compatibility_does_not_change_a_paid_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->bankPayload());
        $before = $order->items()->sole()->fresh();

        // Compatibility is pure catalog configuration: it is never snapshotted
        // onto an order, so removing it cannot rewrite a historical purchase.
        $this->assertDatabaseHas('design_color_compatibilities', [
            'design_image_id' => $this->designImage->id,
            'card_color_id' => $this->color->id,
        ]);

        DesignColorCompatibility::query()
            ->where('design_image_id', $this->designImage->id)
            ->where('card_color_id', $this->color->id)
            ->delete();

        $after = $order->items()->sole()->fresh();

        $this->assertSame($before->product_name_snapshot, $after->product_name_snapshot);
        $this->assertSame($before->color_name_snapshot, $after->color_name_snapshot);
        $this->assertSame($before->design_name_snapshot, $after->design_name_snapshot);
        $this->assertSame($before->design_image_path_snapshot, $after->design_image_path_snapshot);
        $this->assertSame($before->customization_json, $after->customization_json);
    }

    public function test_a_paid_orders_design_file_survives_deactivation_and_deletion_of_the_row(): void
    {
        Storage::fake('public');

        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());
        $path = $this->designImage->image_path;
        Storage::disk('public')->put($path, 'design-bytes');

        $this->createReplaceableImageFor($this->design, 'sibling');

        $this->designImage->update(['is_active' => false]);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $this->designImage->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('design_images', ['id' => $this->designImage->id]);
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $order->items()->sole()->fresh()->design_image_path_snapshot);
    }

    public function test_deleting_a_design_image_that_no_order_ever_bought_still_frees_the_file(): void
    {
        Storage::fake('public');

        [$design, $keepImage] = $this->createDesignAsset('cleanup');
        $doomed = $this->createImageFor($design, 'cleanup');
        Storage::disk('public')->put($doomed->image_path, 'bytes');

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('delete', $doomed->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('design_images', ['id' => $doomed->id]);
        Storage::disk('public')->assertMissing($doomed->image_path);
    }

    public function test_the_order_aware_file_reference_check_is_backed_by_an_index(): void
    {
        // The guard runs on every design image delete/replace. Without an index
        // on design_image_path_snapshot it degrades into a full scan of the
        // fastest-growing table in the system.
        $indexes = collect(Schema::getIndexes('order_items'))
            ->flatMap(fn (array $index): array => $index['columns'] ?? [])
            ->all();

        $this->assertTrue(
            in_array('design_image_path_snapshot', $indexes, true),
            'order_items.design_image_path_snapshot must be indexed so the file reference guard is not a full table scan.',
        );
    }

    public function test_the_file_reference_check_treats_paths_exactly(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());
        $path = $order->items()->sole()->design_image_path_snapshot;

        $manager = app(StoredFileManager::class);

        $this->assertTrue($manager->isDesignImageFileReferenced($path));
        $this->assertFalse($manager->isDesignImageFileReferenced($path.'.other'));
        $this->assertFalse($manager->isDesignImageFileReferenced('designs/other-'.uniqid().'.png'));
    }

    public function test_deleting_catalog_assets_never_breaks_a_paid_order_page(): void
    {
        Storage::fake('public');

        $customer = $this->customer();
        $order = $this->placePaidOrder($customer, $this->fuelPayload());

        // Remove every catalog row the order pointed at, and the file the order
        // depends on, then confirm the order is still fully readable.
        $this->deleteDesignAndItsImage();
        $this->fuelProduct->update(['is_active' => false]);
        $this->color->update(['is_active' => false]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk();

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertOk();
    }

    // =========================================================================
    // PHASE 2 / 4 — THE STATE MATRIX, EXECUTED
    // =========================================================================

    /**
     * The lifecycle points the domain actually recognises, derived from the
     * existing OrderStateMachine transitions and PaymentStatusEnum. No new
     * status is invented here.
     *
     * A paid order is deliberately absent from the cancelled rows: the state
     * machine refuses that transition and requires a refund instead, which
     * test_the_state_machine_refuses_to_cancel_a_paid_order asserts.
     */
    public static function lifecycleStates(): array
    {
        return [
            'order created, payment pending' => [OrderStatusEnum::PENDING, PaymentStatusEnum::UNPAID],
            'payment successful' => [OrderStatusEnum::PENDING, PaymentStatusEnum::PAID],
            'order confirmed' => [OrderStatusEnum::CONFIRMED, PaymentStatusEnum::PAID],
            'order processing' => [OrderStatusEnum::PROCESSING, PaymentStatusEnum::PAID],
            'order completed' => [OrderStatusEnum::COMPLETED, PaymentStatusEnum::PAID],
            'order refunded' => [OrderStatusEnum::COMPLETED, PaymentStatusEnum::REFUNDED],
            'cancelled before payment' => [OrderStatusEnum::CANCELLED, PaymentStatusEnum::UNPAID],
        ];
    }

    #[DataProvider('lifecycleStates')]
    public function test_an_order_stays_reconstructable_in_every_lifecycle_state(
        OrderStatusEnum $status,
        PaymentStatusEnum $paymentStatus,
    ): void {
        Storage::fake('public');

        $customer = $this->customer();
        $order = $this->placeDraftOrder($customer, $this->fuelPayload());

        $path = $this->designImage->image_path;
        Storage::disk('public')->put($path, 'lifecycle-bytes');

        $order = $this->driveOrderTo($order, $status, $paymentStatus);
        $this->assertSame($status, $order->status);
        $this->assertSame($paymentStatus, $order->payment_status);

        // Every destructive catalog operation, applied while the order is in
        // this lifecycle state.
        $this->designImage->delete();
        $this->design->delete();
        $this->color->update(['is_active' => false, 'name' => 'حذف‌شده']);
        $this->fuelProduct->update(['is_active' => false, 'name' => 'حذف‌شده']);

        $item = $order->fresh()->items()->sole();

        // Structured history is intact.
        $this->assertSame('کارت سوخت دوام', $item->product_name_snapshot);
        $this->assertSame('آبی دوام', $item->color_name_snapshot);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame(480000, (int) $item->unit_price_snapshot);
        $this->assertSame(480000, (int) $item->final_price);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);

        // The purchased physical asset is still there.
        Storage::disk('public')->assertExists($path);

        // Both read models still render it.
        $this->actingAs($customer)
            ->get(route('orders.show', $order->fresh()))
            ->assertOk()
            ->assertSee('طرح دوام تست')
            ->assertSee('NAAAAAAAAAAAAAAA1');

        Livewire::actingAs($this->admin())
            ->test(OrderManager::class)
            ->set('selectedOrderId', $order->id)
            ->assertSee('طرح دوام تست')
            ->assertSee('NAAAAAAAAAAAAAAA1');
    }

    public function test_the_state_machine_refuses_to_cancel_a_paid_order(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        // Cancellation must never erase a financial event: a paid order has to
        // go through the refund process, which is what keeps the audit trail.
        try {
            app(OrderStateMachine::class)->transition($order, OrderStatusEnum::CANCELLED);
            $this->fail('Expected the state machine to refuse cancelling a paid order.');
        } catch (OrderLifecycleConstraintException) {
            // Expected.
        }

        $order->refresh();

        $this->assertNotSame(OrderStatusEnum::CANCELLED, $order->status);
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
    }

    public function test_a_refunded_order_keeps_its_audit_trail_through_the_real_refund_path(): void
    {
        Storage::fake('public');

        $order = $this->placeDraftOrder($this->customer(), $this->fuelPayload());

        $order = $this->driveOrderTo(
            $order,
            OrderStatusEnum::COMPLETED,
            PaymentStatusEnum::PAID,
        );

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => PaymentMethod::MANUAL_TRANSFER,
            'status' => PaymentStatus::SUCCESS,
            'amount' => (int) $order->total_price,
            'paid_amount' => (int) $order->total_price,
            'paid_at' => now(),
        ]);

        app(ManualRefundService::class)->refund($payment, (int) $order->total_price);

        $order->refresh();

        $this->assertSame(PaymentStatusEnum::REFUNDED, $order->payment_status);

        // The money moved back, but the production record must remain complete.
        $this->deleteDesignAndItsImage();

        $item = $order->items()->sole();

        $this->assertSame('کارت سوخت دوام', $item->product_name_snapshot);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);
        $this->assertSame((int) $order->total_price, (int) $item->final_price);
    }

    public function test_a_refunded_order_keeps_its_audit_trail(): void
    {
        Storage::fake('public');

        $order = $this->placeDraftOrder($this->customer(), $this->fuelPayload());
        $order = $this->driveOrderTo(
            $order,
            OrderStatusEnum::COMPLETED,
            PaymentStatusEnum::REFUNDED,
        );

        $this->deleteDesignAndItsImage();

        // A refunded order is exactly the record an auditor needs, so the
        // snapshot must still explain what was sold and produced.
        $item = $order->fresh()->items()->sole();

        $this->assertSame('کارت سوخت دوام', $item->product_name_snapshot);
        $this->assertSame('طرح دوام تست', $item->design_name_snapshot);
        $this->assertSame('NAAAAAAAAAAAAAAA1', $item->customization_json['vin']);
    }

    // =========================================================================
    // PHASE 9 — ADMIN AUTHORIZATION MUST NOT BE WEAKENED
    // =========================================================================

    public function test_destructive_catalog_actions_remain_admin_only(): void
    {
        $customer = $this->customer();

        Livewire::actingAs($customer)
            ->test(DesignManager::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(DesignImageManager::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(ProductManager::class)
            ->assertForbidden();

        Livewire::actingAs($customer)
            ->test(ColorManager::class)
            ->assertForbidden();

        $this->assertNotNull(Design::find($this->design->id));
        $this->assertNotNull(DesignImage::find($this->designImage->id));
    }

    public function test_a_customer_cannot_open_the_admin_order_detail_surface(): void
    {
        $order = $this->placePaidOrder($this->customer(), $this->fuelPayload());

        Livewire::actingAs($this->customer())
            ->test(OrderManager::class)
            ->assertForbidden();

        $this->assertNotNull($order->fresh());
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function customer(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'customer'])->save();

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin'])->save();

        return $user;
    }

    private function createProduct(
        ProductTypeEnum $type,
        ?CustomizationWorkflowEnum $workflow,
        string $name,
        int $basePrice,
        int $colorPrice,
    ): Product {
        $product = Product::create([
            'type' => $type->value,
            'customization_workflow' => $workflow?->value,
            'name' => $name,
            'slug' => 'onyx46-'.$type->value.'-'.uniqid(),
            'base_price' => $basePrice,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $this->color->id,
            'price' => $colorPrice,
            'is_active' => true,
        ]);

        return $product;
    }

    private function createDesignAsset(string $prefix): array
    {
        $design = Design::create([
            'cate_design_id' => $this->category->id,
            'name' => 'طرح دوام تست',
            'slug' => $prefix.'-design-'.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $image = $this->createImageFor($design, $prefix);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        return [$design, $image];
    }

    private function createImageFor(Design $design, string $prefix): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $this->color->id,
            'image_path' => 'designs/'.$prefix.'-'.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * A second active image that is itself allowed for the color, so a sibling
     * remains a purchasable option and the pre-existing purchaseability guards
     * do not mask the file-durability behaviour under test.
     */
    private function createReplaceableImageFor(Design $design, string $prefix): DesignImage
    {
        $image = $this->createImageFor($design, $prefix);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $this->color->id,
            'is_allowed' => true,
        ]);

        return $image;
    }

    private function storePayload(int $quantity = 1): array
    {
        return [
            'product_id' => $this->storeProduct->id,
            'color_id' => $this->color->id,
            'design_id' => null,
            'design_image_id' => null,
            'quantity' => $quantity,
            'customization_json' => [],
        ];
    }

    private function bankPayload(): array
    {
        return [
            'product_id' => $this->bankProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => [
                'card_holder_name' => 'علی رضایی',
                'card_number' => '6274051234567890',
            ],
        ];
    }

    private function fuelPayload(array $overrides = []): array
    {
        return [
            'product_id' => $this->fuelProduct->id,
            'color_id' => $this->color->id,
            'design_id' => $this->design->id,
            'design_image_id' => $this->designImage->id,
            'quantity' => 1,
            'customization_json' => array_merge([
                'owner_name' => 'حسین',
                'car_info' => 'پژو ۲۰۶',
                'vin' => 'NAAAAAAAAAAAAAAA1',
                'system_name' => 'کارت سوخت',
                'system_identifier' => 'SYS-A',
                'plate_number' => '11A111',
                'chip_info' => 'small',
            ], $overrides),
        ];
    }

    /**
     * Go through a real server-authoritative checkout so the snapshot is
     * produced by the application, not hand-written by the test.
     */
    private function placeDraftOrder(User $user, array $itemPayload): Order
    {
        app(CartService::class)->addItem($itemPayload);

        return app(CartService::class)->createDraftOrder([
            'customer_name' => 'حسین',
            'customer_phone' => '09120000000',
        ], $user->id);
    }

    private function placePaidOrder(User $user, array $itemPayload): Order
    {
        $order = $this->placeDraftOrder($user, $itemPayload);

        // status/payment_status are guarded on Order, so they are assigned
        // directly rather than mass-assigned (a silent no-op otherwise).
        $order->status = OrderStatusEnum::CONFIRMED;
        $order->payment_status = PaymentStatusEnum::PAID;
        $order->save();

        return $order->fresh();
    }

    /**
     * Move an order to a lifecycle state using only the real
     * OrderStateMachine transitions, so the test can never assert against an
     * unreachable or invented status.
     */
    private function driveOrderTo(
        Order $order,
        OrderStatusEnum $target,
        PaymentStatusEnum $paymentStatus,
    ): Order {
        $stateMachine = app(OrderStateMachine::class);

        // The state machine refuses to complete an unpaid order, so a paid
        // lifecycle is marked paid first. payment_status is guarded, so it is
        // assigned directly rather than mass-assigned.
        if ($paymentStatus !== PaymentStatusEnum::UNPAID) {
            $order->payment_status = PaymentStatusEnum::PAID;
            $order->save();
        }

        foreach ($this->legalRoute($order->status, $target) as $step) {
            $stateMachine->transition($order, $step);
        }

        $order->payment_status = $paymentStatus;
        $order->save();

        return $order->fresh();
    }

    /**
     * The forward path from one status to another using only the transitions
     * OrderStateMachine already allows, so the test never asserts an
     * unreachable or invented state.
     *
     * @return list<OrderStatusEnum>
     */
    private function legalRoute(OrderStatusEnum $from, OrderStatusEnum $to): array
    {
        if ($from === $to) {
            return [];
        }

        // Cancellation is reachable from every non-terminal state.
        if ($to === OrderStatusEnum::CANCELLED) {
            return [OrderStatusEnum::CANCELLED];
        }

        $forward = [
            OrderStatusEnum::CONFIRMED,
            OrderStatusEnum::PROCESSING,
            OrderStatusEnum::COMPLETED,
        ];

        $fromIndex = array_search($from, $forward, true);

        // PENDING sits just before CONFIRMED.
        $fromIndex = $fromIndex === false ? -1 : $fromIndex;
        $toIndex = (int) array_search($to, $forward, true);

        return array_values(array_slice($forward, $fromIndex + 1, $toIndex - $fromIndex));
    }

    /**
     * Drive the real DesignWizard replacement path: the row is repointed at a
     * new path and the wizard runs its own post-update cleanup of the file the
     * edit replaced.
     */
    private function replaceDesignImagePath(string $newPath, ?int $imageId = null): void
    {
        $image = DesignImage::findOrFail($imageId ?? $this->designImage->id);

        Livewire::actingAs($this->admin())
            ->test(DesignWizard::class)
            ->set('designId', $image->design_id)
            ->set('editingImageId', $image->id)
            ->set('colorId', $image->color_id)
            ->set('imagePath', $newPath)
            ->set('imageIsActive', true)
            ->set('imageSortOrder', 1)
            ->call('saveImage')
            ->assertHasNoErrors()
            ->assertSet('editingImageId', null);
    }

    /**
     * Permitted catalog teardown: the design may only go once its images are
     * gone, which is exactly the path a paid order has to survive.
     */
    private function deleteDesignAndItsImage(): void
    {
        DB::transaction(function (): void {
            $this->designImage->delete();
            $this->design->delete();
        });
    }
}
