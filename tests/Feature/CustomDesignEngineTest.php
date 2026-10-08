<?php

namespace Tests\Feature;

use App\Console\Commands\PruneCustomDesignsCommand;
use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignImageLayoutModeEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use App\Services\CartService;
use App\Services\CustomDesign\CustomDesignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class CustomDesignEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        $livewireTmp = storage_path('app/public/livewire-tmp');
        if (File::exists($livewireTmp)) {
            File::cleanDirectory($livewireTmp);
        }

        parent::tearDown();
    }

    private function createAdminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createColor(string $name = 'طلایی', string $hex = '#FFD700'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => $hex,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createProductWithWorkflow(CustomizationWorkflowEnum $workflow, int $price = 350000): array
    {
        $color = $this->createColor('مشکی مات', '#111111');

        $product = Product::create([
            'name' => 'کارت ' . $workflow->value,
            'slug' => 'card-' . strtolower($workflow->value) . '-' . uniqid(),
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => $workflow->value,
            'base_price' => $price,
            'is_active' => true,
        ]);

        $pcp = ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $price,
            'is_active' => true,
        ]);

        $category = CateDesign::create([
            'name' => 'تست',
            'slug' => 'test-cat-' . uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $design = Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح تست',
            'slug' => 'test-design-' . uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/test.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return [$product, $color, $pcp];
    }

    private function createValidImageFile(int $width = 600, int $height = 400, string $name = 'design.png', string $mime = 'image/png'): UploadedFile
    {
        return UploadedFile::fake()->image($name, $width, $height);
    }

    public function test_custom_design_service_accepts_valid_image_and_stores_in_private_disk(): void
    {
        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'my-logo.png');

        $result = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertEquals(CustomizationWorkflowEnum::BANK_CARD->value, $result['workflow']);
        $this->assertEquals(800, $result['width']);
        $this->assertEquals(600, $result['height']);

        // Verify storage on private disk
        Storage::disk('local')->assertExists($result['path']);

        // Verify session binding
        $this->assertNotNull(session('custom_designs.' . $result['token']));
        $this->assertEquals($result['token'], session('custom_designs.' . $result['token'])['token']);
    }

    public function test_custom_design_service_strictly_rejects_svg_files(): void
    {
        $service = app(CustomDesignService::class);

        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><circle cx="200" cy="200" r="100"/></svg>';
        $tempPath = tempnam(sys_get_temp_dir(), 'svg_test_');
        file_put_contents($tempPath, $svgContent);

        $file = new UploadedFile($tempPath, 'malicious.svg', 'image/svg+xml', null, true);

        $this->expectException(ValidationException::class);
        $service->validateAndStoreTemp($file, 'BANK_CARD');
    }

    public function test_custom_design_service_rejects_svg_disguised_as_png(): void
    {
        $service = app(CustomDesignService::class);

        // Disguised file with SVG content but .png extension
        $svgContent = '<?xml version="1.0"?><svg width="500" height="500"><text>xss</text></svg>';
        $tempPath = tempnam(sys_get_temp_dir(), 'fake_png_');
        file_put_contents($tempPath, $svgContent);

        $file = new UploadedFile($tempPath, 'fake.png', 'image/png', null, true);

        $this->expectException(ValidationException::class);
        $service->validateAndStoreTemp($file, 'BANK_CARD');
    }

    public function test_custom_design_service_rejects_images_smaller_than_min_dimensions(): void
    {
        $service = app(CustomDesignService::class);
        $smallFile = UploadedFile::fake()->image('too_small.png', 200, 200);

        $this->expectException(ValidationException::class);
        $service->validateAndStoreTemp($smallFile, 'BANK_CARD');
    }

    public function test_custom_design_service_rejects_images_exceeding_max_file_size(): void
    {
        $service = app(CustomDesignService::class);
        // Create 6MB image (max is 5MB)
        $largeFile = UploadedFile::fake()->image('too_large.png', 800, 800)->size(6000);

        $this->expectException(ValidationException::class);
        $service->validateAndStoreTemp($largeFile, 'BANK_CARD');
    }

    public function test_customer_can_preview_their_own_session_bound_custom_design(): void
    {
        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(600, 600, 'avatar.png');
        $stored = $service->validateAndStoreTemp($file, 'BANK_CARD');

        $response = $this->get(route('custom-design.preview', ['token' => $stored['token']]));
        $response->assertOk();
    }

    public function test_another_session_or_unauthorized_token_cannot_preview_custom_design(): void
    {
        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(600, 600, 'private.png');
        $stored = $service->validateAndStoreTemp($file, 'BANK_CARD');

        // Clear session to simulate another guest/visitor
        session()->flush();

        $response = $this->get(route('custom-design.preview', ['token' => $stored['token']]));
        $response->assertNotFound();
    }

    public function test_cart_service_adds_custom_design_item_for_bank_card(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 400000);

        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'custom-bank.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $cartService = app(CartService::class);

        $cart = $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => DesignImageLayoutModeEnum::FULL_CARD->value,
            'quantity' => 2,
            'card_number' => '6037991812345678',
            'card_holder_name' => 'Ali Rezaei',
        ]);

        $this->assertCount(1, $cart['items']);
        $item = $cart['items'][0];

        $this->assertEquals($product->id, $item['product_id']);
        $this->assertNull($item['design_id']);
        $this->assertEquals('طرح اختصاصی مشتری', $item['design_name_snapshot']);
        $this->assertTrue($item['is_custom_design']);
        $this->assertEquals($stored['token'], $item['custom_design_token']);
        $this->assertEquals('full_card', $item['layout_mode_snapshot']);
        $this->assertEquals(400000, $item['unit_price_snapshot']);
        $this->assertEquals(800000, $item['final_price']);
    }

    public function test_cart_service_adds_custom_design_item_for_fuel_card(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD, 500000);

        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'custom-fuel.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::FUEL_CARD->value);

        $cartService = app(CartService::class);

        $cart = $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => DesignImageLayoutModeEnum::CENTERED->value,
            'quantity' => 1,
            'customization_json' => [
                'vin' => '12345678901234567',
                'plate_number' => '12A345-67',
                'chip_info' => 'small',
            ],
        ]);

        $this->assertCount(1, $cart['items']);
        $item = $cart['items'][0];

        $this->assertEquals($product->id, $item['product_id']);
        $this->assertNull($item['design_id']);
        $this->assertEquals('centered', $item['layout_mode_snapshot']);
        $this->assertEquals(500000, $item['final_price']);
    }

    public function test_cart_service_rejects_mismatched_workflow_custom_design(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        // Upload custom design tagged as BANK_CARD
        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'custom.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $cartService = app(CartService::class);

        $this->expectException(InvalidArgumentException::class);
        $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'customization_json' => [
                'chip_info' => 'small',
            ],
        ]);
    }

    public function test_order_creation_snapshots_custom_design_immutably_to_private_orders_dir(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'client-brand.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $cartService = app(CartService::class);
        $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => DesignImageLayoutModeEnum::FULL_CARD->value,
            'quantity' => 1,
            'card_number' => '6037991812345678',
            'card_holder_name' => 'Saeed Moradi',
        ]);

        $customerData = [
            'customer_name' => 'سعید مرادی',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران، خیابان ولیعصر',
        ];

        $order = $cartService->createDraftOrder($customerData);

        $this->assertNotNull($order->id);
        $this->assertCount(1, $order->items);

        /** @var OrderItem $item */
        $item = $order->items->first();

        $this->assertNull($item->design_id);
        $this->assertEquals('طرح اختصاصی مشتری', $item->design_name_snapshot);
        $this->assertEquals('full_card', $item->layout_mode_snapshot);
        $this->assertTrue($item->isCustomDesign());

        // File path snapshot exists on private disk under orders directory
        $this->assertStringStartsWith('custom_designs/orders/', $item->design_image_path_snapshot);
        Storage::disk('local')->assertExists($item->design_image_path_snapshot);

        // Customization JSON stores custom design metadata
        $this->assertTrue($item->customization_json['custom_design']['is_custom_design']);
        $this->assertEquals('client-brand.png', $item->customization_json['custom_design']['original_name']);

        // Temp upload in session was cleaned up
        $this->assertNull(session('custom_designs.' . $stored['token']));
    }

    public function test_admin_can_view_and_download_order_custom_design(): void
    {
        $admin = $this->createAdminUser();

        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'logo.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $cartService = app(CartService::class);
        $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => 'centered',
            'quantity' => 1,
            'card_number' => '6037991812345678',
            'card_holder_name' => 'Admin Test',
        ]);

        $order = $cartService->createDraftOrder([
            'customer_name' => 'تست ادمین',
            'customer_phone' => '09121112233',
        ]);

        $item = $order->items->first();

        // 1. Unauthenticated request must be redirected or rejected
        $guestResponse = $this->get(route('admin.orders.items.custom-design', [
            'order' => $order->id,
            'item' => $item->id,
        ]));
        $this->assertTrue($guestResponse->isRedirect() || $guestResponse->isForbidden());

        // 2. Authenticated Admin view request
        $viewResponse = $this->actingAs($admin)->get(route('admin.orders.items.custom-design', [
            'order' => $order->id,
            'item' => $item->id,
        ]));
        $viewResponse->assertOk();

        // 3. Authenticated Admin download request
        $downloadResponse = $this->actingAs($admin)->get(route('admin.orders.items.custom-design', [
            'order' => $order->id,
            'item' => $item->id,
            'download' => 1,
        ]));
        $downloadResponse->assertOk();
    }

    public function test_prune_custom_designs_command_removes_old_temp_files(): void
    {
        $service = app(CustomDesignService::class);

        // Create an uploaded temp file
        $file = $this->createValidImageFile(600, 600, 'old.png');
        $stored = $service->validateAndStoreTemp($file, 'BANK_CARD');

        Storage::disk('local')->assertExists($stored['path']);

        // Travel 25 hours forward in time
        $this->travel(25)->hours();

        $this->artisan('custom-designs:prune', ['--hours' => 24])
            ->assertSuccessful();

        Storage::disk('local')->assertMissing($stored['path']);
    }

    public function test_product_customizer_livewire_component_handles_custom_design_flow(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        $fakeFile = $this->createValidImageFile(800, 600, 'my-card.png');

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertSet('designSource', 'catalog')
            ->call('setDesignSource', 'custom')
            ->assertSet('designSource', 'custom')
            ->set('customImage', $fakeFile);

        $this->assertNotNull($component->get('customDesignToken'));

        $component->call('setCustomLayoutMode', 'full_card')
            ->assertSet('customLayoutMode', 'full_card')
            ->set('bankCard.card_number', '6274051234567898')
            ->set('bankCard.card_holder_name', 'Ali Rezaei')
            ->call('addToCart')
            ->assertRedirect(route('cart.index'));

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $this->assertTrue($cart['items'][0]['is_custom_design']);
        $this->assertEquals('full_card', $cart['items'][0]['layout_mode_snapshot']);
    }

    public function test_product_customizer_blocks_advancing_to_step_2_without_custom_design_upload(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->assertSet('step', 1)
            ->call('setStep', 2)
            ->assertSet('step', 1)
            ->assertHasErrors(['customImage']);
    }

    public function test_product_customizer_remove_custom_image_cleans_up_storage(): void
    {
        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);
        $fakeFile = $this->createValidImageFile(800, 600, 'to-delete.png');

        $component = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->set('customImage', $fakeFile);

        $token = $component->get('customDesignToken');
        $this->assertNotNull($token);
        $this->assertNotNull(session("custom_designs.{$token}"));

        $component->call('removeCustomImage');

        $this->assertNull($component->get('customDesignToken'));
        $this->assertNull(session("custom_designs.{$token}"));
    }

    public function test_admin_custom_design_endpoint_rejects_item_from_another_order(): void
    {
        $admin = $this->createAdminUser();

        [$product, $color, $pcp] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);
        $service = app(CustomDesignService::class);
        $file = $this->createValidImageFile(800, 600, 'test.png');
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::BANK_CARD->value);

        $cartService = app(CartService::class);
        $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => 'centered',
            'quantity' => 1,
            'card_number' => '6274051234567898',
            'card_holder_name' => 'Order 1',
        ]);
        $order1 = $cartService->createDraftOrder(['customer_name' => 'سفارش ۱', 'customer_phone' => '09121111111']);
        $item1 = $order1->items->first();

        // Create a completely separate order 2
        $file2 = $this->createValidImageFile(800, 600, 'test2.png');
        $stored2 = $service->validateAndStoreTemp($file2, CustomizationWorkflowEnum::BANK_CARD->value);
        $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored2['token'],
            'layout_mode' => 'centered',
            'quantity' => 1,
            'card_number' => '6274051234567898',
            'card_holder_name' => 'Order 2',
        ]);
        $order2 = $cartService->createDraftOrder(['customer_name' => 'سفارش ۲', 'customer_phone' => '09122222222']);

        // Attempting to access item1 with order2 must fail 404
        $response = $this->actingAs($admin)->get(route('admin.orders.items.custom-design', [
            'order' => $order2->id,
            'item' => $item1->id,
        ]));

        $response->assertNotFound();
    }

    public function test_ui_renders_segmented_control_and_tabs_accessible_labels(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertSee('طرح‌های آماده')
            ->assertSee('طرح اختصاصی شما')
            ->assertSeeHtml('role="tablist"')
            ->assertSeeHtml('id="tab-catalog"')
            ->assertSeeHtml('id="tab-custom"')
            ->assertSeeHtml('id="panel-catalog"');
    }

    public function test_ui_custom_empty_state_shows_dropzone_and_help_text(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->assertSee('تصویر طرح خود را بارگذاری کنید')
            ->assertSee('کلیک کنید یا فایل را اینجا بکشید')
            ->assertSee('برای ظرافت بیشتر حکاکی، تصویر تک‌رنگ یا PNG با پس‌زمینه شفاف پیشنهاد می‌شود')
            ->assertDontSee('طرح استاندارد (مرکزی)')
            ->assertDontSee('پیش‌نمایش طرح اختصاصی');
    }

    public function test_ui_custom_upload_success_renders_file_card_and_layout_selector(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);
        $fakeFile = $this->createValidImageFile(800, 600, 'my-logo.png');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->set('customImage', $fakeFile)
            ->assertSee('my-logo.png')
            ->assertSee('تصویر تایید شده')
            ->assertSee('تغییر تصویر')
            ->assertSee('حذف تصویر')
            ->assertSee('طرح استاندارد (مرکزی)')
            ->assertSee('طرح سرتاسری (پوشش کامل)')
            ->assertSee('پیش‌نمایش طرح اختصاصی')
            ->assertDontSeeText('Centered')
            ->assertDontSeeText('Full Card')
            ->assertDontSeeText('centered')
            ->assertDontSeeText('full_card');
    }

    public function test_ui_step_navigation_preserves_state_between_steps(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD, 450000);
        $fakeFile = $this->createValidImageFile(800, 600, 'my-logo.png');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->set('customImage', $fakeFile)
            ->call('setCustomLayoutMode', 'full_card')
            ->call('setStep', 2)
            ->assertSet('step', 2)
            ->assertSet('activeView', 'back')
            ->assertSet('designSource', 'custom')
            ->assertSet('customLayoutMode', 'full_card')
            ->call('setStep', 1)
            ->assertSet('step', 1)
            ->assertSet('activeView', 'front')
            ->assertSet('designSource', 'custom')
            ->assertSet('customLayoutMode', 'full_card')
            ->assertSee('my-logo.png');
    }

    public function test_ui_fuel_card_custom_design_workspace_preserves_chip_and_specifications(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD, 500000);
        $fakeFile = $this->createValidImageFile(800, 600, 'fuel-logo.png');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setDesignSource', 'custom')
            ->set('customImage', $fakeFile)
            ->assertSee('پیش‌نمایش طرح اختصاصی')
            ->call('setStep', 2)
            ->assertSee('مشخصات کارت سوخت اختصاصی')
            ->assertSee('نام سامانه')
            ->assertSee('شناسه سامانه');
    }
}
