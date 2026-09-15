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
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductManagerOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function category(string $suffix = ''): CateDesign
    {
        return CateDesign::create([
            'name' => 'دسته'.$suffix,
            'slug' => 'ops-cat-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function color(string $name = 'مشکی'): Color
    {
        return Color::create([
            'name' => $name,
            'code_hex' => '#111111',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function design(CateDesign $category, string $suffix = ''): Design
    {
        return Design::create([
            'cate_design_id' => $category->id,
            'name' => 'طرح'.$suffix,
            'slug' => 'ops-design-'.$suffix.uniqid(),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function image(Design $design, Color $color, string $suffix = ''): DesignImage
    {
        return DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/ops-'.$suffix.uniqid().'.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function compatibility(DesignImage $image, Color $color): DesignColorCompatibility
    {
        return DesignColorCompatibility::create([
            'design_image_id' => $image->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);
    }

    private function price(Product $product, Color $color, int $value, bool $isActive = true): ProductColorPrice
    {
        return ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => $value,
            'is_active' => $isActive,
        ]);
    }

    private function product(string $type, ?string $workflow, bool $isActive = false, ?int $basePrice = null, string $name = 'محصول عملیات'): Product
    {
        return Product::create([
            'type' => $type,
            'customization_workflow' => $workflow,
            'name' => $name,
            'slug' => 'ops-product-'.$type.($workflow ?? 'none').uniqid(),
            'base_price' => $basePrice,
            'is_active' => $isActive,
        ]);
    }

    private function readyBankProduct(bool $isActive = true): array
    {
        $category = $this->category('بانک');
        $color = $this->color('مشکی بانک');
        $design = $this->design($category, 'بانک');
        $image = $this->image($design, $color, 'بانک');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, $isActive);
        $this->price($product, $color, 700000);

        return [$product, $color, $design, $category, $image];
    }

    private function readyFuelProduct(bool $isActive = true): array
    {
        $category = $this->category('سوخت');
        $color = $this->color('مشکی سوخت');
        $design = $this->design($category, 'سوخت');
        $image = $this->image($design, $color, 'سوخت');
        $this->compatibility($image, $color);
        $product = $this->product(ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::FUEL_CARD->value, $isActive);
        $this->price($product, $color, 480000);

        return [$product, $color, $design, $category, $image];
    }

    public function test_type_filter_narrows_the_product_list(): void
    {
        $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, false, null, 'کارت بانکی فیلتر');
        $this->product(ProductTypeEnum::STANDARD->value, null, false, null, 'کالای استاندارد فیلتر');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('typeFilter', ProductTypeEnum::STANDARD->value)
            ->assertSee('کالای استاندارد فیلتر')
            ->assertDontSee('کارت بانکی فیلتر');
    }

    public function test_workflow_filter_narrows_the_product_list(): void
    {
        $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, false, null, 'کارت بانکی معمولی');
        $this->product(ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::FUEL_CARD->value, false, null, 'کارت سوخت فیلتر');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('workflowFilter', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->assertSee('کارت سوخت فیلتر')
            ->assertDontSee('کارت بانکی معمولی');
    }

    public function test_workflow_filter_none_shows_only_products_without_customization(): void
    {
        $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, false, null, 'کارت شخصی‌سازی‌شده');
        $this->product(ProductTypeEnum::STANDARD->value, null, false, null, 'کالای بدون شخصی‌سازی');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('workflowFilter', 'none')
            ->assertSee('کالای بدون شخصی‌سازی')
            ->assertDontSee('کارت شخصی‌سازی‌شده');
    }

    public function test_fully_ready_bank_and_fuel_products_are_marked_purchasable(): void
    {
        $this->readyBankProduct();
        $this->readyFuelProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertSee('text-green-600">قابل فروش', false);
    }

    public function test_active_product_without_purchasable_path_is_marked_not_purchasable(): void
    {
        $this->product(ProductTypeEnum::STANDARD->value, null, true, 100000, 'کالای آماده فروش');
        $this->product(ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, true, null, 'کارت ناقص');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertSee('text-green-600">قابل فروش', false)
            ->assertSee('text-red-500">قابل فروش نیست', false);
    }

    public function test_inactive_product_does_not_render_a_purchase_badge(): void
    {
        $this->product(ProductTypeEnum::STANDARD->value, null, false, 100000, 'کالای غیرفعال');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertDontSee('قابل فروش');
    }

    public function test_admin_list_shows_the_minimum_active_color_price(): void
    {
        $product = $this->product(ProductTypeEnum::STANDARD->value, null, false, 500000, 'کالای دارای قیمت رنگ');
        $this->price($product, $this->color('طلایی'), 100000);
        $this->price($product, $this->color('نقره‌ای'), 250000);
        $this->price($product, $this->color('خاکستری'), 1000, false);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->assertSee('از 100,000 تومان')
            ->assertDontSee('از 1,000 تومان');
    }

    public function test_creating_product_with_uploaded_main_image_persists_the_file(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'کالای دارای تصویر')
            ->set('isActive', false)
            ->set('mainImageUpload', UploadedFile::fake()->image('card.png'))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertNotNull($product->main_image);
        $this->assertStringStartsWith('products/', $product->main_image);
        Storage::disk('public')->assertExists($product->main_image);
    }

    public function test_replacing_main_image_deletes_the_old_unreferenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/old.png', 'data');

        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کالای دارای تصویر قدیمی',
            'slug' => 'ops-image-replace-'.uniqid(),
            'base_price' => 100000,
            'main_image' => 'products/old.png',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', $product->name)
            ->set('isActive', false)
            ->set('mainImageUpload', UploadedFile::fake()->image('new.png'))
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertNotSame('products/old.png', $fresh->main_image);
        Storage::disk('public')->assertExists($fresh->main_image);
        Storage::disk('public')->assertMissing('products/old.png');
    }

    public function test_replacing_main_image_preserves_a_shared_referenced_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/shared.png', 'data');

        $first = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کالای اول',
            'slug' => 'ops-shared-first-'.uniqid(),
            'base_price' => 100000,
            'main_image' => 'products/shared.png',
            'is_active' => false,
        ]);

        $second = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'کالای دوم',
            'slug' => 'ops-shared-second-'.uniqid(),
            'base_price' => 100000,
            'main_image' => 'products/shared.png',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $first->id)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', $first->name)
            ->set('isActive', false)
            ->set('mainImageUpload', UploadedFile::fake()->image('replacement.png'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotSame('products/shared.png', $first->fresh()->main_image);
        $this->assertSame('products/shared.png', $second->fresh()->main_image);
        Storage::disk('public')->assertExists('products/shared.png');
        Storage::disk('public')->assertExists($first->fresh()->main_image);
    }

    public function test_creating_product_with_uploaded_og_image_persists_the_file(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', 'کالای دارای تصویر اواجی')
            ->set('isActive', false)
            ->set('ogImageUpload', UploadedFile::fake()->image('og.png'))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertNotNull($product->og_image);
        $this->assertStringStartsWith('products/', $product->og_image);
        Storage::disk('public')->assertExists($product->og_image);
    }

    public function test_deleting_a_product_with_order_items_is_blocked(): void
    {
        $product = $this->product(ProductTypeEnum::STANDARD->value, null, false, 100000, 'کالای دارای سفارش');

        $order = new Order;
        $order->customer_name = 'حسین';
        $order->customer_phone = '09120000000';
        $order->total_price = 100000;
        $order->save();

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => 100000,
            'quantity' => 1,
            'final_price' => 100000,
            'customization_json' => [],
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $product->id)
            ->assertSessionMissing('success');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id]);
    }
}
