<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Livewire\Component;
use Livewire\WithPagination;

class CateDesignManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $name = '';

    public bool $isActive = true;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'name' => 'required|string|min:1|max:255',
        'isActive' => 'boolean',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId && ! $this->isActive) {
            $blocker = ProductPurchaseabilityService::categoryDeactivationBlocker($this->editingId);

            if ($blocker !== null) {
                session()->flash('error', $blocker);

                return;
            }
        }

        $slug = $this->uniqueSlug($this->name, CateDesign::class, $this->editingId ? (int) $this->editingId : null);

        if ($this->editingId) {
            CateDesign::find($this->editingId)->update([
                'name' => $this->name,
                'slug' => $slug,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            CateDesign::create([
                'name' => $this->name,
                'slug' => $slug,
                'is_active' => $this->isActive,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $cate = CateDesign::find($id);

        if (! $cate) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->name = $cate->name;
        $this->isActive = (bool) $cate->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $cate = CateDesign::find($id);

        if (! $cate) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        if ($cate->designs()->exists()) {
            session()->flash('error', 'این دسته‌بندی دارای طرح است و قابل حذف نیست. ابتدا طرح‌های آن را حذف کنید.');

            return;
        }

        $cate->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.cate-design-manager', [
            'categories' => CateDesign::with('designs')->orderByDesc('id')->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت دسته‌بندی طرح‌ها');
    }
}
