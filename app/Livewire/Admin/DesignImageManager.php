<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use App\Models\Design;
use App\Models\DesignImage;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\StoredFileManager;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DesignImageManager extends Component
{
    use AuthorizesAdminActions;
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

    public $designFilter = null;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'designFilter' => 'nullable|integer|exists:designs,id',
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
        'designFilter.integer' => 'شناسه طرح باید عدد صحیح باشد.',
        'designFilter.exists' => 'طرح انتخاب‌شده نامعتبر است.',
        'designId.required' => 'طرح را انتخاب کنید.',
        'colorId.required' => 'رنگ تصویر را انتخاب کنید.',
        'imagePath.required' => 'مسیر تصویر الزامی است.',
    ];

    public function updatedDesignFilter($value): void
    {
        if ($value === '' || $value === null) {
            $this->designFilter = null;
            $this->resetErrorBag('designFilter');
            $this->resetPage();

            return;
        }

        $this->validateOnly('designFilter', [
            'designFilter' => ['nullable', 'integer', 'exists:designs,id'],
        ], [
            'designFilter.integer' => 'شناسه طرح باید عدد صحیح باشد.',
            'designFilter.exists' => 'طرح انتخاب‌شده نامعتبر است.',
        ]);

        $this->resetPage();
    }

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
            $designImage = DesignImage::find($this->editingId);

            if (! $designImage) {
                session()->flash('error', 'تصویر طرح موردنظر یافت نشد');

                return;
            }

            $wasActive = (bool) $designImage->is_active;
            $previousDesignId = (int) $designImage->design_id;
            $previousPath = $designImage->image_path;

            // Both transitions below take an active image out of its current
            // design's visible set: deactivating it, or moving it under another
            // design. Reuse the delete-path blocker so the "a design keeps at
            // least one visible image" invariant also holds on edits. Inactive
            // images and pure metadata/path edits are untouched by this.
            $leavesCurrentDesign = $wasActive
                && (! $this->isActive || (int) $this->designId !== $previousDesignId);

            if ($leavesCurrentDesign) {
                $removalBlocker = ProductPurchaseabilityService::designImageRemovalBlocker((int) $designImage->id);

                if ($removalBlocker !== null) {
                    session()->flash('error', $removalBlocker);

                    return;
                }
            }

            $designImage->update($data);

            // Same order-aware cleanup the wizard performs. It runs only after a
            // successful write, so a failed update never frees the old file, and
            // a file still referenced by a live row or by an order's
            // design_image_path_snapshot is preserved.
            if ($previousPath !== null && $previousPath !== $data['image_path']) {
                app(StoredFileManager::class)->deleteDesignImageFilesWhenUnreferenced([$previousPath]);
            }

            session()->flash('success', 'تصویر طرح با موفقیت ویرایش شد');
        } else {
            DesignImage::create($data);
            session()->flash('success', 'تصویر طرح با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new design image. Always resets first:
     * otherwise opening the form right after editing another row keeps the
     * previous editingId and silently overwrites that image on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
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

        app(StoredFileManager::class)->deleteDesignImageFilesWhenUnreferenced([$image->image_path]);

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
        if ($this->designFilter !== null && $this->designFilter !== '') {
            $validator = Validator::make(
                ['designFilter' => $this->designFilter],
                ['designFilter' => ['nullable', 'integer', 'exists:designs,id']],
                [
                    'designFilter.integer' => 'شناسه طرح باید عدد صحیح باشد.',
                    'designFilter.exists' => 'طرح انتخاب‌شده نامعتبر است.',
                ]
            );

            if ($validator->fails()) {
                $this->addError('designFilter', $validator->errors()->first('designFilter'));
            }
        }

        $hasFilterError = $this->getErrorBag()->has('designFilter');

        $images = DesignImage::query()
            ->with('design', 'color')
            ->when(
                $this->designFilter && ! $hasFilterError,
                fn ($query) => $query->where('design_id', (int) $this->designFilter)
            )
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
