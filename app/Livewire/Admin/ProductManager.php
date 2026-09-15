<?php

namespace App\Livewire\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\DesignColorCompatibility;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\Customization\FuelCardActivationService;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\DesignCatalogService;
use App\Services\StoredFileManager;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithFileUploads;
    use WithPagination;

    public string $type = 'bank';

    public ?string $customizationWorkflow = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $mainImage = null;

    public $mainImageUpload;

    public ?int $basePrice = null;

    public bool $supportsChipSelection = false;

    public ?string $designConfig = null;

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $canonicalUrl = null;

    public bool $robotsIndex = true;

    public ?string $ogImage = null;

    public $ogImageUpload;

    public ?string $seoContent = null;

    public bool $isActive = true;

    public string $search = '';

    public string $typeFilter = '';

    public string $workflowFilter = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'type' => 'required|in:bank,fuel,standard',
        'customizationWorkflow' => 'nullable|in:bank_card,fuel_card',
        'name' => 'required|string|min:1|max:255',
        'description' => 'nullable|string',
        'mainImage' => 'nullable|string|max:255',
        'mainImageUpload' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        'basePrice' => 'nullable|integer|min:0',
        'supportsChipSelection' => 'boolean',
        'designConfig' => 'nullable|json',
        'metaTitle' => 'nullable|string|max:255',
        'metaDescription' => 'nullable|string|max:255',
        'canonicalUrl' => 'nullable|string|max:255',
        'ogImage' => 'nullable|string|max:255',
        'ogImageUpload' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
        'seoContent' => 'nullable|string',
        'robotsIndex' => 'boolean',
        'isActive' => 'boolean',
    ];

    public function save(): void
    {
        $this->validate();

        if (! CustomizationWorkflowRegistry::typeIsConsistent($this->type, $this->customizationWorkflow)) {
            $this->addError(
                'customizationWorkflow',
                'نوع محصول و فرآیند شخصی‌سازی باید هماهنگ باشند: محصول استاندارد بدون شخصی‌سازی، کارت بانکی با فرآیند کارت بانکی، کارت سوخت با فرآیند کارت سوخت.'
            );

            return;
        }

        if (
            $this->customizationWorkflow === CustomizationWorkflowEnum::FUEL_CARD->value
            && $this->editingId
            && ProductColorPrice::fuelActiveCount($this->editingId) > 1
        ) {
            $this->addError(
                'customizationWorkflow',
                'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد؛ ابتدا رنگ‌های اضافی را غیرفعال کنید.'
            );

            return;
        }

        if ($this->customizationWorkflow === CustomizationWorkflowEnum::FUEL_CARD->value && $this->isActive) {
            if (! CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::FUEL_CARD)) {
                $this->addError(
                    'customizationWorkflow',
                    'سرویس کارت سوخت هنوز فعال نشده است؛ محصول قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'
                );

                return;
            }

            foreach (FuelCardActivationService::activationBlockers($this->editingId) as $blocker) {
                $this->addError('customizationWorkflow', $blocker);
            }

            if ($this->getErrorBag()->has('customizationWorkflow')) {
                return;
            }
        }

        if ($this->customizationWorkflow === CustomizationWorkflowEnum::BANK_CARD->value && $this->isActive) {
            if (! CustomizationWorkflowRegistry::isActive(CustomizationWorkflowEnum::BANK_CARD)) {
                $this->addError(
                    'customizationWorkflow',
                    'سرویس کارت بانکی هنوز فعال نشده است؛ محصول قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'
                );

                return;
            }

            foreach (ProductPurchaseabilityService::activationBlockers($this->editingId) as $blocker) {
                $this->addError('customizationWorkflow', $blocker);
            }

            if ($this->getErrorBag()->has('customizationWorkflow')) {
                return;
            }
        }

        if ($this->type === ProductTypeEnum::STANDARD->value && $this->isActive && $this->basePrice === null) {
            $this->addError(
                'basePrice',
                'محصول استاندارد فعال باید قیمت پایه داشته باشد؛ بدون قیمت پایه قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'
            );

            return;
        }

        $previousMainImage = null;
        $previousOgImage = null;

        if ($this->editingId) {
            $existing = Product::find($this->editingId);
            $previousMainImage = $existing?->main_image;
            $previousOgImage = $existing?->og_image;
        }

        if ($this->mainImageUpload) {
            $this->mainImage = $this->mainImageUpload->store('products', 'public');
            $this->mainImageUpload = null;
        }

        if ($this->ogImageUpload) {
            $this->ogImage = $this->ogImageUpload->store('products', 'public');
            $this->ogImageUpload = null;
        }

        $slug = $this->uniqueSlug($this->name, Product::class, $this->editingId, 'product');

        $data = [
            'type' => $this->type,
            'customization_workflow' => $this->customizationWorkflow ?: null,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description ?: null,
            'main_image' => $this->mainImage ?: null,
            'base_price' => $this->basePrice,
            'supports_chip_selection' => $this->supportsChipSelection,
            'design_config' => $this->designConfig ? json_decode($this->designConfig, true) : null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'canonical_url' => $this->canonicalUrl ?: null,
            'og_image' => $this->ogImage ?: null,
            'seo_content' => $this->seoContent ?: null,
            'robots_index' => $this->robotsIndex,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Product::find($this->editingId)->update($data);
            session()->flash('success', 'محصول با موفقیت ویرایش شد');
        } else {
            Product::create($data);
            session()->flash('success', 'محصول با موفقیت اضافه شد');
        }

        $this->cleanupReplacedProductImages(
            $previousMainImage,
            $previousOgImage,
            $this->mainImage,
            $this->ogImage,
        );

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $product = Product::find($id);

        if (! $product) {
            session()->flash('error', 'محصول موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->type = $product->type->value;
        $this->customizationWorkflow = $product->customization_workflow?->value ?? null;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->mainImage = $product->main_image;
        $this->basePrice = $product->base_price;
        $this->supportsChipSelection = (bool) $product->supports_chip_selection;
        $this->designConfig = $product->design_config ? json_encode($product->design_config, JSON_UNESCAPED_UNICODE) : null;
        $this->metaTitle = $product->meta_title;
        $this->metaDescription = $product->meta_description;
        $this->canonicalUrl = $product->canonical_url;
        $this->robotsIndex = (bool) $product->robots_index;
        $this->ogImage = $product->og_image;
        $this->seoContent = $product->seo_content;
        $this->isActive = (bool) $product->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $product = Product::find($id);

        if (! $product) {
            session()->flash('error', 'محصول موردنظر یافت نشد');

            return;
        }

        if ($product->orderItems()->exists()) {
            session()->flash('error', 'این محصول در سفارش‌های ثبت‌شده استفاده شده است و قابل حذف نیست.');

            return;
        }

        $product->delete();

        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            [$product->main_image, $product->og_image],
            fn (string $path): bool => Product::query()
                ->where(function ($query) use ($path) {
                    $query->where('main_image', $path)->orWhere('og_image', $path);
                })
                ->exists(),
        );

        session()->flash('success', 'محصول با موفقیت حذف شد');
    }

    /**
     * Deletes the image files that an edit replaced, as long as no other
     * product still references them. A replaced path that is still shared is
     * preserved, and files never referenced by a saved product are untouched.
     */
    private function cleanupReplacedProductImages(?string $previousMainImage, ?string $previousOgImage, ?string $currentMainImage, ?string $currentOgImage): void
    {
        $stale = array_values(array_filter([
            $previousMainImage !== $currentMainImage ? $previousMainImage : null,
            $previousOgImage !== $currentOgImage ? $previousOgImage : null,
        ]));

        if ($stale === []) {
            return;
        }

        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            $stale,
            fn (string $path): bool => Product::query()
                ->where(function ($query) use ($path) {
                    $query->where('main_image', $path)->orWhere('og_image', $path);
                })
                ->exists(),
        );
    }

    public function resetForm(): void
    {
        $this->type = 'bank';
        $this->customizationWorkflow = null;
        $this->name = '';
        $this->description = null;
        $this->mainImage = null;
        $this->mainImageUpload = null;
        $this->basePrice = null;
        $this->supportsChipSelection = false;
        $this->designConfig = null;
        $this->metaTitle = null;
        $this->metaDescription = null;
        $this->canonicalUrl = null;
        $this->robotsIndex = true;
        $this->ogImage = null;
        $this->ogImageUpload = null;
        $this->seoContent = null;
        $this->isActive = true;
        $this->editingId = null;
    }

    /**
     * Fuel activation checklist for the admin form. Each item reflects the
     * database (`ProductColorPrice` row and purchasable-design catalog), never
     * the submitted color count or design ids; for a new product the items are
     * merely listed as required work (nothing can be checked before save).
     */
    public function getFuelPreparationProperty(): array
    {
        $items = [
            ['label' => 'انتخاب یک رنگ فعال', 'ok' => false],
            ['label' => 'تعریف قیمت', 'ok' => false],
            ['label' => 'انتخاب طرح کارت', 'ok' => false],
        ];

        if ($this->editingId === null) {
            return $items;
        }

        $activeColorPrices = ProductColorPrice::query()
            ->where('product_id', $this->editingId)
            ->where('is_active', true)
            ->get(['color_id']);

        $items[0]['ok'] = $activeColorPrices->isNotEmpty();
        $items[1]['ok'] = $activeColorPrices->isNotEmpty();

        if ($activeColorPrices->count() === 1) {
            $colorId = (int) $activeColorPrices->first()->color_id;

            $allowedImageIds = DesignColorCompatibility::query()
                ->where('card_color_id', $colorId)
                ->where('is_allowed', true)
                ->pluck('design_image_id');

            $items[2]['ok'] = app(DesignCatalogService::class)->hasPurchasableDesign(null, $allowedImageIds);
        }

        return $items;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedWorkflowFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $products = Product::query()
            ->withCount('colorPrices')
            ->withMin('activeColorPrices', 'price')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->typeFilter !== '', fn ($query) => $query->where('type', $this->typeFilter))
            ->when($this->workflowFilter === 'none', fn ($query) => $query->whereNull('customization_workflow'))
            ->when(
                in_array($this->workflowFilter, [
                    CustomizationWorkflowEnum::BANK_CARD->value,
                    CustomizationWorkflowEnum::FUEL_CARD->value,
                ], true),
                fn ($query) => $query->where('customization_workflow', $this->workflowFilter)
            )
            ->orderByDesc('id')
            ->paginate(15);

        $pageProductIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();

        $purchasableIds = Product::query()
            ->whereIn('id', $pageProductIds)
            ->purchasable()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('livewire.admin.product-manager', [
            'products' => $products,
            'purchasableIds' => $purchasableIds,
            'typeOptions' => ProductTypeEnum::options(),
            'typeFilterOptions' => array_merge(
                [['value' => '', 'label' => 'همه انواع']],
                array_map(
                    fn (ProductTypeEnum $case) => ['value' => $case->value, 'label' => $case->faLabel()],
                    ProductTypeEnum::cases()
                ),
            ),
            'workflowFilterOptions' => [
                ['value' => '', 'label' => 'همه فرآیندها'],
                ['value' => 'none', 'label' => 'بدون شخصی‌سازی'],
                ['value' => CustomizationWorkflowEnum::BANK_CARD->value, 'label' => CustomizationWorkflowEnum::BANK_CARD->faLabel()],
                ['value' => CustomizationWorkflowEnum::FUEL_CARD->value, 'label' => CustomizationWorkflowEnum::FUEL_CARD->faLabel()],
            ],
            'workflowOptions' => [
                ['value' => '', 'label' => 'بدون شخصی‌سازی'],
                ['value' => CustomizationWorkflowEnum::BANK_CARD->value, 'label' => CustomizationWorkflowEnum::BANK_CARD->faLabel()],
                ['value' => CustomizationWorkflowEnum::FUEL_CARD->value, 'label' => CustomizationWorkflowEnum::FUEL_CARD->faLabel()],
            ],
        ])->layout('layouts.admin')->title('مدیریت محصولات');
    }
}
