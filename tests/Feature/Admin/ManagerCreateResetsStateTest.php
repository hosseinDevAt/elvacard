<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\CateDesignManager;
use App\Livewire\Admin\ColorManager;
use App\Livewire\Admin\DesignImageManager;
use App\Livewire\Admin\DesignManager;
use App\Livewire\Admin\ProductColorPriceManager;
use App\Livewire\Admin\ProductManager;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The "+ جدید" button in every admin manager used to only set showForm = true,
 * leaving the previous editingId in place. Opening "new" right after an edit
 * therefore saved over the row that was just edited. Every manager must reset
 * the form through create() before showing it.
 */
class ManagerCreateResetsStateTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function product(): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'name' => 'محصول وضعیت',
            'slug' => 'state-product-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ]);
    }

    public function test_product_manager_create_resets_editing_state(): void
    {
        $product = $this->product();

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('edit', $product->id)
            ->assertSet('editingId', $product->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }

    public function test_color_manager_create_resets_editing_state(): void
    {
        $color = Color::create(['name' => 'رنگ وضعیت', 'code_hex' => '#123456', 'is_active' => true, 'sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(ColorManager::class)
            ->call('edit', $color->id)
            ->assertSet('editingId', $color->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }

    public function test_product_color_price_manager_create_resets_editing_state(): void
    {
        $product = $this->product();
        $color = Color::create(['name' => 'رنگ قیمت', 'code_hex' => '#654321', 'is_active' => true, 'sort_order' => 1]);
        $price = ProductColorPrice::create(['product_id' => $product->id, 'color_id' => $color->id, 'price' => 50000, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(ProductColorPriceManager::class)
            ->call('edit', $price->id)
            ->assertSet('editingId', $price->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }

    public function test_design_manager_create_resets_editing_state(): void
    {
        $category = CateDesign::create(['name' => 'دسته وضعیت', 'slug' => 'state-cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $design = Design::create(['cate_design_id' => $category->id, 'name' => 'طرح وضعیت', 'slug' => 'state-design-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(DesignManager::class)
            ->call('edit', $design->id)
            ->assertSet('editingId', $design->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }

    public function test_cate_design_manager_create_resets_editing_state(): void
    {
        $category = CateDesign::create(['name' => 'دسته وضعیت', 'slug' => 'state-cate-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(CateDesignManager::class)
            ->call('edit', $category->id)
            ->assertSet('editingId', $category->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }

    public function test_design_image_manager_create_resets_editing_state(): void
    {
        $category = CateDesign::create(['name' => 'دسته تصویر وضعیت', 'slug' => 'state-img-cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $design = Design::create(['cate_design_id' => $category->id, 'name' => 'طرح تصویر وضعیت', 'slug' => 'state-img-design-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $color = Color::create(['name' => 'رنگ تصویر وضعیت', 'code_hex' => '#abcdef', 'is_active' => true, 'sort_order' => 1]);
        $image = DesignImage::create(['design_id' => $design->id, 'color_id' => $color->id, 'image_path' => 'designs/state.png', 'is_active' => true, 'sort_order' => 1]);

        Livewire::actingAs($this->admin())
            ->test(DesignImageManager::class)
            ->call('edit', $image->id)
            ->assertSet('editingId', $image->id)
            ->call('create')
            ->assertSet('editingId', null)
            ->assertSet('showForm', true);
    }
}
