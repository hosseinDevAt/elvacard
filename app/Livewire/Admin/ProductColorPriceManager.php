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
        'imageUploads.*' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
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

        if ($product !== null && filter_var($product, FILTER_VALIDATE_INT) !== false) {
            $this->productId = (int) $product;
        }
    }

    public function save(): void
    {
        $this->validate();

        $duplicate = ProductColorPrice::query()
            ->where('product_id', $this->productId)
            ->where('color_id', $this->colorId)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($duplicate) {
            session()->flash('error', 'قیمت‌گذاری برای این محصول و رنگ از قبل ثبت شده است.');

            return;
        }

        $product = Product::find($this->productId);

        if (
            $this->isActive
            && $product?->customization_workflow === CustomizationWorkflowEnum::FUEL_CARD
            && ProductColorPrice::fuelActiveCount($this->productId, $this->editingId) >= 1
        ) {
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        $currentColorPrice = $this->editingId ? ProductColorPrice::find($this->editingId) : null;

        if (
            ! $this->isActive
            && $product?->customization_workflow === CustomizationWorkflowEnum::FUEL_CARD
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
            $priceItem = ProductColorPrice::find($this->editingId);
            $previousColorId = $priceItem?->color_id !== null ? (int) $priceItem->color_id : null;
            $priceItem?->update($data);
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

    public function edit(int $id): void
    {
        $priceItem = ProductColorPrice::with('product', 'color')->find($id);

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
        $priceItem = ProductColorPrice::with('product')->find($id);

        if (! $priceItem) {
            session()->flash('error', 'قیمت موردنظر یافت نشد');

            return;
        }

        if (
            $priceItem->product?->customization_workflow === CustomizationWorkflowEnum::FUEL_CARD
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
        $image = ProductImage::find($id);

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
        $image = ProductImage::find($id);

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
        $items = Product::query()->orderBy('name')->get(['id', 'name', 'is_active']);

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
            ->when($this->productId, fn ($query) => $query->where('product_id', $this->productId))
            ->when($this->search !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$this->search.'%')))
            ->orderByDesc('id')
            ->paginate(15);

        $selectedProduct = $this->productId ? Product::query()->find($this->productId) : null;

        return view('livewire.admin.product-color-price-manager', [
            'prices' => $prices,
            'productOptions' => $this->productOptions,
            'colorOptions' => $this->colorOptions,
            'selectedProductIsFuel' => $selectedProduct?->customization_workflow === CustomizationWorkflowEnum::FUEL_CARD,
        ])->layout('layouts.admin')->title('قیمت رنگ محصولات');
    }
}
