<?php

namespace Tests\Feature;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Livewire\Admin\ProductManager;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductTypeWorkflowIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function saveProductManager(string $type, ?string $workflow, ?int $editingId = null)
    {
        return Livewire::actingAs($this->admin())
            ->test(ProductManager::class)
            ->set('editingId', $editingId)
            ->set('type', $type)
            ->set('customizationWorkflow', $workflow)
            ->set('name', 'محصول یکپارچگی نوع')
            ->set('isActive', false)
            ->call('save');
    }

    public static function invalidCombos(): array
    {
        return [
            'standard + bank_card' => [ProductTypeEnum::STANDARD->value, CustomizationWorkflowEnum::BANK_CARD->value],
            'standard + fuel_card' => [ProductTypeEnum::STANDARD->value, CustomizationWorkflowEnum::FUEL_CARD->value],
            'bank + none' => [ProductTypeEnum::BANK->value, null],
            'fuel + none' => [ProductTypeEnum::FUEL->value, null],
            'bank + fuel_card' => [ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::FUEL_CARD->value],
            'fuel + bank_card' => [ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::BANK_CARD->value],
        ];
    }

    public static function validCombos(): array
    {
        return [
            'standard + none' => [ProductTypeEnum::STANDARD->value, null, ProductTypeEnum::STANDARD->value, null],
            'bank + bank_card' => [ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value, ProductTypeEnum::BANK->value, CustomizationWorkflowEnum::BANK_CARD->value],
            'fuel + fuel_card' => [ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::FUEL_CARD->value, ProductTypeEnum::FUEL->value, CustomizationWorkflowEnum::FUEL_CARD->value],
        ];
    }

    #[DataProvider('invalidCombos')]
    public function test_product_manager_rejects_invalid_type_workflow_combo(string $type, ?string $workflow): void
    {
        $this->saveProductManager($type, $workflow)
            ->assertHasErrors('customizationWorkflow');

        $this->assertSame(0, Product::count(), 'An invalid combo must never be persisted.');
    }

    #[DataProvider('invalidCombos')]
    public function test_product_manager_rejects_invalid_type_workflow_combo_when_editing(string $type, ?string $workflow): void
    {
        $product = Product::create([
            'type' => ProductTypeEnum::STANDARD->value,
            'customization_workflow' => null,
            'name' => 'محصول موجود',
            'slug' => 'integrity-existing-'.uniqid(),
            'base_price' => 100000,
            'is_active' => false,
        ]);

        $rawWorkflowBefore = $product->getRawOriginal('customization_workflow');

        $this->saveProductManager($type, $workflow, $product->id)
            ->assertHasErrors('customizationWorkflow');

        $this->assertSame(
            $rawWorkflowBefore,
            $product->fresh()->getRawOriginal('customization_workflow'),
            'An invalid edit must not mutate the stored workflow.'
        );
        $this->assertSame(
            ProductTypeEnum::STANDARD->value,
            $product->fresh()->getRawOriginal('type'),
            'An invalid edit must not mutate the stored type.'
        );
    }

    #[DataProvider('validCombos')]
    public function test_product_manager_accepts_valid_type_workflow_combo(string $type, ?string $workflow, string $expectedType, ?string $expectedWorkflow): void
    {
        $this->saveProductManager($type, $workflow)
            ->assertHasNoErrors();

        $product = Product::firstOrFail();
        $this->assertSame($expectedType, $product->getRawOriginal('type'));
        $this->assertSame($expectedWorkflow, $product->getRawOriginal('customization_workflow'));
    }
}
