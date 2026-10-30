<?php

namespace App\Livewire\Admin;

use App\Models\CateDesign;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\DesignCatalogService;
use App\Services\StoredFileManager;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class DesignManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    /**
     * Verbatim copy of the message DesignWizard::save() flashes for the same
     * condition, so the identical refusal reads identically on both admin
     * entry points. Components keep their own message constants in this
     * codebase rather than sharing a global catalogue.
     */
    private const NOT_READY_FOR_WORKSPACE = 'برای فعال‌سازی، طرح باید حداقل یک تصویر فعال داشته باشد که برای یک رنگ فعال مجاز شده باشد؛ در غیر این صورت در بخش شخصی‌سازی نمایش داده نمی‌شود.';

    public ?int $cateDesignId = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $canonicalUrl = null;

    public bool $robotsIndex = true;

    public ?string $seoContent = null;

    public bool $isActive = true;

    public bool $isPopular = false;

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
        'isPopular' => 'boolean',
        'sortOrder' => 'integer|min:0',
    ];

    protected $messages = [
        'cateDesignId.required' => 'دسته‌بندی طرح را انتخاب کنید.',
        'name.required' => 'نام طرح الزامی است.',
    ];

    public function save(): void
    {
        $this->validate();

        $targetCategory = CateDesign::find($this->cateDesignId);
        if ($this->isActive && (! $targetCategory || ! $targetCategory->is_active)) {
            session()->flash('error', 'امکان انتساب طرح فعال به دسته‌بندی غیرفعال وجود ندارد.');

            return;
        }

        if ($this->editingId) {
            if (! $this->isActive) {
                $blocker = ProductPurchaseabilityService::designDeactivationBlocker($this->editingId);

                if ($blocker !== null) {
                    session()->flash('error', $blocker);

                    return;
                }
            } elseif (! app(DesignCatalogService::class)->isReadyForWorkspace((int) $this->editingId, (int) $this->cateDesignId, $this->isActive)) {
                // The same authoritative gate DesignWizard::save() applies. An
                // active design that cannot surface in the workspace is
                // saved-but-hidden, so it is refused instead. The rule itself
                // is never re-implemented here: it is read from the catalog
                // service, which is the single workspace-readiness definition.
                session()->flash('error', self::NOT_READY_FOR_WORKSPACE);

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
            'is_popular' => $this->isPopular,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            $design = Design::find($this->editingId);

            if (! $design) {
                session()->flash('error', 'طرح موردنظر یافت نشد');

                return;
            }

            $design->update($data);
            session()->flash('success', 'طرح با موفقیت ویرایش شد');
        } else {
            // A brand-new design owns no image yet, so it can never satisfy the
            // readiness rule. Persist it inactive and let the readiness-aware
            // wizard activate it once a compatible image exists, exactly like
            // DesignWizard::persistDesign() does.
            $data['is_active'] = false;
            Design::create($data);
            session()->flash('success', 'طرح با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new design. Always resets first: otherwise
     * opening the form right after editing another row keeps the previous
     * editingId and silently overwrites that design on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
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
        $this->isPopular = (bool) $design->is_popular;
        $this->sortOrder = (int) $design->sort_order;
        $this->showForm = true;
    }

    public function togglePopular(int $id): void
    {
        $design = Design::find($id);

        if (! $design) {
            session()->flash('error', 'طرح موردنظر یافت نشد');

            return;
        }

        $design->update([
            'is_popular' => ! $design->is_popular,
        ]);

        $status = $design->is_popular ? 'به طرح‌های پرطرفدار اضافه شد' : 'از طرح‌های پرطرفدار حذف شد';
        session()->flash('success', "«{$design->name}» با موفقیت {$status}");
    }

    public function delete(int $id): void
    {
        $design = Design::query()->find($id);

        if (! $design) {
            $msg = 'طرح موردنظر یافت نشد';
            session()->flash('error', $msg);
            $this->dispatch('elva-toast', message: $msg, type: 'error', title: 'خطا');

            return;
        }

        // 1. Guard against breaking purchasability of active products:
        // If this design is the ONLY purchasable design keeping an active product buyable,
        // deletion is blocked to prevent breaking customer-facing purchasing.
        $blocker = ProductPurchaseabilityService::designDeactivationBlocker($design->id);
        if ($blocker !== null) {
            session()->flash('error', $blocker);
            $this->dispatch('elva-toast', message: $blocker, type: 'error', title: 'خطا');

            return;
        }

        // 2. Collect image files and IDs owned by this design BEFORE database deletion
        $images = $design->images()->get(['id', 'image_path']);
        $imageIds = $images->pluck('id')->all();
        $imagePaths = $images->pluck('image_path')->filter()->unique()->values()->all();

        try {
            DB::transaction(function () use ($design, $imageIds) {
                // Delete compatibility records owned by these images
                if (! empty($imageIds)) {
                    DesignColorCompatibility::query()
                        ->whereIn('design_image_id', $imageIds)
                        ->delete();

                    // Delete the design images themselves
                    DesignImage::query()
                        ->whereIn('id', $imageIds)
                        ->delete();
                }

                // Delete the design record
                $design->delete();
            });
        } catch (\Throwable $e) {
            report($e);
            $errorMsg = 'خطایی در حذف طرح رخ داد. لطفاً دوباره تلاش کنید.';
            session()->flash('error', $errorMsg);
            $this->dispatch('elva-toast', message: $errorMsg, type: 'error', title: 'خطا');

            return;
        }

        // 3. Post-transaction file cleanup on the 'public' disk:
        // Database transactions cannot rollback filesystem operations, so filesystem
        // cleanup is executed only after the database transaction commits successfully.
        // StoredFileManager::deleteDesignImageFilesWhenUnreferenced ensures physical files
        // are only removed if no other DesignImage and no OrderItem still points to them.
        if (! empty($imagePaths)) {
            app(StoredFileManager::class)->deleteDesignImageFilesWhenUnreferenced($imagePaths);
        }

        // If the deleted design was currently being edited in the form, reset the form state
        if ($this->editingId === $id) {
            $this->resetForm();
            $this->showForm = false;
        }

        $successMsg = 'طرح و تمام تصاویر و سازگاری‌های مرتبط با آن با موفقیت حذف شدند';
        session()->flash('success', $successMsg);
        $this->dispatch('elva-toast', message: $successMsg, type: 'success', title: 'موفقیت‌آمیز');
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
        $this->isPopular = false;
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

        // Surface the workspace gate in the list: an active design that is not
        // ready (missing an active image allowed for an active color, or an
        // inactive category) never reaches the customizer even though the row
        // says "فعال". Showing it here is what makes the silent hide visible.
        $catalog = app(DesignCatalogService::class);
        $workspaceReady = [];

        foreach ($designs as $design) {
            $workspaceReady[$design->id] = $catalog->isReadyForWorkspace((int) $design->id);
        }

        return view('livewire.admin.design-manager', [
            'designs' => $designs,
            'workspaceReady' => $workspaceReady,
            'categories' => CateDesign::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ])->layout('layouts.admin')->title('مدیریت طرح‌ها');
    }
}
