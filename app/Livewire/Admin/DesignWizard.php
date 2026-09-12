<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Support\Concerns\GeneratesUniqueSlug;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Unified 5-step workflow for creating and editing a Design.
 *
 *  ① اطلاعات ← ② تصاویر ← ③ رنگ‌ها ← ④ سازگاری ← ⑤ بررسی
 *
 * The admin never leaves this page to manage images, colors or the
 * image/color compatibility matrix. All of that lives inside the wizard,
 * so Design, DesignImage and DesignColorCompatibility are no longer
 * standalone sidebar items.
 */
class DesignWizard extends Component
{
    use GeneratesUniqueSlug;

    public const TOTAL_STEPS = 5;

    public ?int $designId = null;
    public int $step = 1;

    // Step 1 — basic information
    public ?int $cateDesignId = null;
    public string $name = '';
    public ?string $description = null;
    public ?string $metaTitle = null;
    public ?string $metaDescription = null;
    public ?string $canonicalUrl = null;
    public bool $robotsIndex = true;
    public ?string $seoContent = null;
    public bool $isActive = true;
    public int $sortOrder = 0;

    // Step 1 — quick category creation
    public bool $showCategoryForm = false;
    public string $newCategoryName = '';

    // Step 2 — image form
    public ?int $colorId = null;
    public string $imagePath = '';
    public ?string $altText = null;
    public ?string $imageTitle = null;
    public ?string $optimizedFilename = null;
    public ?string $seoCaption = null;
    public bool $imageIsActive = true;
    public int $imageSortOrder = 0;
    public ?int $editingImageId = null;
    public bool $showImageForm = false;

    public function mount(?int $designId = null): void
    {
        if (! $designId) {
            return;
        }

        $design = Design::findOrFail($designId);

        $this->designId = $design->id;
        $this->cateDesignId = (int) $design->cate_design_id;
        $this->name = (string) $design->name;
        $this->description = $design->description;
        $this->metaTitle = $design->meta_title;
        $this->metaDescription = $design->meta_description;
        $this->canonicalUrl = $design->canonical_url;
        $this->robotsIndex = (bool) $design->robots_index;
        $this->seoContent = $design->seo_content;
        $this->isActive = (bool) $design->is_active;
        $this->sortOrder = (int) $design->sort_order;
        $this->showImageForm = true;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function next(): void
    {
        if ($this->step === 1) {
            $this->validate($this->stepOneRules());
            $this->persistDesign();
        }

        if ($this->step < self::TOTAL_STEPS) {
            $this->step++;
        }
    }

    public function save(): void
    {
        if ($this->step === 1) {
            $this->validate($this->stepOneRules());
        }

        $this->persistDesign();

        session()->flash('success', 'طرح با موفقیت ذخیره شد.');

        $this->redirectRoute('admin.designs');
    }

    public function addCategory(): void
    {
        $this->validate([
            'newCategoryName' => 'required|string|min:1|max:255',
        ]);

        $category = CateDesign::create([
            'name' => $this->newCategoryName,
            'slug' => $this->uniqueSlug($this->newCategoryName, CateDesign::class),
            'is_active' => true,
        ]);

        $this->cateDesignId = (int) $category->id;
        $this->newCategoryName = '';
        $this->showCategoryForm = false;

        session()->flash('success', 'دسته‌بندی جدید اضافه شد.');
    }

    public function saveImage(): void
    {
        $this->validate([
            'colorId' => 'required|integer|exists:colors,id',
            'imagePath' => 'required|string|max:255',
            'altText' => 'nullable|string|max:255',
            'imageTitle' => 'nullable|string|max:255',
            'optimizedFilename' => 'nullable|string|max:255',
            'seoCaption' => 'nullable|string',
            'imageIsActive' => 'boolean',
            'imageSortOrder' => 'integer|min:0',
        ]);

        if (! $this->designId) {
            session()->flash('error', 'ابتدا اطلاعات پایه طرح را ثبت کنید.');

            return;
        }

        $data = [
            'color_id' => $this->colorId,
            'image_path' => $this->imagePath,
            'alt_text' => $this->altText ?: null,
            'image_title' => $this->imageTitle ?: null,
            'optimized_filename' => $this->optimizedFilename ?: null,
            'seo_caption' => $this->seoCaption ?: null,
            'is_active' => $this->imageIsActive,
            'sort_order' => $this->imageSortOrder,
        ];

        if ($this->editingImageId) {
            DesignImage::query()
                ->where('id', $this->editingImageId)
                ->where('design_id', $this->designId)
                ->update($data);
            session()->flash('success', 'تصویر طرح با موفقیت ویرایش شد.');
        } else {
            DesignImage::create(array_merge($data, ['design_id' => $this->designId]));
            session()->flash('success', 'تصویر طرح با موفقیت اضافه شد.');
        }

        $this->resetImageForm();
    }

    public function editImage(int $id): void
    {
        $image = DesignImage::find($id);

        if (! $image || (int) $image->design_id !== (int) $this->designId) {
            session()->flash('error', 'تصویر موردنظر یافت نشد.');

            return;
        }

        $this->editingImageId = (int) $image->id;
        $this->colorId = (int) $image->color_id;
        $this->imagePath = (string) $image->image_path;
        $this->altText = $image->alt_text;
        $this->imageTitle = $image->image_title;
        $this->optimizedFilename = $image->optimized_filename;
        $this->seoCaption = $image->seo_caption;
        $this->imageIsActive = (bool) $image->is_active;
        $this->imageSortOrder = (int) $image->sort_order;
        $this->showImageForm = true;
    }

    public function deleteImage(int $id): void
    {
        $image = DesignImage::find($id);

        if (! $image || (int) $image->design_id !== (int) $this->designId) {
            session()->flash('error', 'تصویر موردنظر یافت نشد.');

            return;
        }

        $image->delete();
        session()->flash('success', 'تصویر طرح به همراه سازگاری‌هایش حذف شد.');
    }

    public function toggleCompatibility(int $designImageId, int $colorId): void
    {
        $image = DesignImage::find($designImageId);

        if (! $image || (int) $image->design_id !== (int) $this->designId) {
            session()->flash('error', 'تصویر انتخابی متعلق به این طرح نیست.');

            return;
        }

        if (! Color::query()->whereKey($colorId)->exists()) {
            session()->flash('error', 'رنگ موردنظر یافت نشد.');

            return;
        }

        $existing = DesignColorCompatibility::query()
            ->where('design_image_id', $designImageId)
            ->where('card_color_id', $colorId)
            ->first();

        if ($existing) {
            $existing->update(['is_allowed' => ! $existing->is_allowed]);
            session()->flash('success', 'وضعیت سازگاری با موفقیت تغییر کرد.');
        } else {
            DesignColorCompatibility::create([
                'design_image_id' => $designImageId,
                'card_color_id' => $colorId,
                'is_allowed' => true,
            ]);
            session()->flash('success', 'سازگاری جدید ثبت شد.');
        }
    }

    public function resetImageForm(): void
    {
        $this->colorId = null;
        $this->imagePath = '';
        $this->altText = null;
        $this->imageTitle = null;
        $this->optimizedFilename = null;
        $this->seoCaption = null;
        $this->imageIsActive = true;
        $this->imageSortOrder = 0;
        $this->editingImageId = null;
        $this->showImageForm = true;
    }

    protected function stepOneRules(): array
    {
        return [
            'cateDesignId' => 'required|integer|exists:cate_designs,id',
            'name' => 'required|string|min:1|max:255',
            'description' => 'nullable|string',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string|max:255',
            'canonicalUrl' => 'nullable|string|max:255',
            'seoContent' => 'nullable|string',
            'robotsIndex' => 'boolean',
            'isActive' => 'boolean',
            'sortOrder' => 'integer|min:0',
        ];
    }

    protected function persistDesign(): void
    {
        $data = [
            'cate_design_id' => $this->cateDesignId,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'canonical_url' => $this->canonicalUrl ?: null,
            'seo_content' => $this->seoContent ?: null,
            'robots_index' => $this->robotsIndex,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->designId) {
            Design::whereKey($this->designId)->update($data);

            return;
        }

        $data['slug'] = $this->uniqueSlug($this->name, Design::class, null, 'design');

        $this->designId = (int) Design::create($data)->id;
    }

    /**
     * Built the image × color compatibility matrix for the current design.
     *
     * @return array<int, array{image: DesignImage, allowedCount: int, map: array<int, bool>}>
     */
    protected function compatibilityRows(Collection $images, Collection $colors): array
    {
        $rows = [];

        foreach ($images as $image) {
            $map = [];

            foreach ($colors as $color) {
                $row = $image->compatibilities->firstWhere('card_color_id', $color->id);
                $map[$color->id] = $row ? (bool) $row->is_allowed : false;
            }

            $rows[] = [
                'image' => $image,
                'allowedCount' => count(array_filter($map)),
                'map' => $map,
            ];
        }

        return $rows;
    }

    public function render()
    {
        $colors = Color::query()->active()->orderBy('sort_order')->orderBy('name')->get();
        $categories = CateDesign::query()->orderBy('name')->get(['id', 'name', 'is_active']);

        $images = collect();

        if ($this->designId) {
            $images = DesignImage::query()
                ->where('design_id', $this->designId)
                ->with(['color', 'compatibilities'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $colorOverview = [];
        foreach ($images as $image) {
            $color = $image->color;
            if (! $color) {
                continue;
            }
            if (! isset($colorOverview[$color->id])) {
                $colorOverview[$color->id] = ['name' => $color->name, 'hex' => $color->code_hex, 'count' => 0];
            }
            $colorOverview[$color->id]['count']++;
        }

        $compatibilityRows = $this->compatibilityRows($images, $colors);

        $design = $this->designId ? Design::with('category')->find($this->designId) : null;

        $summary = [
            'name' => $this->name,
            'category' => $this->cateDesignId ? CateDesign::find($this->cateDesignId)?->name : null,
            'slug' => $design?->slug,
            'isActive' => $this->isActive,
            'sortOrder' => $this->sortOrder,
            'description' => $this->description,
            'imagesCount' => $images->count(),
            'colorsCount' => count($colorOverview),
            'allowedCombos' => array_sum(array_column($compatibilityRows, 'allowedCount')),
        ];

        return view('livewire.admin.design-wizard', [
            'colors' => $colors,
            'categories' => $categories,
            'images' => $images,
            'colorOverview' => $colorOverview,
            'compatibilityRows' => $compatibilityRows,
            'design' => $design,
            'summary' => $summary,
        ])->layout('layouts.admin')->title($this->designId ? 'ویرایش طرح' : 'طرح جدید');
    }
}