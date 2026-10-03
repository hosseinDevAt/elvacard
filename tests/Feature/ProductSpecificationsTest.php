<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSpecificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'دستبند چرمی الگو',
            'slug' => 'leather-bracelet-'.uniqid(),
            'description' => 'توضیحات تست دستبند چرمی',
            'base_price' => 350000,
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_can_add_reorder_and_save_specifications(): void
    {
        $product = $this->createProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('specifications', [])
            ->call('addSpecificationRow')
            ->set('specifications.0.label', 'جنس بدنه')
            ->set('specifications.0.value', 'استیل ۳۱۶ ضدزنگ')
            ->call('addSpecificationRow')
            ->set('specifications.1.label', 'ضخامت')
            ->set('specifications.1.value', '۰.۸ میلی‌متر')
            ->call('moveSpecificationDown', 0)
            ->assertSet('specifications.0.label', 'ضخامت')
            ->assertSet('specifications.1.label', 'جنس بدنه')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertCount(2, $fresh->specifications);
        $this->assertEquals('ضخامت', $fresh->specifications[0]['label']);
        $this->assertEquals('۰.۸ میلی‌متر', $fresh->specifications[0]['value']);
        $this->assertEquals('جنس بدنه', $fresh->specifications[1]['label']);
        $this->assertEquals('استیل ۳۱۶ ضدزنگ', $fresh->specifications[1]['value']);
    }

    public function test_admin_can_remove_specification_row(): void
    {
        $product = $this->createProduct([
            'specifications' => [
                ['label' => 'ویژگی ۱', 'value' => 'مقدار ۱'],
                ['label' => 'ویژگی ۲', 'value' => 'مقدار ۲'],
                ['label' => 'ویژگی ۳', 'value' => 'مقدار ۳'],
            ],
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertCount('specifications', 3)
            ->call('removeSpecificationRow', 1)
            ->assertCount('specifications', 2)
            ->assertSet('specifications.0.label', 'ویژگی ۱')
            ->assertSet('specifications.1.label', 'ویژگی ۳')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertCount(2, $fresh->specifications);
        $this->assertEquals('ویژگی ۱', $fresh->specifications[0]['label']);
        $this->assertEquals('ویژگی ۳', $fresh->specifications[1]['label']);
    }

    public function test_empty_specification_rows_are_pruned_on_save(): void
    {
        $product = $this->createProduct();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->call('addSpecificationRow')
            ->set('specifications.0.label', 'ابعاد')
            ->set('specifications.0.value', '۸۵ × ۵۴ میلی‌متر')
            ->call('addSpecificationRow')
            ->set('specifications.1.label', '   ')
            ->set('specifications.1.value', '')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $product->fresh();
        $this->assertCount(1, $fresh->specifications);
        $this->assertEquals('ابعاد', $fresh->specifications[0]['label']);
    }

    public function test_product_specifications_are_isolated_and_reset_on_create(): void
    {
        $productA = $this->createProduct(['specifications' => [['label' => 'رنگ', 'value' => 'طلایی']]]);
        $productB = $this->createProduct(['specifications' => [['label' => 'رنگ', 'value' => 'نقره‌ای']]]);

        $test = Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $productA->id)
            ->assertSet('specifications.0.value', 'طلایی')
            ->call('edit', $productB->id)
            ->assertSet('specifications.0.value', 'نقره‌ای')
            ->call('create')
            ->assertSet('specifications', []);
    }

    public function test_public_detail_page_renders_product_specifications(): void
    {
        $product = $this->createProduct([
            'specifications' => [
                ['label' => 'جنس بدنه', 'value' => 'فلزی براق'],
                ['label' => 'نوع چیپ', 'value' => 'NFC NTAG216'],
            ],
        ]);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('جنس بدنه');
        $response->assertSee('فلزی براق');
        $response->assertSee('نوع چیپ');
        $response->assertSee('NFC NTAG216');
        $response->assertDontSee('مشخصات فنی ثبت‌شده‌ای برای این محصول تعریف نشده است.');
    }

    public function test_public_detail_page_renders_graceful_empty_state_when_no_specs(): void
    {
        $product = $this->createProduct(['specifications' => null]);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('مشخصات فنی ثبت‌شده‌ای برای این محصول تعریف نشده است.');
    }

    public function test_public_detail_page_escapes_html_in_specifications(): void
    {
        $xssLabel = '<script>alert("xss-label")</script>';
        $xssValue = '<img src=x onerror=alert("xss-val")>';

        $product = $this->createProduct([
            'specifications' => [
                ['label' => $xssLabel, 'value' => $xssValue],
            ],
        ]);

        $response = $this->get(route('catalog.products.show', $product->slug));

        $response->assertOk();
        // Plain unescaped script tag should NOT be present
        $response->assertDontSee($xssLabel, false);
        $response->assertDontSee($xssValue, false);
        // Escaped HTML entities must be present
        $response->assertSee(e($xssLabel), false);
        $response->assertSee(e($xssValue), false);
    }
}
