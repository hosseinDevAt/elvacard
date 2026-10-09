<?php

namespace Tests\Feature;

use App\Livewire\Admin\OrderProcessGuideManager;
use App\Models\Order;
use App\Models\OrderProcessGuide;
use App\Models\OrderProcessStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderProcessGuideAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        OrderProcessGuide::resetTableExistenceCache();
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'customer']);
    }

    public function test_guest_is_redirected_from_admin_order_process_page(): void
    {
        $this->get(route('admin.order-process'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_is_forbidden_from_admin_order_process_page(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.order-process'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_order_process_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.order-process'))
            ->assertOk()
            ->assertSee('مدیریت راهنمای مراحل سفارش');
    }

    public function test_admin_order_process_manager_handles_missing_tables_gracefully(): void
    {
        OrderProcessGuide::$tablesExistOverride = false;

        $response = $this->actingAs($this->admin())
            ->get(route('admin.order-process'));

        $response->assertOk()
            ->assertSee('جداول پایگاه داده ایجاد نشده‌اند');

        OrderProcessGuide::resetTableExistenceCache();
    }

    public function test_admin_can_switch_workflows(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->assertSet('workflow', OrderProcessGuide::WORKFLOW_BANK)
            ->call('switchWorkflow', OrderProcessGuide::WORKFLOW_FUEL)
            ->assertSet('workflow', OrderProcessGuide::WORKFLOW_FUEL);
    }

    public function test_admin_can_update_guide_settings(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->set('guideTitle', 'مراحل سفارش کارت فلزی لوکس')
            ->set('guideDescription', 'توضیحات آزمایشی روند سفارش')
            ->set('isPublished', true)
            ->call('saveGuide')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('order_process_guides', [
            'workflow' => OrderProcessGuide::WORKFLOW_BANK,
            'title' => 'مراحل سفارش کارت فلزی لوکس',
            'description' => 'توضیحات آزمایشی روند سفارش',
            'is_published' => true,
        ]);
    }

    public function test_admin_can_toggle_publish_status(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->assertSet('isPublished', false)
            ->call('togglePublish')
            ->assertSet('isPublished', true);

        $this->assertDatabaseHas('order_process_guides', [
            'workflow' => OrderProcessGuide::WORKFLOW_BANK,
            'is_published' => true,
        ]);
    }

    public function test_admin_can_create_and_edit_stages(): void
    {
        $testComponent = Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->call('createStage')
            ->assertSet('showStageModal', true)
            ->set('stageTitle', 'مرحله ۱: انتخاب طرح')
            ->set('stageDescription', 'توضیح کامل انتخاب طرح دلخواه')
            ->set('stageDurationHint', 'آنی')
            ->set('stageIcon', 'palette')
            ->set('stageSortOrder', 1)
            ->set('stageIsActive', true)
            ->call('saveStage')
            ->assertHasNoErrors()
            ->assertSet('showStageModal', false);

        $stage = OrderProcessStage::first();
        $this->assertNotNull($stage);
        $this->assertEquals('مرحله ۱: انتخاب طرح', $stage->title);
        $this->assertEquals('palette', $stage->icon);

        // Edit stage
        $testComponent->call('editStage', $stage->id)
            ->assertSet('editingStageId', $stage->id)
            ->set('stageTitle', 'مرحله ۱: ویرایش شده')
            ->call('saveStage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('order_process_stages', [
            'id' => $stage->id,
            'title' => 'مرحله ۱: ویرایش شده',
        ]);
    }

    public function test_admin_cannot_access_or_mutate_stages_of_another_guide(): void
    {
        $fuelGuide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_FUEL);
        $fuelStage = $fuelGuide->stages()->create([
            'title' => 'مرحله کارت سوخت',
            'description' => 'توضیحات',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Attempting to edit or delete the fuel stage while active workflow is bank_card
        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->assertSet('workflow', OrderProcessGuide::WORKFLOW_BANK)
            ->call('editStage', $fuelStage->id)
            ->assertSet('editingStageId', null)
            ->assertSet('showStageModal', false)
            ->call('deleteStage', $fuelStage->id);

        // Fuel stage must still exist and be completely untouched
        $this->assertDatabaseHas('order_process_stages', [
            'id' => $fuelStage->id,
            'title' => 'مرحله کارت سوخت',
        ]);
    }

    public function test_admin_can_toggle_stage_active_status(): void
    {
        $guide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_BANK);
        $stage = $guide->stages()->create([
            'title' => 'مرحله تست',
            'description' => 'توضیحات',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->call('toggleStageActive', $stage->id);

        $this->assertFalse($stage->fresh()->is_active);
    }

    public function test_admin_can_reorder_stages(): void
    {
        $guide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_BANK);
        $stage1 = $guide->stages()->create(['title' => 'مرحله اول', 'description' => 'توضیح', 'sort_order' => 1, 'is_active' => true]);
        $stage2 = $guide->stages()->create(['title' => 'مرحله دوم', 'description' => 'توضیح', 'sort_order' => 2, 'is_active' => true]);

        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->call('moveStageDown', $stage1->id);

        $this->assertEquals(2, $stage1->fresh()->sort_order);
        $this->assertEquals(1, $stage2->fresh()->sort_order);
    }

    public function test_admin_can_delete_stage(): void
    {
        $guide = OrderProcessGuide::findOrCreateForWorkflow(OrderProcessGuide::WORKFLOW_BANK);
        $stage = $guide->stages()->create([
            'title' => 'مرحله حذف‌شدنی',
            'description' => 'توضیحات',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->call('deleteStage', $stage->id);

        $this->assertDatabaseMissing('order_process_stages', [
            'id' => $stage->id,
        ]);
    }

    public function test_stage_icon_must_be_in_allowlist(): void
    {
        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->call('createStage')
            ->set('stageTitle', 'مرحله با آیکون غیرمجاز')
            ->set('stageDescription', 'توضیح')
            ->set('stageIcon', 'unsupported-arbitrary-icon')
            ->call('saveStage')
            ->assertHasErrors(['stageIcon']);
    }

    public function test_order_process_operations_never_touch_orders_table(): void
    {
        $this->assertEquals(0, Order::count());

        Livewire::actingAs($this->admin())
            ->test(OrderProcessGuideManager::class)
            ->set('guideTitle', 'راهنمای ایزوله')
            ->set('isPublished', true)
            ->call('saveGuide')
            ->call('createStage')
            ->set('stageTitle', 'مرحله تست ایزولاسیون')
            ->set('stageDescription', 'توضیح')
            ->call('saveStage');

        $this->assertEquals(0, Order::count());
    }
}
