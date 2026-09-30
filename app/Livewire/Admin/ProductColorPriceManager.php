<?php

namespace App\Livewire\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductColorPriceManager extends Component
{
    use AuthorizesAdminActions;
    use WithFileUploads;
    use WithPagination;

    public ?int $productId = null;

    public ?int $colorId = null;

    public ?int $price = null;

    public bool $isActive = true;

    public $imageUploads = [];

    public string $search = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'productId' => 'required|integer|exists:products,id',
        'colorId' => 'required|integer|exists:colors,id',
        'price' => 'required|integer|min:0',
        'isActive' => 'boolean',
        'imageUploads.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ];

    protected $messages = [
        'productId.required' => 'محصول را انتخاب کنید.',
        'colorId.required' => 'رنگ را انتخاب کنید.',
        'price.required' => 'قیمت را وارد کنید.',
        'price.integer' => 'قیمت باید عدد صحیح باشد.',
        'price.min' => 'قیمت نمی‌تواند منفی باشد.',
    ];

    public function mount(): void
    {
        $product = request()->query('product');

        // Only Bank/Fuel card products belong to this surface. A crafted query
        // string pointing at an ordinary Store product must never select it.
        if ($product !== null && filter_var($product, FILTER_VALIDATE_INT) !== false) {
            $resolved = Product::query()->find((int) $product);

            if ($resolved !== null && $this->isCardWorkflow($resolved)) {
                $this->productId = (int) $product;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $product = $this->editableCardProduct();

        if ($product === null) {
            session()->flash('error', 'مدیریت قیمت رنگ فقط برای کارت‌های بانکی و سوخت انجام می‌شود؛ قیمت محصولات فروشگاهی را از «مدیریت محصولات» تنظیم کنید.');

            return;
        }

        $duplicate = ProductColorPrice::query()
            ->where('product_id', $this->productId)
            ->where('color_id', $this->colorId)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($duplicate) {
            session()->flash('error', 'قیمت‌گذاری برای این محصول و رنگ از قبل ثبت شده است.');

            return;
        }

        // The row being edited must belong to the selected card product, so a
        // crafted call cannot re-parent another product's row into this one.
        $currentColorPrice = $this->editingId
            ? $this->scopedCardVariant($this->editingId)
            : null;

        if ($this->editingId !== null && $currentColorPrice === null) {
            session()->flash('error', 'این قیمت متعلق به محصول در حال ویرایش نیست؛ عملیات متوقف شد.');

            return;
        }

        $workflowRaw = (string) $product->getRawOriginal('customization_workflow');
        $isFuelCard = $workflowRaw === CustomizationWorkflowEnum::FUEL_CARD->value;

        if (
            $this->isActive
            && $isFuelCard
            && ProductColorPrice::fuelActiveCount($this->productId, $this->editingId) >= 1
        ) {
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        if (
            ! $this->isActive
            && $isFuelCard
            && $currentColorPrice?->is_active
            && ProductColorPrice::fuelActiveCount($this->productId, $this->editingId) === 0
        ) {
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        $priceRowBlocker = ProductPurchaseabilityService::priceRowChangeBlocker(
            $this->productId,
            $this->editingId,
            $this->colorId,
            $this->isActive,
        );

        if ($priceRowBlocker !== null) {
            session()->flash('error', $priceRowBlocker);

            return;
        }

        $data = [
            'product_id' => $this->productId,
            'color_id' => $this->colorId,
            'price' => $this->price,
            'is_active' => $this->isActive,
        ];

        $priceItem = null;
        $previousColorId = null;

        if ($this->editingId) {
            // Already resolved and ownership-checked above.
            $previousColorId = $currentColorPrice->color_id !== null ? (int) $currentColorPrice->color_id : null;
            $currentColorPrice->update($data);
            $priceItem = $currentColorPrice;
            session()->flash('success', 'قیمت با موفقیت ویرایش شد');
        } else {
            $priceItem = ProductColorPrice::create($data);
            session()->flash('success', 'قیمت با موفقیت اضافه شد');
        }

        if ($previousColorId !== null && (int) $previousColorId !== (int) $this->colorId) {
            ProductImage::query()
                ->where('product_id', $this->productId)
                ->where('color_id', $previousColorId)
                ->update(['color_id' => $this->colorId]);
        }

        $this->persistUploadedImages($priceItem->id);

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new price row. Always resets first: otherwise
     * opening the form right after editing another row keeps the previous
     * editingId and silently overwrites that row on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Card pricing is owned by exactly one Bank/Fuel product. A row is only
     * reachable while its own product is the one selected in the form, so a
     * crafted Livewire call can never re-parent another product's price row
     * into this one, nor mutate a product the form has not opened.
     */
    private function editableCardProduct(): ?Product
    {
        if ($this->productId === null) {
            return null;
        }

        $product = Product::query()->find($this->productId);

        if ($product === null || ! $this->isCardWorkflow($product)) {
            return null;
        }

        return $product;
    }

    private function scopedCardVariant(int $id): ?ProductColorPrice
    {
        $priceItem = ProductColorPrice::query()->whereKey($id)->first();

        if ($priceItem === null) {
            return null;
        }

        $product = $this->editableCardProduct();

        if ($product === null) {
            return null;
        }

        return (int) $priceItem->product_id === (int) $product->id ? $priceItem : null;
    }

    private function scopedCardVariantImage(int $id): ?ProductImage
    {
        $image = ProductImage::query()->whereKey($id)->first();

        if ($image === null) {
            return null;
        }

        $product = $this->editableCardProduct();

        if ($product === null) {
            return null;
        }

        return (int) $image->product_id === (int) $product->id ? $image : null;
    }

    /**
     * Card pricing covers Bank and Fuel cards only; Store products keep their
     * variants in the Store → Products surface.
     */
    private function isCardWorkflow(Product $product): bool
    {
        return in_array(
            (string) $product->getRawOriginal('customization_workflow'),
            [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value],
            true,
        );
    }

    /**
     * The cross-product list renders every card's rows, so a row can be listed
     * long before its product is opened. Choosing a row is what establishes the
     * mutation context; it is not itself a change to the row.
     */
    public function selectProduct(int $id): void
    {
        $product = Product::query()->find($id);

        if ($product === null || ! $this->isCardWorkflow($product)) {
            session()->flash('error', 'مدیریت قیمت رنگ فقط برای کارت‌های بانکی و سوخت انجام می‌شود؛ قیمت محصولات فروشگاهی را از «مدیریت محصولات» تنظیم کنید.');

            return;
        }

        $this->productId = (int) $product->id;
    }

    public function edit(int $id): void
    {
        $priceItem = $this->scopedCardVariant($id);

        if (! $priceItem) {
            session()->flash('error', 'قیمت موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->productId = (int) $priceItem->product_id;
        $this->colorId = (int) $priceItem->color_id;
        $this->price = (int) $priceItem->price;
        $this->isActive = (bool) $priceItem->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $priceItem = $this->scopedCardVariant($id);

        if (! $priceItem) {
            session()->flash('error', 'قیمت موردنظر یافت نشد');

            return;
        }

        if (
            (string) $priceItem->product->getRawOriginal('customization_workflow') === CustomizationWorkflowEnum::FUEL_CARD->value
            && $priceItem->is_active
            && ProductColorPrice::fuelActiveCount($priceItem->product_id, $id) === 0
        ) {
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

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
            $this->deleteImageRow($image);
        }

        session()->flash('success', 'قیمت با موفقیت حذف شد');
    }

    public function setPrimaryImage(int $id): void
    {
        $image = $this->scopedCardVariantImage($id);

        if (! $image) {
            session()->flash('error', 'تصویر موردنظر یافت نشد');

            return;
        }

        ProductImage::query()
            ->where('product_id', $image->product_id)
            ->where('color_id', $image->color_id)
            ->update(['is_primary' => false]);

        $image->update(['is_primary' => true]);
        session()->flash('success', 'تصویر اصلی با موفقیت تعیین شد');
    }

    public function deleteImage(int $id): void
    {
        $image = $this->scopedCardVariantImage($id);

        if (! $image) {
            session()->flash('error', 'تصویر موردنظر یافت نشد');

            return;
        }

        $this->deleteImageRow($image);
        session()->flash('success', 'تصویر با موفقیت حذف شد');
    }

    /**
     * Stores every uploaded variant image under the product's color, ordered
     * after any existing gallery. The first upload becomes the primary image
     * only when the variant has none yet.
     */
    private function persistUploadedImages(int $priceItemId): void
    {
        $priceItem = ProductColorPrice::query()->find($priceItemId);

        if ($priceItem === null) {
            return;
        }

        $uploads = array_values(array_filter(
            is_array($this->imageUploads) ? $this->imageUploads : [],
            fn ($file) => $file !== null,
        ));

        if ($uploads === []) {
            return;
        }

        $hasPrimary = ProductImage::query()
            ->where('product_id', $this->productId)
            ->where('color_id', $this->colorId)
            ->where('is_primary', true)
            ->exists();

        $nextSortOrder = (int) ProductImage::query()
            ->where('product_id', $this->productId)
            ->where('color_id', $this->colorId)
            ->max('sort_order');

        foreach ($uploads as $index => $file) {
            ProductImage::query()->create([
                'product_id' => $this->productId,
                'color_id' => $priceItem->color_id,
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
    private function deleteImageRow(ProductImage $image): void
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

    public function getProductOptionsProperty(): array
    {
        // Only Bank/Fuel card products may be priced here. Ordinary Store
        // products keep their pricing inside the Store → Products surface.
        $items = Product::query()
            ->whereNotNull('customization_workflow')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        return array_merge(
            $items->filter(fn ($product) => (bool) $product->is_active)
                ->map(fn ($product) => ['id' => $product->id, 'name' => $product->name])
                ->values()
                ->all(),
            $this->editingId
                ? $items->filter(fn ($product) => ! $product->is_active && (int) $product->id === (int) $this->productId)
                    ->map(fn ($product) => ['id' => $product->id, 'name' => $product->name.' (غیرفعال)'])
                    ->values()
                    ->all()
                : []
        );
    }

    public function getColorOptionsProperty(): array
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
        $this->productId = null;
        $this->colorId = null;
        $this->price = null;
        $this->isActive = true;
        $this->imageUploads = [];
        $this->editingId = null;
    }

    public function render()
    {
        $prices = ProductColorPrice::query()
            ->with('product', 'color', 'images')
            ->whereHas('product', fn ($productQuery) => $productQuery->whereNotNull('customization_workflow'))
            ->when($this->productId, fn ($query) => $query->where('product_id', $this->productId))
            ->when($this->search !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$this->search.'%')))
            ->orderByDesc('id')
            ->paginate(15);

        $selectedProduct = $this->productId ? Product::query()->find($this->productId) : null;

        return view('livewire.admin.product-color-price-manager', [
            'prices' => $prices,
            'productOptions' => $this->productOptions,
            'colorOptions' => $this->colorOptions,
            'selectedProduct' => $selectedProduct,
            'selectedProductIsFuel' => (string) $selectedProduct?->getRawOriginal('customization_workflow') === CustomizationWorkflowEnum::FUEL_CARD->value,
        ])->layout('layouts.admin')->title('قیمت‌گذاری کارت‌ها');
    }
}
