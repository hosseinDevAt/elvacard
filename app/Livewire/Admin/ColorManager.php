<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;
use Livewire\WithPagination;

class ColorManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public string $name = '';

    public string $colorCode = '#000000';

    public ?string $previewImage = null;

    public bool $isActive = true;

    public int $sortOrder = 0;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'name' => 'required|string|min:1|max:100',
        'colorCode' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        'previewImage' => 'nullable|string|max:255',
        'isActive' => 'boolean',
        'sortOrder' => 'integer|min:0',
    ];

    protected $messages = [
        'colorCode.required' => 'کد رنگ الزامی است.',
        'colorCode.regex' => 'کد رنگ باید به فرمت #RRGGBB باشد (مثل #A1B2C3).',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId && ! $this->isActive) {
            $blocker = ProductPurchaseabilityService::colorDeactivationBlocker($this->editingId);

            if ($blocker !== null) {
                session()->flash('error', $blocker);

                return;
            }
        }

        $data = [
            'name' => $this->name,
            'code_hex' => strtoupper($this->colorCode),
            'preview_image' => $this->previewImage ?: null,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            Color::find($this->editingId)->update($data);
            session()->flash('success', 'رنگ با موفقیت ویرایش شد');
        } else {
            Color::create($data);
            session()->flash('success', 'رنگ با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $color = Color::find($id);

        if (! $color) {
            session()->flash('error', 'رنگ موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->name = $color->name;
        $this->colorCode = $color->code_hex ?: '#000000';
        $this->previewImage = $color->preview_image;
        $this->isActive = (bool) $color->is_active;
        $this->sortOrder = (int) $color->sort_order;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $color = Color::find($id);

        if (! $color) {
            session()->flash('error', 'رنگ موردنظر یافت نشد');

            return;
        }

        if ($color->productColorPrices()->exists()) {
            session()->flash('error', 'این رنگ در قیمت‌گذاری محصولات استفاده شده است و قابل حذف نیست.');

            return;
        }

        if ($color->designImages()->exists()) {
            session()->flash('error', 'این رنگ برای تصاویر طرح‌ها استفاده شده است و قابل حذف نیست.');

            return;
        }

        if ($color->designColorCompatibilities()->exists()) {
            session()->flash('error', 'این رنگ در سازگاری طرح‌ها استفاده شده است و قابل حذف نیست.');

            return;
        }

        $color->delete();
        session()->flash('success', 'رنگ با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->colorCode = '#000000';
        $this->previewImage = null;
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.color-manager', [
            'colors' => Color::orderBy('sort_order')->orderBy('id')->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت رنگ‌ها');
    }
}
