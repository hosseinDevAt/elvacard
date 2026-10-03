<?php

namespace App\Livewire\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Color;
use App\Models\DesignColorCompatibility;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Models\ProductSlugHistory;
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

    public ?int $productCategoryId = null;

    public ?string $designConfig = null;

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $canonicalUrl = null;

    public bool $robotsIndex = true;

    public ?string $ogImage = null;

    public $ogImageUpload;

    public ?string $seoContent = null;

    public bool $isActive = true;

    /**
     * Transient pricing-model selection of the Store form («محصول عادی» /
     * «محصول متغیر»). It is never persisted: the database stays authoritative
     * (products.base_price for simple rows, ProductColorPrice for variables).
     */
    public string $pricingType = 'simple';

    public string $search = '';

    public string $typeFilter = '';

    public string $workflowFilter = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    public ?int $variantColorId = null;

    public ?int $variantPrice = null;

    public bool $variantIsActive = true;

    public $variantImageUploads = [];

    public ?int $editingVariantId = null;

    public bool $showVariantForm = false;

    public array $specifications = [];

    protected $rules = [
        'type' => 'required|in:bank,fuel,standard',
        'customizationWorkflow' => 'nullable|in:bank_card,fuel_card',
        'name' => 'required|string|min:1|max:255',
        'description' => 'nullable|string',
        'mainImage' => 'nullable|string|max:255',
        'mainImageUpload' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'basePrice' => 'nullable|integer|min:0',
        'productCategoryId' => 'nullable|integer|exists:product_categories,id',
        'designConfig' => 'nullable|json',
        'metaTitle' => 'nullable|string|max:255',
        'metaDescription' => 'nullable|string|max:255',
        'canonicalUrl' => 'nullable|url:http,https|max:255',
        'ogImage' => 'nullable|string|max:255',
        'ogImageUpload' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'seoContent' => 'nullable|string',
        'specifications' => 'nullable|array|max:50',
        'specifications.*.label' => 'nullable|string|max:120',
        'specifications.*.value' => 'nullable|string|max:500',
        'robotsIndex' => 'boolean',
        'isActive' => 'boolean',
    ];

    protected $variantRules = [
        'variantColorId' => 'required|integer|exists:colors,id',
        'variantPrice' => 'required|integer|min:0',
        'variantIsActive' => 'boolean',
        'variantImageUploads.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ];

    protected $variantMessages = [
        'variantColorId.required' => 'رنگ را انتخاب کنید.',
        'variantPrice.required' => 'قیمت را وارد کنید.',
        'variantPrice.integer' => 'قیمت باید عدد صحیح باشد.',
        'variantPrice.min' => 'قیمت نمی‌تواند منفی باشد.',
    ];

    public function save(): void
    {
        $this->customizationWorkflow = CustomizationWorkflowRegistry::normalizeWorkflow($this->customizationWorkflow);

        // Categories describe ordinary Store products only. A customizable card
        // (Bank/Fuel) is never a categorized commerce item, so a stale selection
        // carried from another row is dropped before validation.
        if ($this->customizationWorkflow !== null) {
            $this->productCategoryId = null;
        }

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

            foreach (ProductPurchaseabilityService::activationBlockers($this->editingId, CustomizationWorkflowEnum::BANK_CARD) as $blocker) {
                $this->addError('customizationWorkflow', $blocker);
            }

            if ($this->getErrorBag()->has('customizationWorkflow')) {
                return;
            }
        }

        if (
            $this->editingId !== null
            && $this->customizationWorkflow === null
            && $this->pricingType === 'simple'
            && ProductColorPrice::query()->where('product_id', $this->editingId)->exists()
        ) {
            $this->addError(
                'pricingType',
                'این محصول دارای رنگ و قیمت‌های متغیر ثبت‌شده است؛ برای تبدیل به محصول عادی ابتدا همه متغیرها را از بخش «متغیرهای محصول» حذف کنید.'
            );

            return;
        }

        if ($this->type === ProductTypeEnum::STANDARD->value && $this->isActive) {
            if ($this->pricingType === 'variable') {
                $hasActiveVariant = ProductColorPrice::query()
                    ->where('product_id', $this->editingId)
                    ->where('is_active', true)
                    ->exists();

                if (! $hasActiveVariant) {
                    $this->addError(
                        'pricingType',
                        'محصول متغیر فعال باید حداقل یک رنگ و قیمت فعال داشته باشد؛ ابتدا محصول را بدون فعال بودن ذخیره کنید، سپس از بخش «متغیرهای محصول» رنگ و قیمت فعال اضافه کنید و دوباره آن را فعال کنید.'
                    );

                    return;
                }
            } elseif ($this->basePrice === null) {
                $this->addError(
                    'basePrice',
                    'محصول استاندارد (عادی) فعال باید قیمت پایه داشته باشد؛ بدون قیمت پایه قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'
                );

                return;
            }
        }

        $previousMainImage = null;
        $previousOgImage = null;
        $previousSlug = null;

        if ($this->editingId) {
            $existing = Product::find($this->editingId);
            $previousMainImage = $existing?->main_image;
            $previousOgImage = $existing?->og_image;
            $previousSlug = $existing?->slug;
        }

        if ($this->mainImageUpload) {
            $this->mainImage = $this->mainImageUpload->store('products', 'public');
            $this->mainImageUpload = null;
        }

        if ($this->ogImageUpload) {
            $this->ogImage = $this->ogImageUpload->store('products', 'public');
            $this->ogImageUpload = null;
        }

        $slug = $this->uniqueSlug(
            $this->name,
            Product::class,
            $this->editingId,
            'product',
            fn (string $candidate): bool => ProductSlugHistory::query()->where('slug', $candidate)->exists(),
        );

        if ($this->editingId && $previousSlug !== null && $previousSlug !== $slug) {
            ProductSlugHistory::query()
                ->firstOrCreate(['slug' => $previousSlug], ['product_id' => $this->editingId]);
        }

        // A variable Store product is priced exclusively by its active
        // ProductColorPrice rows. Persisting a base price for it would leave a
        // second, admin-unaware price source behind: CartService seeds
        // unit_price from base_price whenever a request omits color_id, so the
        // stale value would still be charged and would still be published as
        // AggregateOffer.lowPrice in the catalog JSON-LD.
        if ($this->pricingType === 'variable') {
            $this->basePrice = null;
        }

        $cleanedSpecs = collect($this->specifications)
            ->map(function ($spec) {
                if (! is_array($spec)) {
                    return null;
                }

                return [
                    'label' => trim((string) ($spec['label'] ?? $spec['name'] ?? '')),
                    'value' => trim((string) ($spec['value'] ?? '')),
                ];
            })
            ->filter(fn ($spec) => $spec !== null && ($spec['label'] !== '' || $spec['value'] !== ''))
            ->values()
            ->all();

        $data = [
            'type' => $this->type,
            'customization_workflow' => $this->customizationWorkflow ?: null,
            'name' => $this->name,
            'slug' => $slug,
            'description' => $this->description ?: null,
            'main_image' => $this->mainImage ?: null,
            'base_price' => $this->basePrice,
            'product_category_id' => $this->productCategoryId,
            'design_config' => $this->designConfig ? json_decode($this->designConfig, true) : null,
            'meta_title' => $this->metaTitle ?: null,
            'meta_description' => $this->metaDescription ?: null,
            'canonical_url' => $this->canonicalUrl ?: null,
            'og_image' => $this->ogImage ?: null,
            'seo_content' => $this->seoContent ?: null,
            'specifications' => $cleanedSpecs !== [] ? $cleanedSpecs : null,
            'robots_index' => $this->robotsIndex,
            'is_active' => $this->isActive,
        ];

        $createdProductId = null;

        if ($this->editingId) {
            $product = Product::find($this->editingId);

            if (! $product) {
                session()->flash('error', 'محصول موردنظر یافت نشد');

                return;
            }

            $product->update($data);
            session()->flash('success', 'محصول با موفقیت ویرایش شد');
        } else {
            /** @var Product $createdProduct */
            $createdProduct = Product::create($data);
            $createdProductId = $createdProduct->id;
            session()->flash('success', 'محصول با موفقیت اضافه شد');
        }

        $this->cleanupReplacedProductImages(
            $previousMainImage,
            $previousOgImage,
            $this->mainImage,
            $this->ogImage,
        );

        // A variable Store product needs a real product id before any color
        // price exists, so creating one opens the edit form right into the
        // variant-pricing section instead of dumping the admin back to the
        // list. edit() derives the pricing model from existing rows (which are
        // none yet), so the variable model is restored explicitly afterwards.
        if (
            $createdProductId !== null
            && $this->type === ProductTypeEnum::STANDARD->value
            && $this->pricingType === 'variable'
        ) {
            $this->edit($createdProductId);
            $this->pricingType = 'variable';

            return;
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new product. Always resets first: otherwise
     * opening the form right after editing another row keeps the previous
     * editingId and silently overwrites that product on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Guards the pricing-model toggle while editing: a Store product that
     * already carries color-price rows must not silently lose them when the
     * admin switches to «محصول عادی». The toggle is rejected, the state stays
     * variable, and the rows are preserved; the admin must delete every variant
     * first through the existing guarded delete actions.
     */
    public function updatedPricingType(string $value): void
    {
        if ($value !== 'simple' || $this->editingId === null || $this->customizationWorkflow !== null) {
            return;
        }

        if (ProductColorPrice::query()->where('product_id', $this->editingId)->exists()) {
            $this->pricingType = 'variable';
            $this->addError(
                'pricingType',
                'این محصول دارای رنگ و قیمت‌های متغیر ثبت‌شده است؛ برای تبدیل به محصول عادی ابتدا همه متغیرها را از بخش «متغیرهای محصول» حذف کنید. هیچ داده‌ای به‌صورت خودکار حذف نمی‌شود.'
            );
        }
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
        $this->productCategoryId = $product->product_category_id !== null ? (int) $product->product_category_id : null;
        $this->designConfig = $product->design_config ? json_encode($product->design_config, JSON_UNESCAPED_UNICODE) : null;
        $this->metaTitle = $product->meta_title;
        $this->metaDescription = $product->meta_description;
        $this->canonicalUrl = $product->canonical_url;
        $this->robotsIndex = (bool) $product->robots_index;
        $this->ogImage = $product->og_image;
        $this->seoContent = $product->seo_content;
        $this->isActive = (bool) $product->is_active;
        $this->specifications = $product->specificationsList();
        $this->pricingType = $this->customizationWorkflow === null
            && ProductColorPrice::query()->where('product_id', $product->id)->exists()
                ? 'variable'
                : 'simple';
        $this->resetVariantForm();
        $this->showVariantForm = false;
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

        // The product_images rows disappear through the FK cascade, which never
        // fires Eloquent events, so the gallery files must be cleaned explicitly
        // while the rows still exist to collect their paths.
        $galleryPaths = $product->images()->pluck('image_path')->all();
        $product->images()->delete();
        ProductImage::deleteFilesWhenUnreferenced($galleryPaths);

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
        $this->productCategoryId = null;
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
        $this->pricingType = 'simple';
        $this->specifications = [];
        $this->resetVariantForm();
        $this->showVariantForm = false;
    }

    public function addSpecificationRow(): void
    {
        $this->specifications[] = ['label' => '', 'value' => ''];
    }

    public function removeSpecificationRow(int $index): void
    {
        unset($this->specifications[$index]);
        $this->specifications = array_values($this->specifications);
    }

    public function moveSpecificationUp(int $index): void
    {
        if ($index > 0 && isset($this->specifications[$index])) {
            $temp = $this->specifications[$index - 1];
            $this->specifications[$index - 1] = $this->specifications[$index];
            $this->specifications[$index] = $temp;
            $this->specifications = array_values($this->specifications);
        }
    }

    public function moveSpecificationDown(int $index): void
    {
        if ($index < count($this->specifications) - 1 && isset($this->specifications[$index])) {
            $temp = $this->specifications[$index + 1];
            $this->specifications[$index + 1] = $this->specifications[$index];
            $this->specifications[$index] = $temp;
            $this->specifications = array_values($this->specifications);
        }
    }

    /**
     * Store-product pricing is managed inline from the Store → Products
     * surface only: an ordinary Store product (customization_workflow = null).
     * Bank/Fuel cards keep their color pricing in «قیمت‌گذاری کارت‌ها».
     */
    private function editableStoreProduct(): ?Product
    {
        if ($this->editingId === null) {
            return null;
        }

        $product = Product::query()->find($this->editingId);

        if ($product === null || $product->getRawOriginal('customization_workflow') !== null) {
            return null;
        }

        return $product;
    }

    /**
     * Resolves a variant price row only when it belongs to the Store product
     * currently open in the form. Every variant action goes through this, so a
     * crafted Livewire call can never re-parent another product's price row
     * into this one.
     */
    private function scopedStoreVariant(int $id): ?ProductColorPrice
    {
        $product = $this->editableStoreProduct();

        if ($product === null) {
            return null;
        }

        return ProductColorPrice::query()
            ->where('product_id', $product->id)
            ->whereKey($id)
            ->first();
    }

    /**
     * Same ownership contract as scopedStoreVariant(), applied to variant
     * gallery images: an image is only reachable while its own product is open.
     */
    private function scopedStoreVariantImage(int $id): ?ProductImage
    {
        $product = $this->editableStoreProduct();

        if ($product === null) {
            return null;
        }

        return ProductImage::query()
            ->where('product_id', $product->id)
            ->whereKey($id)
            ->first();
    }

    public function openVariantForm(): void
    {
        if ($this->editableStoreProduct() === null) {
            session()->flash('error', 'برای مدیریت قیمت متغیر، ابتدا محصول فروشگاهی را ذخیره و سپس ویرایش کنید.');

            return;
        }

        $this->resetVariantForm();
        $this->showVariantForm = true;
    }

    public function editVariant(int $id): void
    {
        $priceItem = $this->scopedStoreVariant($id);

        if ($priceItem === null) {
            session()->flash('error', 'این قیمت متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');

            return;
        }

        $this->editingVariantId = $id;
        $this->variantColorId = (int) $priceItem->color_id;
        $this->variantPrice = (int) $priceItem->price;
        $this->variantIsActive = (bool) $priceItem->is_active;
        $this->showVariantForm = true;
    }

    public function saveVariant(): void
    {
        $this->validate($this->variantRules, $this->variantMessages);

        $product = $this->editableStoreProduct();

        if ($product === null) {
            session()->flash('error', 'ابتدا محصول فروشگاهی را ذخیره کنید؛ سپس رنگ و قیمت متغیر آن را تعریف کنید.');

            return;
        }

        $duplicate = ProductColorPrice::query()
            ->where('product_id', $product->id)
            ->where('color_id', $this->variantColorId)
            ->when($this->editingVariantId, fn ($query) => $query->where('id', '!=', $this->editingVariantId))
            ->exists();

        if ($duplicate) {
            session()->flash('error', 'قیمت‌گذاری برای این محصول و رنگ از قبل ثبت شده است.');

            return;
        }

        $priceRowBlocker = ProductPurchaseabilityService::priceRowChangeBlocker(
            $product->id,
            $this->editingVariantId,
            $this->variantColorId,
            $this->variantIsActive,
        );

        if ($priceRowBlocker !== null) {
            session()->flash('error', $priceRowBlocker);

            return;
        }

        $data = [
            'product_id' => $product->id,
            'color_id' => $this->variantColorId,
            'price' => $this->variantPrice,
            'is_active' => $this->variantIsActive,
        ];

        $priceItem = null;
        $previousColorId = null;

        if ($this->editingVariantId) {
            $priceItem = $this->scopedStoreVariant($this->editingVariantId);

            if ($priceItem === null) {
                session()->flash('error', 'این قیمت متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');
                $this->resetVariantForm();
                $this->showVariantForm = false;

                return;
            }

            $previousColorId = $priceItem->color_id !== null ? (int) $priceItem->color_id : null;
            $priceItem->update($data);
            session()->flash('success', 'قیمت متغیر با موفقیت ویرایش شد');
        } else {
            $priceItem = ProductColorPrice::create($data);
            session()->flash('success', 'قیمت متغیر با موفقیت اضافه شد');
        }

        if ($previousColorId !== null && (int) $previousColorId !== (int) $this->variantColorId) {
            ProductImage::query()
                ->where('product_id', $product->id)
                ->where('color_id', $previousColorId)
                ->update(['color_id' => $this->variantColorId]);
        }

        $this->persistVariantUploadedImages();

        $this->resetVariantForm();
        $this->showVariantForm = false;
    }

    public function deleteVariant(int $id): void
    {
        $priceItem = $this->scopedStoreVariant($id);

        if ($priceItem === null) {
            session()->flash('error', 'این قیمت متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');

            return;
        }

        $priceRowBlocker = ProductPurchaseabilityService::priceRowChangeBlocker(
            $priceItem->product_id,
            $id,
            null,
            false,
        );

        if ($priceRowBlocker !== null) {
            session()->flash('error', $priceRowBlocker);

            return;
        }

        $priceItem->delete();

        $images = ProductImage::query()
            ->where('product_id', $priceItem->product_id)
            ->where('color_id', $priceItem->color_id)
            ->get();

        foreach ($images as $image) {
            $this->deleteVariantImageRow($image);
        }

        session()->flash('success', 'قیمت متغیر با موفقیت حذف شد');
    }

    public function setPrimaryVariantImage(int $id): void
    {
        $image = $this->scopedStoreVariantImage($id);

        if ($image === null) {
            session()->flash('error', 'تصویر موردنظر متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');

            return;
        }

        ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('color_id', $image->color_id)
            ->update(['is_primary' => false]);

        $image->update(['is_primary' => true]);
        session()->flash('success', 'تصویر اصلی با موفقیت تعیین شد');
    }

    public function deleteVariantImage(int $id): void
    {
        $image = $this->scopedStoreVariantImage($id);

        if ($image === null) {
            session()->flash('error', 'تصویر موردنظر متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');

            return;
        }

        $this->deleteVariantImageRow($image);
        session()->flash('success', 'تصویر با موفقیت حذف شد');
    }

    /**
     * Stores every uploaded variant image under the product's color, ordered
     * after any existing gallery. The first upload becomes the primary image
     * only when the variant has none yet.
     */
    private function persistVariantUploadedImages(): void
    {
        $uploads = array_values(array_filter(
            is_array($this->variantImageUploads) ? $this->variantImageUploads : [],
            fn ($file) => $file !== null,
        ));

        if ($uploads === []) {
            return;
        }

        $hasPrimary = ProductImage::query()
            ->where('product_id', $this->editingId)
            ->where('color_id', $this->variantColorId)
            ->where('is_primary', true)
            ->exists();

        $nextSortOrder = (int) ProductImage::query()
            ->where('product_id', $this->editingId)
            ->where('color_id', $this->variantColorId)
            ->max('sort_order');

        foreach ($uploads as $index => $file) {
            ProductImage::query()->create([
                'product_id' => $this->editingId,
                'color_id' => $this->variantColorId,
                'image_path' => $file->store('products', 'public'),
                'sort_order' => $nextSortOrder + $index + 1,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);
        }
    }

    /**
     * Removes a variant image row (the model's deleted hook clears its physical
     * file once no other product / variant image, main image or og image still
     * references the path), then guarantees the variant keeps exactly one
     * primary image when images remain.
     */
    private function deleteVariantImageRow(ProductImage $image): void
    {
        $image->delete();

        $remaining = ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('color_id', $image->color_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (! $remaining->contains(fn ($item) => (bool) $item->is_primary) && $remaining->isNotEmpty()) {
            $remaining->first()->update(['is_primary' => true]);
        }
    }

    public function getStoreColorOptionsProperty(): array
    {
        $items = Color::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'is_active']);

        return array_merge(
            $items->filter(fn ($color) => (bool) $color->is_active)
                ->map(fn ($color) => ['id' => $color->id, 'name' => $color->name])
                ->values()
                ->all(),
            $this->editingVariantId
                ? $items->filter(fn ($color) => ! $color->is_active && (int) $color->id === (int) $this->variantColorId)
                    ->map(fn ($color) => ['id' => $color->id, 'name' => $color->name.' (غیرفعال)'])
                    ->values()
                    ->all()
                : []
        );
    }

    public function resetVariantForm(): void
    {
        $this->variantColorId = null;
        $this->variantPrice = null;
        $this->variantIsActive = true;
        $this->variantImageUploads = [];
        $this->editingVariantId = null;
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
            ->with('category:id,name')
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

        $storeProduct = $this->editableStoreProduct();

        $storeVariants = $storeProduct !== null
            ? ProductColorPrice::query()
                ->with('color', 'images')
                ->where('product_id', $storeProduct->id)
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('livewire.admin.product-manager', [
            'products' => $products,
            'purchasableIds' => $purchasableIds,
            'storeVariants' => $storeVariants,
            'storeColorOptions' => $this->storeColorOptions,
            'fuelPreparation' => $this->fuelPreparation,
            'typeOptions' => ProductTypeEnum::options(),
            'categoryOptions' => ProductCategory::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
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
