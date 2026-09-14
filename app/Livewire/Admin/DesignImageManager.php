<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Services\Customization\ProductPurchaseabilityService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DesignImageManager extends Component
{
    use WithPagination;

    public ?int $designId = null;

    public ?int $colorId = null;

    public string $imagePath = '';

    public ?string $altText = null;

    public ?string $imageTitle = null;

    public ?string $optimizedFilename = null;

    public ?string $seoCaption = null;

    public bool $isActive = true;

    public int $sortOrder = 0;

    public string $search = '';

    public ?int $designFilter = null;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'designId' => 'required|integer|exists:designs,id',
        'colorId' => 'required|integer|exists:colors,id',
        'imagePath' => 'required|string|max:255',
        'altText' => 'nullable|string|max:255',
        'imageTitle' => 'nullable|string|max:255',
        'optimizedFilename' => 'nullable|string|max:255',
        'seoCaption' => 'nullable|string',
        'isActive' => 'boolean',
        'sortOrder' => 'integer|min:0',
    ];

    protected $messages = [
        'designId.required' => 'طرح را انتخاب کنید.',
        'colorId.required' => 'رنگ تصویر را انتخاب کنید.',
        'imagePath.required' => 'مسیر تصویر الزامی است.',
    ];

    public function save(): void
    {
        $this->validate();

        $data = [
            'design_id' => $this->designId,
            'color_id' => $this->colorId,
            'image_path' => $this->imagePath,
            'alt_text' => $this->altText ?: null,
            'image_title' => $this->imageTitle ?: null,
            'optimized_filename' => $this->optimizedFilename ?: null,
            'seo_caption' => $this->seoCaption ?: null,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            DesignImage::find($this->editingId)->update($data);
            session()->flash('success', 'تصویر طرح با موفقیت ویرایش شد');
        } else {
            DesignImage::create($data);
            session()->flash('success', 'تصویر طرح با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $image = DesignImage::find($id);

        if (! $image) {
            session()->flash('error', 'تصویر موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->designId = (int) $image->design_id;
        $this->colorId = (int) $image->color_id;
        $this->imagePath = $image->image_path;
        $this->altText = $image->alt_text;
        $this->imageTitle = $image->image_title;
        $this->optimizedFilename = $image->optimized_filename;
        $this->seoCaption = $image->seo_caption;
        $this->isActive = (bool) $image->is_active;
        $this->sortOrder = (int) $image->sort_order;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $image = DesignImage::find($id);

        if (! $image) {
            session()->flash('error', 'تصویر موردنظر یافت نشد');

            return;
        }

        $removalBlocker = ProductPurchaseabilityService::designImageRemovalBlocker($id);

        if ($removalBlocker !== null) {
            session()->flash('error', $removalBlocker);

            return;
        }

        $image->delete();
        session()->flash('success', 'تصویر طرح به همراه سازگاری‌هایش حذف شد');
    }

    #[Computed]
    public function designOptions(): array
    {
        $items = $this->designFilterOptions;

        return array_merge(
            $items->filter(fn ($design) => (bool) $design->is_active)
                ->map(fn ($design) => ['id' => $design->id, 'name' => $design->name])
                ->values()
                ->all(),
            $this->editingId
                ? $items->filter(fn ($design) => ! $design->is_active && (int) $design->id === (int) $this->designId)
                    ->map(fn ($design) => ['id' => $design->id, 'name' => $design->name.' (غیرفعال)'])
                    ->values()
                    ->all()
                : []
        );
    }

    #[Computed]
    public function designFilterOptions(): Collection
    {
        return Design::query()->orderBy('name')->get(['id', 'name', 'is_active']);
    }

    #[Computed]
    public function colorOptions(): array
    {
        $items = Color::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'is_active']);

        return array_merge(
            $items->filter(fn ($color) => (bool) $color->is_active)
                ->map(fn ($color) => ['id' => $color->id, 'name' => $color->name])
                ->values()
                ->all(),
            $this->editingId
                ? $items->filter(fn ($color) => ! $color->is_active && (int) $color->id === (int) $this->colorId)
                    ->map(fn ($color) => ['id' => $color->id, 'name' => $color->name.' (غیرفعال)'])
                    ->values()
                    ->all()
                : []
        );
    }

    public function resetForm(): void
    {
        $this->designId = null;
        $this->colorId = null;
        $this->imagePath = '';
        $this->altText = null;
        $this->imageTitle = null;
        $this->optimizedFilename = null;
        $this->seoCaption = null;
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->editingId = null;
    }

    public function render()
    {
        $images = DesignImage::query()
            ->with('design', 'color')
            ->when($this->designFilter, fn ($query) => $query->where('design_id', $this->designFilter))
            ->when($this->search !== '', fn ($query) => $query->where('image_path', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.design-image-manager', [
            'images' => $images,
            'designFilterOptions' => $this->designFilterOptions,
            'designOptions' => $this->designOptions,
            'colorOptions' => $this->colorOptions,
        ])->layout('layouts.admin')->title('تصاویر طرح‌ها');
    }
}
