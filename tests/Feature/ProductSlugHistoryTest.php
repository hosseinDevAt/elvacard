<?php

namespace Tests\Feature;

use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\ProductSlugHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProductSlugHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function standardProduct(string $name, string $slug): Product
    {
        return Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => $name,
            'slug' => $slug,
            'base_price' => 100000,
            'is_active' => true,
        ]);
    }

    private function renameViaManager(Product $product, string $newName): void
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $product->id)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', $newName)
            ->set('basePrice', (int) $product->base_price)
            ->set('isActive', true)
            ->call('save')
            ->assertHasNoErrors();
    }

    private function createViaManager(string $name, bool $active = true, ?int $basePrice = 100000): Product
    {
        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('type', ProductTypeEnum::STANDARD->value)
            ->set('customizationWorkflow', null)
            ->set('name', $name)
            ->set('basePrice', $basePrice)
            ->set('isActive', $active)
            ->call('save')
            ->assertHasNoErrors();

        return Product::query()->where('name', $name)->latest('id')->firstOrFail();
    }

    public function test_rename_generates_new_slug_and_old_slug_301s_to_it(): void
    {
        $product = $this->standardProduct('عقاب کارت', 'eagle-card');
        $oldSlug = $product->slug;

        $this->renameViaManager($product, 'عقاب کارت طلایی');

        $fresh = $product->fresh();
        $expected = Str::slug('عقاب کارت طلایی');
        $this->assertNotSame($oldSlug, $fresh->slug);
        $this->assertSame($expected, $fresh->slug);

        $this->assertDatabaseHas('product_slug_histories', [
            'slug' => $oldSlug,
            'product_id' => $product->id,
        ]);

        $this->get(route('catalog.products.show', $oldSlug))
            ->assertStatus(301)
            ->assertRedirect(route('catalog.products.show', $expected));

        $this->get(route('catalog.products.show', $expected))
            ->assertOk()
            ->assertSee('عقاب کارت طلایی');
    }

    public function test_saving_without_a_name_change_keeps_slug_and_writes_no_history(): void
    {
        $product = $this->createViaManager('محصول پایدار');
        $slug = $product->slug;

        $this->renameViaManager($product, 'محصول پایدار');

        $this->assertSame($slug, $product->fresh()->slug);
        $this->assertSame(0, ProductSlugHistory::count());
    }

    public function test_historical_slug_cannot_be_claimed_by_another_product(): void
    {
        $a = $this->createViaManager('Foo Card');
        $this->assertSame('foo-card', $a->slug);

        $this->renameViaManager($a, 'Bar Card');
        $a->refresh();
        $this->assertSame('bar-card', $a->slug);

        $b = $this->createViaManager('Foo Card');
        $this->assertNotSame($a->id, $b->id);
        $this->assertSame('foo-card-2', $b->slug, 'The historical slug must stay reserved.');

        $this->assertDatabaseHas('product_slug_histories', [
            'slug' => 'foo-card',
            'product_id' => $a->id,
        ]);

        $this->get(route('catalog.products.show', 'foo-card'))
            ->assertStatus(301)
            ->assertRedirect(route('catalog.products.show', $a->slug));
    }

    public function test_historical_slug_never_redirects_to_another_product(): void
    {
        $a = $this->createViaManager('Foo Card');
        $this->renameViaManager($a, 'Bar Card');
        $a->refresh();
        $b = $this->createViaManager('Foo Card');
        $this->assertSame('foo-card-2', $b->slug);

        $response = $this->get(route('catalog.products.show', 'foo-card'));

        $response->assertStatus(301);
        $location = $response->headers->get('Location');
        $this->assertSame(route('catalog.products.show', $a->slug), $location);
        $this->assertNotSame(route('catalog.products.show', $b->slug), $location);
    }

    public function test_deleted_product_historical_slug_returns_404_and_stays_reserved(): void
    {
        $a = $this->createViaManager('Foo Card');
        $this->renameViaManager($a, 'Bar Card');
        $a->refresh();
        $this->assertSame('bar-card', $a->slug);

        Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->call('delete', $a->id);

        $this->assertDatabaseMissing('products', ['id' => $a->id]);

        $historical = $this->get(route('catalog.products.show', 'foo-card'));
        $historical->assertNotFound();
        $this->assertFalse($historical->headers->has('Location'));

        $this->get(route('catalog.products.show', 'bar-card'))->assertNotFound();

        $this->assertDatabaseHas('product_slug_histories', ['slug' => 'foo-card', 'product_id' => null]);
        $this->assertDatabaseHas('product_slug_histories', ['slug' => 'bar-card', 'product_id' => null]);

        $b = $this->createViaManager('Foo Card');
        $this->assertSame('foo-card-2', $b->slug);
    }

    public function test_unknown_slug_returns_404_without_a_redirect(): void
    {
        $response = $this->get(route('catalog.products.show', 'definitely-not-a-slug'));

        $response->assertNotFound();
        $this->assertFalse($response->headers->has('Location'));
    }
}
