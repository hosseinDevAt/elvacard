<?php

namespace App\Livewire\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Models\ProductImage;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductColorPriceManager extends Component
{
    use AuthorizesAdminActions;
    use WithFileUploads;
    use WithPagination;

    public string $workflow = 'bank_card';

    public ?string $workflowFilter = null;

    public ?int $productId = null;

    public ?int $colorId = null;

    public ?int $price = null;

    public bool $isActive = true;

    public $imageUploads = [];

    public string $search = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'workflow' => ['required', 'string', 'in:bank_card,fuel_card'],
            'colorId' => ['required', 'integer', 'exists:colors,id'],
            'price' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
            'imageUploads.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    protected $messages = [
        'workflow.required' => 'نوع کارت را انتخاب کنید.',
        'workflow.in' => 'نوع کارت انتخابی نامعتبر است.',
        'colorId.required' => 'رنگ را انتخاب کنید.',
        'colorId.exists' => 'رنگ انتخاب‌شده نامعتبر است.',
        'price.required' => 'قیمت را وارد کنید.',
        'price.integer' => 'قیمت باید عدد صحیح باشد.',
        'price.min' => 'قیمت نمی‌تواند منفی باشد.',
    ];

    public function mount(): void
    {
        $workflowParam = request()->query('workflow');
        if (is_string($workflowParam) && in_array($workflowParam, [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value], true)) {
            $this->workflow = $workflowParam;
            $this->workflowFilter = $workflowParam;
        }

        // Backward compatibility for legacy ?product= query string
        $productParam = request()->query('product');
        if ($productParam !== null && filter_var($productParam, FILTER_VALIDATE_INT) !== false) {
            $resolved = Product::query()->find((int) $productParam);
            if ($resolved !== null && $resolved->customization_workflow) {
                $this->workflow = $resolved->customization_workflow->value;
                $this->workflowFilter = $resolved->customization_workflow->value;
                $this->productId = $resolved->id;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $duplicate = ProductColorPrice::query()
            ->where('customization_workflow', $this->workflow)
            ->where('color_id', $this->colorId)
            ->when($this->editingId, fn ($query) => $query->where('id', '!=', $this->editingId))
            ->exists();

        if ($duplicate) {
            $this->addError('colorId', 'قیمت‌گذاری برای این نوع کارت و رنگ از قبل ثبت شده است.');
            session()->flash('error', 'قیمت‌گذاری برای این نوع کارت و رنگ از قبل ثبت شده است.');

            return;
        }

        $currentColorPrice = $this->editingId
            ? $this->scopedCardVariant($this->editingId)
            : null;

        if ($this->editingId !== null && $currentColorPrice === null) {
            session()->flash('error', 'این قیمت یافت نشد؛ عملیات متوقف شد.');

            return;
        }

        $isFuelCard = $this->workflow === CustomizationWorkflowEnum::FUEL_CARD->value;

        if (
            $this->isActive
            && $isFuelCard
            && ProductColorPrice::fuelActiveCount($this->editingId) >= 1
        ) {
            $this->addError('isActive', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        if (
            ! $this->isActive
            && $isFuelCard
            && $currentColorPrice?->is_active
            && ProductColorPrice::fuelActiveCount($this->editingId) === 0
        ) {
            $this->addError('isActive', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        if ($this->productId !== null) {
            $product = Product::query()->find($this->productId);
            if ($product !== null && ! $this->isCardWorkflow($product)) {
                $this->addError('workflow', 'مدیریت قیمت رنگ فقط برای کارت‌های بانکی و سوخت انجام می‌شود؛ قیمت محصولات فروشگاهی را از «مدیریت محصولات» تنظیم کنید.');
                session()->flash('error', 'مدیریت قیمت رنگ فقط برای کارت‌های بانکی و سوخت انجام می‌شود؛ قیمت محصولات فروشگاهی را از «مدیریت محصولات» تنظیم کنید.');

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
        }

        $data = [
            'customization_workflow' => $this->workflow,
            'product_id' => $this->productId ?? $currentColorPrice?->product_id ?? null,
            'color_id' => $this->colorId,
            'price' => $this->price,
            'is_active' => $this->isActive,
        ];

        $previousColorId = null;

        if ($this->editingId && $currentColorPrice) {
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
                ->where('customization_workflow', $this->workflow)
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
        $currentWorkflow = $this->workflow;
        $this->resetForm();

        if (in_array($currentWorkflow, [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value], true)) {
            $this->workflow = $currentWorkflow;
        } else {
            $this->workflow = CustomizationWorkflowEnum::BANK_CARD->value;
        }

        $this->showForm = true;
    }

    private function scopedCardVariant(int $id): ?ProductColorPrice
    {
        $priceItem = ProductColorPrice::query()->whereKey($id)->first();

        if ($priceItem === null) {
            return null;
        }

        $workflow = $priceItem->customization_workflow instanceof CustomizationWorkflowEnum
            ? $priceItem->customization_workflow->value
            : ($priceItem->customization_workflow ? (string) $priceItem->customization_workflow : null);

        if ($workflow === null && $priceItem->product) {
            $productWorkflow = $priceItem->product->getRawOriginal('customization_workflow');
            $workflow = $productWorkflow !== null ? (string) $productWorkflow : null;
        }

        if (! in_array($workflow, [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value], true)) {
            return null;
        }

        if ($priceItem->product_id !== null && (int) $priceItem->product_id !== (int) $this->productId) {
            return null;
        }

        return $priceItem;
    }

    private function scopedCardVariantImage(int $id): ?ProductImage
    {
        $image = ProductImage::query()->whereKey($id)->first();

        if ($image === null) {
            return null;
        }

        $workflow = $image->customization_workflow instanceof CustomizationWorkflowEnum
            ? $image->customization_workflow->value
            : ($image->customization_workflow ? (string) $image->customization_workflow : null);

        if ($workflow === null && $image->product) {
            $productWorkflow = $image->product->getRawOriginal('customization_workflow');
            $workflow = $productWorkflow !== null ? (string) $productWorkflow : null;
        }

        if (! in_array($workflow, [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value], true)) {
            return null;
        }

        if ($image->product_id !== null && (int) $image->product_id !== (int) $this->productId) {
            return null;
        }

        return $image;
    }

    public function selectWorkflow(?string $workflow): void
    {
        if ($workflow === null || in_array($workflow, [CustomizationWorkflowEnum::BANK_CARD->value, CustomizationWorkflowEnum::FUEL_CARD->value], true)) {
            $this->workflowFilter = $workflow;
            if ($workflow !== null) {
                $this->workflow = $workflow;
            }
        }
    }

    public function selectProduct(int $id): void
    {
        $product = Product::query()->find($id);

        if ($product === null || ! $this->isCardWorkflow($product)) {
            session()->flash('error', 'مدیریت قیمت رنگ فقط برای کارت‌های بانکی و سوخت انجام می‌شود؛ قیمت محصولات فروشگاهی را از «مدیریت محصولات» تنظیم کنید.');

            return;
        }

        $this->productId = (int) $product->id;
        $workflowRaw = $product->getRawOriginal('customization_workflow');
        $this->workflow = $workflowRaw !== null ? (string) $workflowRaw : CustomizationWorkflowEnum::BANK_CARD->value;
        $this->workflowFilter = $this->workflow;
    }

    private function isCardWorkflow(Product $product): bool
    {
        $workflowRaw = (string) $product->getRawOriginal('customization_workflow');
        $workflow = CustomizationWorkflowEnum::tryFrom($workflowRaw);

        if ($workflow === null || ! CustomizationWorkflowRegistry::isActive($workflow)) {
            return false;
        }

        $typeRaw = (string) $product->getRawOriginal('type');
        $expectedType = CustomizationWorkflowRegistry::expectedType($workflow);

        return $typeRaw === $expectedType->value;
    }

    public function edit(int $id): void
    {
        $priceItem = $this->scopedCardVariant($id);

        if (! $priceItem) {
            session()->flash('error', 'قیمت موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;

        $workflow = $priceItem->customization_workflow instanceof CustomizationWorkflowEnum
            ? $priceItem->customization_workflow->value
            : ($priceItem->customization_workflow ? (string) $priceItem->customization_workflow : null);

        if ($workflow === null && $priceItem->product) {
            $productWorkflow = $priceItem->product->getRawOriginal('customization_workflow');
            $workflow = $productWorkflow !== null ? (string) $productWorkflow : null;
        }

        $this->workflow = $workflow ?? CustomizationWorkflowEnum::BANK_CARD->value;
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

        $workflow = $priceItem->customization_workflow instanceof CustomizationWorkflowEnum
            ? $priceItem->customization_workflow->value
            : (string) $priceItem->customization_workflow;

        if (
            $workflow === CustomizationWorkflowEnum::FUEL_CARD->value
            && $priceItem->is_active
            && ProductColorPrice::fuelActiveCount($id) === 0
        ) {
            session()->flash('error', 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد.');

            return;
        }

        if ($priceItem->product_id !== null) {
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
        }

        $images = ProductImage::query()
            ->where('customization_workflow', $workflow)
            ->where('color_id', $priceItem->color_id)
            ->get();

        $priceItem->delete();

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

        $workflow = $image->customization_workflow instanceof CustomizationWorkflowEnum
            ? $image->customization_workflow->value
            : (string) $image->customization_workflow;

        ProductImage::query()
            ->where('customization_workflow', $workflow)
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
            ->where('customization_workflow', $this->workflow)
            ->where('color_id', $this->colorId)
            ->where('is_primary', true)
            ->exists();

        $nextSortOrder = (int) ProductImage::query()
            ->where('customization_workflow', $this->workflow)
            ->where('color_id', $this->colorId)
            ->max('sort_order');

        foreach ($uploads as $index => $file) {
            ProductImage::query()->create([
                'customization_workflow' => $this->workflow,
                'product_id' => $this->productId ?? $priceItem->product_id ?? null,
                'color_id' => $priceItem->color_id,
                'image_path' => $file->store('products', 'public'),
                'sort_order' => $nextSortOrder + $index + 1,
                'is_primary' => ! $hasPrimary && $index === 0,
            ]);
        }
    }

    private function deleteImageRow(ProductImage $image): void
    {
        $workflow = $image->customization_workflow instanceof CustomizationWorkflowEnum
            ? $image->customization_workflow->value
            : (string) $image->customization_workflow;

        $colorId = $image->color_id;

        $image->delete();

        $remaining = ProductImage::query()
            ->where('customization_workflow', $workflow)
            ->where('color_id', $colorId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (! $remaining->contains(fn ($item) => (bool) $item->is_primary) && $remaining->isNotEmpty()) {
            $remaining->first()->update(['is_primary' => true]);
        }
    }

    public static function findCardProduct(CustomizationWorkflowEnum $workflow): ?Product
    {
        $expectedType = CustomizationWorkflowRegistry::expectedType($workflow);

        return Product::query()
            ->where('customization_workflow', $workflow->value)
            ->where('type', $expectedType->value)
            ->first();
    }

    public function getCardProductsProperty(): array
    {
        return [
            'bank_card' => self::findCardProduct(CustomizationWorkflowEnum::BANK_CARD),
            'fuel_card' => self::findCardProduct(CustomizationWorkflowEnum::FUEL_CARD),
        ];
    }

    public function getWorkflowOptionsProperty(): array
    {
        return [
            ['value' => CustomizationWorkflowEnum::BANK_CARD->value, 'name' => 'کارت بانکی', 'workflow' => 'bank_card'],
            ['value' => CustomizationWorkflowEnum::FUEL_CARD->value, 'name' => 'کارت سوخت', 'workflow' => 'fuel_card'],
        ];
    }

    public function getProductOptionsProperty(): array
    {
        $cards = $this->cardProducts;

        return [
            [
                'id' => $cards['bank_card']?->id,
                'name' => $cards['bank_card']?->name ?: 'کارت بانکی',
                'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            ],
            [
                'id' => $cards['fuel_card']?->id,
                'name' => $cards['fuel_card']?->name ?: 'کارت سوخت',
                'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
            ],
        ];
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
        $this->colorId = null;
        $this->price = null;
        $this->isActive = true;
        $this->imageUploads = [];
        $this->editingId = null;
    }

    public function render()
    {
        $prices = ProductColorPrice::query()
            ->with(['color', 'images', 'product'])
            ->where(function ($q) {
                $q->whereNotNull('customization_workflow')
                    ->orWhereHas('product', fn ($pq) => $pq->whereNotNull('customization_workflow'));
            })
            ->when($this->workflowFilter, function ($q) {
                $q->where(function ($wq) {
                    $wq->where('customization_workflow', $this->workflowFilter)
                        ->orWhereHas('product', fn ($pq) => $pq->where('customization_workflow', $this->workflowFilter));
                });
            })
            ->when($this->search !== '', fn ($query) => $query->whereHas('color', fn ($cq) => $cq->where('name', 'like', '%'.$this->search.'%')))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.product-color-price-manager', [
            'prices' => $prices,
            'workflowOptions' => $this->workflowOptions,
            'productOptions' => $this->productOptions,
            'colorOptions' => $this->colorOptions,
            'selectedWorkflowIsFuel' => $this->workflow === CustomizationWorkflowEnum::FUEL_CARD->value,
        ])->layout('layouts.admin')->title('قیمت‌گذاری کارت‌ها');
    }
}
