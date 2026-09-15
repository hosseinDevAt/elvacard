<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Models\Design;
use App\Models\DesignImage;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\StoredFileManager;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Livewire\Component;
use Livewire\WithPagination;

class DesignManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

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

    public string $search = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
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

    protected $messages = [
        'cateDesignId.required' => 'دسته‌بندی طرح را انتخاب کنید.',
        'name.required' => 'نام طرح الزامی است.',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->editingId && ! $this->isActive) {
            $blocker = ProductPurchaseabilityService::designDeactivationBlocker($this->editingId);

            if ($blocker !== null) {
                session()->flash('error', $blocker);

                return;
            }
        }

        $slug = $this->uniqueSlug($this->name, Design::class, $this->editingId, 'design');

        $data = [
            'cate_design_id' => $this->cateDesignId,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description ?: null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'canonical_url' => $this->canonicalUrl ?: null,
            'seo_content' => $this->seoContent ?: null,
            'robots_index' => $this->robotsIndex,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            Design::find($this->editingId)->update($data);
            session()->flash('success', 'طرح با موفقیت ویرایش شد');
        } else {
            Design::create($data);
            session()->flash('success', 'طرح با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $design = Design::find($id);

        if (! $design) {
            session()->flash('error', 'طرح موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->cateDesignId = (int) $design->cate_design_id;
        $this->name = $design->name;
        $this->description = $design->description;
        $this->metaTitle = $design->meta_title;
        $this->metaDescription = $design->meta_description;
        $this->canonicalUrl = $design->canonical_url;
        $this->robotsIndex = (bool) $design->robots_index;
        $this->seoContent = $design->seo_content;
        $this->isActive = (bool) $design->is_active;
        $this->sortOrder = (int) $design->sort_order;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $design = Design::find($id);

        if (! $design) {
            session()->flash('error', 'طرح موردنظر یافت نشد');

            return;
        }

        if ($design->images()->exists()) {
            session()->flash('error', 'این طرح دارای تصویر است و قابل حذف نیست. ابتدا تصاویر آن را حذف کنید.');

            return;
        }

        $imagePaths = $design->images()->pluck('image_path')->all();

        $design->delete();

        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            $imagePaths,
            fn (string $path): bool => DesignImage::query()->where('image_path', $path)->exists(),
        );

        session()->flash('success', 'طرح با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->cateDesignId = null;
        $this->name = '';
        $this->description = null;
        $this->metaTitle = null;
        $this->metaDescription = null;
        $this->canonicalUrl = null;
        $this->robotsIndex = true;
        $this->seoContent = null;
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->editingId = null;
    }

    public function render()
    {
        $designs = Design::query()
            ->with('category')
            ->withCount('images')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.design-manager', [
            'designs' => $designs,
            'categories' => CateDesign::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ])->layout('layouts.admin')->title('مدیریت طرح‌ها');
    }
}
