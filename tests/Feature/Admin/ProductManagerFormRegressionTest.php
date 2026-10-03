<?php

namespace Tests\Feature\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression coverage for the two admin Product form bugs found during the
 * N-Onyx-38 audit:
 *
 *  1. The customization workflow <select> submits "" for "no workflow". The
 *     empty string used to fail the type/workflow consistency guard, making
 *     every ordinary product edit fail.
 *  2. The "+ جدید" button only set showForm = true, keeping the previous
 *     editingId, so saving the "new" form silently overwrote the row that had
 *     just been edited.
 */
class ProductManagerFormRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول پایه',
            'slug' => 'form-regression-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ], $overrides));
    }

    public function test_saving_new_product_with_empty_workflow_select_value_succeeds(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', '')
            ->set('name', 'محصول بدون شخصی‌سازی')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', [
            'name' => 'محصول بدون شخصی‌سازی',
            'customization_workflow' => null,
        ]);
    }

    public function test_editing_product_with_browser_empty_strings_succeeds(): void
    {
        $product = $this->product();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->set('customizationWorkflow', '')
            ->set('description', '')
            ->set('mainImage', '')
            ->set('basePrice', '')
            ->set('metaTitle', '')
            ->set('metaDescription', '')
            ->set('canonicalUrl', '')
            ->set('ogImage', '')
            ->set('seoContent', '')
            ->set('name', 'محصول ویرایش‌شده')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('محصول ویرایش‌شده', $product->fresh()->name);
    }

    public function test_fuel_workflow_edit_form_renders_activation_checklist(): void
    {
        $product = $this->product([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertHasNoErrors()
            ->assertSee('پیش‌نیازهای فعال‌سازی و فروش کارت سوخت')
            ->assertSee('انتخاب یک رنگ فعال')
            ->assertSee('تعریف قیمت')
            ->assertSee('انتخاب طرح کارت')
            ->assertSee('لازم است');
    }

    public function test_fuel_workflow_create_form_renders_placeholder_checklist(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('create')
            ->set('type', ProductTypeEnum::FUEL->value)
            ->set('customizationWorkflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->assertHasNoErrors()
            ->assertSee('پیش‌نیازهای فعال‌سازی و فروش کارت سوخت')
            ->assertSee('انتخاب یک رنگ فعال')
            ->assertDontSee('لازم است');
    }

    public function test_fuel_checklist_marks_prerequisite_complete_when_color_price_exists(): void
    {
        $product = $this->product([
            'type' => ProductTypeEnum::FUEL->value,
            'customization_workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            'name' => 'کارت سوخت آماده',
        ]);

        $color = Color::create([
            'name' => 'قرمز '.uniqid(),
            'code_hex' => '#ff0000',
            'is_active' => true,
        ]);

        ProductColorPrice::create([
            'product_id' => $product->id,
            'color_id' => $color->id,
            'price' => 250000,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertHasNoErrors()
            ->assertSee('تکمیل شده');
    }

    public function test_opening_create_after_edit_creates_a_new_product(): void
    {
        $existing = $this->product(['name' => 'محصول موجود']);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $existing->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('name', '')
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('name', 'محصول تازه')
            ->set('basePrice', 250000)
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('محصول موجود', $existing->fresh()->name);
        $this->assertDatabaseHas('products', ['name' => 'محصول تازه']);
    }
}
