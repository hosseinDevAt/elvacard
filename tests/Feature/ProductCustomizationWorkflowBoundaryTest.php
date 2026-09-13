<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Catalog\ProductCustomizer;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCustomizationWorkflowBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(): CateDesign
    {
        return CateDesign::create([
            'name' => 'تست',
            'slug' => 'boundary-cat',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createColor(): Color
    {
        return Color::create([
            'name' => 'آبی',
            'code_hex' => '#0000FF',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function createCommerceProduct(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول بدون شخصی‌سازی',
            'slug' => 'commerce-only',
            'base_price' => 120000,
            'is_active' => true,
        ]);
    }

    private function createFuelProduct(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت تست',
            'slug' => 'fuel-wf-product',
            'base_price' => 450000,
            'is_active' => true,
        ]);
    }

    private function createDesign(Color $color): array
    {
        $design = Design::create([
            'cate_design_id' => $this->createCategory()->id,
            'name' => 'طرح تست',
            'slug' => 'boundary-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/boundary.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => true,
        ]);

        return ['design' => $design, 'designImage' => $designImage];
    }

    private function createForbiddenDesign(Color $color): array
    {
        $design = Design::create([
            'cate_design_id' => $this->createCategory()->id,
            'name' => 'طرح ممنوعه',
            'slug' => 'forbidden-design',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $designImage = DesignImage::create([
            'design_id' => $design->id,
            'color_id' => $color->id,
            'image_path' => 'designs/forbidden.png',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        DesignColorCompatibility::create([
            'design_image_id' => $designImage->id,
            'card_color_id' => $color->id,
            'is_allowed' => false,
        ]);

        return ['design' => $design, 'designImage' => $designImage];
    }

    private function createBankProduct(Color $color, Design $design): Product
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'customization_workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'name' => 'کارت بانکی تست',
            'slug' => 'bank-wf-product',
            'base_price' => 500000,
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 700000,
            'is_active' => true,
        ]);

        return $product;
    }

    private function bankAddPayload(Product $product, Color $color, Design $design, DesignImage $designImage): array
    {
        return [
            'product_id' => $product->id,
            'color_id' => $color->id,
            'design_id' => $design->id,
            'design_image_id' => $designImage->id,
            'quantity' => 1,
        ];
    }

    public function test_commerce_product_without_workflow_does_not_mount_bank_customizer(): void
    {
        $product = $this->createCommerceProduct();

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('افزودن به سبد خرید');
        $response->assertDontSee('فهرست طرح‌ها');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);
    }

    public function test_bank_workflow_product_mounts_customizer(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('فهرست طرح‌ها');
        $response->assertDontSee('افزودن به سبد خرید');
    }

    public function test_fuel_workflow_product_never_falls_back_to_bank_customizer(): void
    {
        $product = $this->createFuelProduct();

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('هنوز قابل خرید نیست');
        $response->assertDontSee('افزودن به سبد خرید');

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);
    }

    public function test_unknown_workflow_value_is_rejected_by_cart_and_customizer(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول قدیمی',
            'slug' => 'unknown-workflow',
            'is_active' => true,
        ]);

        DB::table('products')
            ->where('id', $product->id)
            ->update(['customization_workflow' => 'garbage']);

        $color = $this->createColor();
        $designData = $this->createDesign($color);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 100000,
            'is_active' => true,
        ]);

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported customization workflow');

        app(CartService::class)->addItem(
            $this->bankAddPayload($product, $color, $designData['design'], $designData['designImage'])
        );
    }

    public function test_commerce_product_can_be_added_without_design_or_color(): void
    {
        $product = $this->createCommerceProduct();
        $cartService = app(CartService::class);

        $cart = $cartService->addItem([
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $item = $cart['items'][0];
        $this->assertSame($product->id, $item['product_id']);
        $this->assertNull($item['color_id']);
        $this->assertNull($item['design_id']);
        $this->assertNull($item['design_image_id']);
        $this->assertSame(120000, $item['unit_price_snapshot']);
        $this->assertSame(240000, $item['final_price']);
        $this->assertSame([], $item['customization_json']);

        $cartService->clear();

        $color = $this->createColor();
        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 300000,
            'is_active' => true,
        ]);

        $cart = $cartService->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 1,
        ]);

        $item = $cart['items'][0];
        $this->assertSame($color->id, $item['color_id']);
        $this->assertSame(300000, $item['unit_price_snapshot']);
    }

    public function test_bank_workflow_still_enforces_design_color_compatibility_server_side(): void
    {
        $color = $this->createColor();
        $designData = $this->createForbiddenDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not compatible');

        app(CartService::class)->addItem(
            $this->bankAddPayload($product, $color, $designData['design'], $designData['designImage'])
        );
    }

    public function test_bank_workflow_unit_price_comes_only_from_product_color_price(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        $product->update(['base_price' => 111]);

        $cart = app(CartService::class)->addItem(
            $this->bankAddPayload($product, $color, $designData['design'], $designData['designImage'])
        );

        $item = $cart['items'][0];
        $this->assertSame(700000, $item['unit_price_snapshot']);
        $this->assertSame(700000, $item['final_price']);
        $this->assertNotSame(111, $item['unit_price_snapshot']);
    }

    public function test_workflow_value_cannot_resolve_arbitrary_classes(): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::BANK->value,
            'name' => 'محصول مخرب',
            'slug' => 'malicious-class-name',
            'is_active' => true,
        ]);

        DB::table('products')
            ->where('id', $product->id)
            ->update(['customization_workflow' => ProductCustomizer::class]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported customization workflow');

        app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => 1,
            'design_id' => 1,
            'quantity' => 1,
        ]);
    }

    public function test_type_mutation_does_not_change_workflow_behavior(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        $this->assertSame('bank', $product->getRawOriginal('type'));
        $this->assertSame('bank_card', $product->getRawOriginal('customization_workflow'));

        $product->update(['type' => ProductTypeEnum::STANDARD->value]);

        $this->assertSame('standard', $product->fresh()->getRawOriginal('type'));
        $this->assertSame('bank_card', $product->fresh()->getRawOriginal('customization_workflow'));

        $cart = app(CartService::class)->addItem(
            $this->bankAddPayload($product, $color, $designData['design'], $designData['designImage'])
        );

        $this->assertCount(1, $cart['items']);
        $this->assertSame(700000, $cart['items'][0]['unit_price_snapshot']);
    }

    public function test_workflow_mutation_does_not_change_type_taxonomy(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        $product->update(['customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value]);

        $this->assertSame('bank', $product->fresh()->getRawOriginal('type'));
        $this->assertSame('fuel_card', $product->fresh()->getRawOriginal('customization_workflow'));

        Livewire::test(ProductCustomizer::class, ['productId' => $product->id])
            ->assertStatus(404);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('currently unavailable');

        app(CartService::class)->addItem(
            $this->bankAddPayload($product, $color, $designData['design'], $designData['designImage'])
        );
    }

    private function designCatalogQueries(array $log): array
    {
        $tables = ['cate_designs', 'designs', 'design_images', 'design_color_compatibilities'];

        return array_values(array_filter(
            array_map(fn (array $query) => $query['query'], $log),
            fn (string $sql) => (bool) preg_match('~from (`|")(\w+)(`|")~i', $sql, $m) && in_array($m[2], $tables, true)
        ));
    }

    public function test_commerce_product_page_does_not_query_design_catalog(): void
    {
        $product = $this->createCommerceProduct();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('افزودن به سبد خرید');
        $response->assertDontSee('فهرست طرح‌ها');

        $this->assertCount(0, $this->designCatalogQueries(DB::getQueryLog()), 'Commerce-only page must not query the design catalog.');
    }

    public function test_bank_product_page_still_queries_design_catalog(): void
    {
        $color = $this->createColor();
        $designData = $this->createDesign($color);
        $product = $this->createBankProduct($color, $designData['design']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('فهرست طرح‌ها');

        $this->assertNotEmpty($this->designCatalogQueries(DB::getQueryLog()), 'Bank-card page must still load the design catalog.');
    }

    public function test_cart_resolves_color_without_extra_lazy_query(): void
    {
        $product = $this->createCommerceProduct();
        $color = $this->createColor();

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 300000,
            'is_active' => true,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $cart = app(CartService::class)->addItem([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'quantity' => 1,
        ]);

        $this->assertSame($color->name, $cart['items'][0]['color_name_snapshot']);
        $this->assertSame(300000, $cart['items'][0]['unit_price_snapshot']);
        $this->assertSame(300000, $cart['items'][0]['final_price']);

        $colorQueries = array_values(array_filter(
            array_map(fn (array $query) => $query['query'], DB::getQueryLog()),
            fn (string $sql) => (bool) preg_match('~from (`|")(\w+)(`|")~i', $sql, $m) && $m[2] === 'colors'
        ));
        $this->assertCount(1, $colorQueries, 'Commercial color must be resolved exactly once (eager loaded).');

        app(CartService::class)->clear();

        $bankColor = $this->createColor();
        $designData = $this->createDesign($bankColor);
        $bankProduct = $this->createBankProduct($bankColor, $designData['design']);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $cart = app(CartService::class)->addItem(
            $this->bankAddPayload($bankProduct, $bankColor, $designData['design'], $designData['designImage'])
        );

        $this->assertSame($bankColor->name, $cart['items'][0]['color_name_snapshot']);
        $this->assertSame(700000, $cart['items'][0]['unit_price_snapshot']);

        $colorQueries = array_values(array_filter(
            array_map(fn (array $query) => $query['query'], DB::getQueryLog()),
            fn (string $sql) => (bool) preg_match('~from (`|")(\w+)(`|")~i', $sql, $m) && $m[2] === 'colors'
        ));
        $this->assertCount(1, $colorQueries, 'Bank-card color must be resolved exactly once (eager loaded).');

        app(CartService::class)->clear();
    }
}
