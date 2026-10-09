<?php

namespace App\Livewire\Admin;

use App\Models\OrderProcessGuide;
use App\Models\OrderProcessStage;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrderProcessGuideManager extends Component
{
    use AuthorizesAdminActions;

    public string $workflow = OrderProcessGuide::WORKFLOW_BANK;

    // Guide metadata
    public string $guideTitle = '';
    public string $guideDescription = '';
    public bool $isPublished = false;

    // Stage Form Modal
    public bool $showStageModal = false;
    public ?int $editingStageId = null;
    public string $stageTitle = '';
    public string $stageDescription = '';
    public string $stageDurationHint = '';
    public string $stageIcon = 'check-badge';
    public int $stageSortOrder = 0;
    public bool $stageIsActive = true;

    protected function rules(): array
    {
        return [
            'guideTitle' => 'required|string|max:255',
            'guideDescription' => 'nullable|string|max:3000',
            'isPublished' => 'boolean',
        ];
    }

    public function mount(): void
    {
        $this->loadGuide();
    }

    public function switchWorkflow(string $workflow): void
    {
        if (! in_array($workflow, OrderProcessGuide::WORKFLOWS, true)) {
            return;
        }

        $this->workflow = $workflow;
        $this->cancelStageModal();
        $this->loadGuide();
    }

    public function loadGuide(): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $this->guideTitle = $guide->title;
        $this->guideDescription = (string) ($guide->description ?? '');
        $this->isPublished = (bool) $guide->is_published;
    }

    public function saveGuide(): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            session()->flash('error', 'جداول پایگاه داده هنوز ایجاد نشده‌اند.');
            return;
        }

        $this->validate();

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $guide->update([
            'title' => $this->guideTitle,
            'description' => $this->guideDescription !== '' ? $this->guideDescription : null,
            'is_published' => $this->isPublished,
        ]);

        session()->flash('success', 'تنظیمات کلی راهنما با موفقیت ذخیره شد.');
    }

    public function togglePublish(): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            session()->flash('error', 'جداول پایگاه داده هنوز ایجاد نشده‌اند.');
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $newStatus = ! $guide->is_published;
        $guide->update(['is_published' => $newStatus]);
        $this->isPublished = $newStatus;

        session()->flash('success', $newStatus ? 'راهنما منتشر شد.' : 'راهنما از حالت انتشار خارج شد.');
    }

    public function createStage(): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            session()->flash('error', 'جداول پایگاه داده هنوز ایجاد نشده‌اند.');
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $this->resetStageForm();
        $maxSort = $guide->stages()->max('sort_order') ?? 0;
        $this->stageSortOrder = $maxSort + 1;
        $this->showStageModal = true;
    }

    public function editStage(int $stageId): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            session()->flash('error', 'جداول پایگاه داده هنوز ایجاد نشده‌اند.');
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $stage = $guide->stages()->find($stageId);

        if (! $stage) {
            session()->flash('error', 'مرحله موردنظر یافت نشد.');
            return;
        }

        $this->editingStageId = $stage->id;
        $this->stageTitle = $stage->title;
        $this->stageDescription = $stage->description;
        $this->stageDurationHint = (string) ($stage->duration_hint ?? '');
        $this->stageIcon = $stage->icon ?: 'check-badge';
        $this->stageSortOrder = (int) $stage->sort_order;
        $this->stageIsActive = (bool) $stage->is_active;
        $this->showStageModal = true;
    }

    public function saveStage(): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            session()->flash('error', 'جداول پایگاه داده هنوز ایجاد نشده‌اند.');
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $this->validate([
            'stageTitle' => 'required|string|max:255',
            'stageDescription' => 'required|string|max:3000',
            'stageDurationHint' => 'nullable|string|max:100',
            'stageIcon' => ['nullable', 'string', Rule::in(array_keys(OrderProcessStage::SUPPORTED_ICONS))],
            'stageSortOrder' => 'required|integer|min:0',
            'stageIsActive' => 'boolean',
        ]);

        if ($this->editingStageId) {
            $stage = $guide->stages()->find($this->editingStageId);

            if (! $stage) {
                session()->flash('error', 'مرحله موردنظر یافت نشد.');
                return;
            }

            $stage->update([
                'title' => $this->stageTitle,
                'description' => $this->stageDescription,
                'duration_hint' => $this->stageDurationHint !== '' ? $this->stageDurationHint : null,
                'icon' => $this->stageIcon ?: null,
                'sort_order' => $this->stageSortOrder,
                'is_active' => $this->stageIsActive,
            ]);

            session()->flash('success', 'مرحله با موفقیت ویرایش شد.');
        } else {
            $guide->stages()->create([
                'title' => $this->stageTitle,
                'description' => $this->stageDescription,
                'duration_hint' => $this->stageDurationHint !== '' ? $this->stageDurationHint : null,
                'icon' => $this->stageIcon ?: null,
                'sort_order' => $this->stageSortOrder,
                'is_active' => $this->stageIsActive,
            ]);

            session()->flash('success', 'مرحله جدید با موفقیت اضافه شد.');
        }

        $this->cancelStageModal();
    }

    public function toggleStageActive(int $stageId): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $stage = $guide->stages()->find($stageId);

        if (! $stage) {
            session()->flash('error', 'مرحله موردنظر یافت نشد.');
            return;
        }

        $stage->update(['is_active' => ! $stage->is_active]);
        session()->flash('success', 'وضعیت مرحله به‌روزرسانی شد.');
    }

    public function moveStageUp(int $stageId): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $stages = $guide->stages()->get();
        $currentIndex = $stages->search(fn ($s) => $s->id === $stageId);

        if ($currentIndex === false || $currentIndex === 0) {
            return;
        }

        $prevStage = $stages[$currentIndex - 1];
        $currentStage = $stages[$currentIndex];

        $prevOrder = (int) $prevStage->sort_order;
        $currentOrder = (int) $currentStage->sort_order;

        if ($prevOrder === $currentOrder) {
            $currentStage->update(['sort_order' => max(0, $prevOrder - 1)]);
        } else {
            $currentStage->update(['sort_order' => $prevOrder]);
            $prevStage->update(['sort_order' => $currentOrder]);
        }

        session()->flash('success', 'ترتیب مرحله تغییر یافت.');
    }

    public function moveStageDown(int $stageId): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $stages = $guide->stages()->get();
        $currentIndex = $stages->search(fn ($s) => $s->id === $stageId);

        if ($currentIndex === false || $currentIndex >= $stages->count() - 1) {
            return;
        }

        $nextStage = $stages[$currentIndex + 1];
        $currentStage = $stages[$currentIndex];

        $nextOrder = (int) $nextStage->sort_order;
        $currentOrder = (int) $currentStage->sort_order;

        if ($nextOrder === $currentOrder) {
            $nextStage->update(['sort_order' => $currentOrder + 1]);
        } else {
            $currentStage->update(['sort_order' => $nextOrder]);
            $nextStage->update(['sort_order' => $currentOrder]);
        }

        session()->flash('success', 'ترتیب مرحله تغییر یافت.');
    }

    public function deleteStage(int $stageId): void
    {
        if (! OrderProcessGuide::tablesExist()) {
            return;
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        if (! $guide) {
            return;
        }

        $stage = $guide->stages()->find($stageId);

        if (! $stage) {
            session()->flash('error', 'مرحله موردنظر یافت نشد.');
            return;
        }

        $stage->delete();
        session()->flash('success', 'مرحله با موفقیت حذف شد.');
    }

    public function cancelStageModal(): void
    {
        $this->showStageModal = false;
        $this->resetStageForm();
    }

    private function resetStageForm(): void
    {
        $this->editingStageId = null;
        $this->stageTitle = '';
        $this->stageDescription = '';
        $this->stageDurationHint = '';
        $this->stageIcon = 'check-badge';
        $this->stageSortOrder = 0;
        $this->stageIsActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        $tablesReady = OrderProcessGuide::tablesExist();

        if (! $tablesReady) {
            return view('livewire.admin.order-process-guide-manager', [
                'tablesReady' => false,
                'guide' => null,
                'stages' => collect(),
                'supportedIcons' => OrderProcessStage::SUPPORTED_ICONS,
                'workflowLabels' => OrderProcessGuide::WORKFLOW_LABELS,
            ])->layout('layouts.admin')->title('مدیریت راهنمای مراحل سفارش');
        }

        $guide = OrderProcessGuide::findOrCreateForWorkflow($this->workflow);
        $stages = $guide ? $guide->stages : collect();

        return view('livewire.admin.order-process-guide-manager', [
            'tablesReady' => true,
            'guide' => $guide,
            'stages' => $stages,
            'supportedIcons' => OrderProcessStage::SUPPORTED_ICONS,
            'workflowLabels' => OrderProcessGuide::WORKFLOW_LABELS,
        ])->layout('layouts.admin')->title('مدیریت راهنمای مراحل سفارش');
    }
}
