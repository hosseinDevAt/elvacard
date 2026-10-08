<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\DesignImageLayoutModeEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\OrderManager;
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
use App\Services\CustomDesign\CustomDesignService;
use App\Services\FuelCard\FuelCardCustomization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class FuelCardDesignerEnhancementTest extends TestCase
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

    private function createColor(string $name = 'مشکی', string $hex = '#000000'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => $hex,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createProductWithWorkflow(CustomizationWorkflowEnum $workflow, int $price = 450000): array
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
            'name' => 'تست ' . uniqid(),
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

        $image = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/test-' . uniqid() . '.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return [$product, $color, $design, $image, $pcp];
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Requirement 1: Fuel custom upload rejected, Bank custom upload allowed.
     */
    public function test_fuel_custom_upload_is_strictly_rejected_in_ui_and_cart(): void
    {
        [$fuelProduct, $fuelColor] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        // UI rejects switching to custom
        Livewire::test(ProductCustomizer::class, ['productId' => $fuelProduct->id])
            ->assertDontSee('طرح اختصاصی شما')
            ->call('setDesignSource', 'custom')
            ->assertSet('designSource', 'catalog');

        // CartService rejects custom design for Fuel Card
        $service = app(CustomDesignService::class);
        $file = UploadedFile::fake()->image('custom.png', 800, 600);
        $stored = $service->validateAndStoreTemp($file, CustomizationWorkflowEnum::FUEL_CARD->value);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('امکان آپلود طرح اختصاصی برای کارت سوخت وجود ندارد.');

        app(CartService::class)->addItem([
            'product_id' => $fuelProduct->id,
            'color_id' => $fuelColor->id,
            'is_custom_design' => true,
            'custom_design_token' => $stored['token'],
            'layout_mode' => DesignImageLayoutModeEnum::CENTERED->value,
            'quantity' => 1,
            'customization_json' => [
                'owner_name' => 'علی رضایی',
                'phone_number' => '09123456789',
                'vehicle_type' => 'سواری شخصی',
                'chip_info' => 'small',
            ],
        ]);
    }

    public function test_bank_custom_upload_remains_allowed(): void
    {
        [$bankProduct] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::BANK_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $bankProduct->id])
            ->assertSee('طرح اختصاصی شما')
            ->call('setDesignSource', 'custom')
            ->assertSet('designSource', 'custom');
    }

    /**
     * Requirement 2: Validation of required and optional fields.
     */
    public function test_fuel_card_requires_owner_name(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', '')
            ->set('fuelCard.phone_number', '09123456789')
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.owner_name' => 'required']);
    }

    public function test_fuel_card_requires_valid_iranian_phone_number(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        // Empty phone
        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.phone_number', '')
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.phone_number']);

        // Invalid format (less than 11 digits or doesn't start with 09)
        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.phone_number', '02112345678')
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.phone_number' => 'regex']);
    }

    public function test_fuel_card_vehicle_type_strictly_accepts_only_six_values(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        // Invalid vehicle type
        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.phone_number', '09123456789')
            ->set('fuelCard.vehicle_type', 'هواپیمای شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.vehicle_type']);

        // All 6 valid vehicle types accepted
        foreach (FuelCardCustomization::VALID_VEHICLE_TYPES as $validType) {
            $cart = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
                ->set('fuelCard.owner_name', 'علی رضایی')
                ->set('fuelCard.phone_number', '09123456789')
                ->set('fuelCard.vehicle_type', $validType)
                ->set('fuelCard.chip_info', 'small')
                ->call('addToCart')
                ->assertHasNoErrors(['fuelCard.vehicle_type']);
        }
    }

    public function test_fuel_card_allows_empty_optional_fields(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.phone_number', '09123456789')
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.vehicle_model', '')
            ->set('fuelCard.oil_serial_number', '')
            ->set('fuelCard.plate_number', '')
            ->set('fuelCard.vin', '')
            ->set('fuelCard.chip_info', 'small')
            ->call('addToCart')
            ->assertHasNoErrors();
    }

    public function test_fuel_card_rejects_invalid_vin(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'علی رضایی')
            ->set('fuelCard.phone_number', '09123456789')
            ->set('fuelCard.vehicle_type', 'سواری شخصی')
            ->set('fuelCard.chip_info', 'small')
            ->set('fuelCard.vin', 'SHORT123')
            ->call('addToCart')
            ->assertHasErrors(['fuelCard.vin' => 'regex']);
    }

    public function test_fuel_card_structured_plate_and_chip_snapshot(): void
    {
        [$product, $color, $design, $image] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->set('fuelCard.owner_name', 'رضا اکبری')
            ->set('fuelCard.phone_number', '۰۹۱۲۳۴۵۶۷۸۹')
            ->set('fuelCard.vehicle_type', 'کامیونت عمومی')
            ->set('fuelCard.vehicle_model', 'ولوو اف اچ 12')
            ->set('fuelCard.oil_serial_number', '7744330')
            ->set('fuelCard.plate_part1', '۱۲')
            ->set('fuelCard.plate_letter', 'ج')
            ->set('fuelCard.plate_part2', '۳۴۵')
            ->set('fuelCard.plate_iran', '۱۱')
            ->set('fuelCard.vin', 'IRV12345678901234')
            ->set('fuelCard.chip_info', 'large')
            ->call('addToCart')
            ->assertHasNoErrors();

        $cart = app(CartService::class)->getCart();
        $this->assertCount(1, $cart['items']);
        $item = $cart['items'][0];
        $custom = $item['customization_json'];

        $this->assertSame('رضا اکبری', $custom['owner_name']);
        $this->assertSame('09123456789', $custom['phone_number']);
        $this->assertSame('کامیونت عمومی', $custom['vehicle_type']);
        $this->assertSame('ولوو اف اچ 12', $custom['vehicle_model']);
        $this->assertSame('7744330', $custom['oil_serial_number']);
        $this->assertSame('12 ج 345 ایران 11', $custom['plate_number']);
        $this->assertSame('IRV12345678901234', $custom['vin']);
        $this->assertSame('large', $custom['chip_info']);
    }

    public function test_admin_displays_canonical_fuel_fields_and_previews(): void
    {
        [$product, $color, $design, $image] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);
        $admin = $this->createAdmin();

        $order = new Order([
            'customer_name' => 'رضا اکبری',
            'customer_phone' => '09123456789',
            'shipping_address' => 'تهران',
            'shipping_postal_code' => '1234567890',
            'shipping_plaque' => '12',
        ]);
        $order->status = \App\Enums\OrderStatusEnum::PENDING;
        $order->payment_status = \App\Enums\PaymentStatusEnum::PAID;
        $order->total_price = 450000;
        $order->save();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD,
            'color_id' => $color->id,
            'design_id' => $design->id,
            'design_image_id' => $image->id,
            'quantity' => 1,
            'unit_price_snapshot' => 450000,
            'final_price' => 450000,
            'product_name_snapshot' => $product->name,
            'color_name_snapshot' => $color->name,
            'design_name_snapshot' => $design->name,
            'design_image_path_snapshot' => $image->image_path,
            'customization_json' => [
                'owner_name' => 'رضا اکبری',
                'phone_number' => '09123456789',
                'vehicle_type' => 'کامیونت عمومی',
                'vehicle_model' => 'ولوو اف اچ 12',
                'oil_serial_number' => '7744330',
                'plate_number' => '12 ج 345 ایران 11',
                'vin' => 'IRV12345678901234',
                'chip_info' => 'large',
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(OrderManager::class)
            ->call('viewOrder', $order->id)
            ->assertSee('رضا اکبری')
            ->assertSee('09123456789')
            ->assertSee('کامیونت عمومی')
            ->assertSee('ولوو اف اچ 12')
            ->assertSee('7744330')
            ->assertSee('IRV12345678901234')
            ->assertSee('بزرگ');
    }

    public function test_oil_serial_number_formatting(): void
    {
        $this->assertSame('7744330', FuelCardCustomization::formatOilSerialNumber('7744330'));
        $this->assertSame('', FuelCardCustomization::formatOilSerialNumber(null));
        $this->assertSame('', FuelCardCustomization::formatOilSerialNumber('   '));
    }

    public function test_oil_serial_number_placeholder_has_dots_but_no_extra_guide_text(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setStep', 2)
            ->assertSeeHtml('placeholder="7744330•••••"')
            ->assertDontSee('نقاط صرفاً نشان‌دهنده');
    }

    public function test_fuel_card_back_preview_has_no_black_strip_and_has_all_7_fixed_rows_always(): void
    {
        [$product] = $this->createProductWithWorkflow(CustomizationWorkflowEnum::FUEL_CARD);

        $test = Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->call('setStep', 2)
            ->assertSet('activeView', 'back')
            ->assertDontSeeHtml('bg-black/80 rounded-sm mb-1 opacity-90 shadow-inner')
            ->assertSeeHtml('h-full')
            ->assertSeeHtml('مشخصات کارت سوخت اختصاصی')
            // All 7 rows are present initially even when empty
            ->assertSee('پلاک ایران')
            ->assertSee('نام مالک')
            ->assertSee('نوع خودرو')
            ->assertSee('مدل خودرو')
            ->assertSee('سریال نفت')
            ->assertSee('شماره تلفن')
            ->assertSee('شماره شاسی')
            ->assertSee('—');

        // When oil_serial_number is entered, preview displays ONLY actual entered value (no trailing dots)
        $test->set('fuelCard.oil_serial_number', '7744330')
            ->assertSee('7744330')
            ->assertDontSee('7744330 •••••');

        // When field is cleared, row remains and placeholder dash returns
        $test->set('fuelCard.oil_serial_number', '')
            ->assertSee('سریال نفت')
            ->assertSee('—');
    }
}

