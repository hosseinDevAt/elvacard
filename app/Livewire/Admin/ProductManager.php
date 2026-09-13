<?php

namespace App\Livewire\Admin;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\ProductTypeEnum;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Support\Concerns\GeneratesUniqueSlug;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $type = 'bank';

    public ?string $customizationWorkflow = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $mainImage = null;

    public ?int $basePrice = null;

    public bool $supportsChipSelection = false;

    public ?string $designConfig = null;

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $canonicalUrl = null;

    public bool $robotsIndex = true;

    public ?string $ogImage = null;

    public ?string $seoContent = null;

    public bool $isActive = true;

    public string $search = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'type' => 'required|in:bank,fuel,standard',
        'customizationWorkflow' => 'nullable|in:bank_card,fuel_card',
        'name' => 'required|string|min:1|max:255',
        'description' => 'nullable|string',
        'mainImage' => 'nullable|string|max:255',
        'basePrice' => 'nullable|integer|min:0',
        'supportsChipSelection' => 'boolean',
        'designConfig' => 'nullable|json',
        'metaTitle' => 'nullable|string|max:255',
        'metaDescription' => 'nullable|string|max:255',
        'canonicalUrl' => 'nullable|string|max:255',
        'ogImage' => 'nullable|string|max:255',
        'seoContent' => 'nullable|string',
        'robotsIndex' => 'boolean',
        'isActive' => 'boolean',
    ];

    public function save(): void
    {
        $this->validate();

        if ($this->customizationWorkflow === CustomizationWorkflowEnum::FUEL_CARD->value && $this->isActive) {
            $this->addError(
                'customizationWorkflow',
                'سرویس کارت سوخت هنوز فعال نشده است؛ محصول قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'
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
        session()->flash('success', 'محصول با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->type = 'bank';
        $this->customizationWorkflow = null;
        $this->name = '';
        $this->description = null;
        $this->mainImage = null;
        $this->basePrice = null;
        $this->supportsChipSelection = false;
        $this->designConfig = null;
        $this->metaTitle = null;
        $this->metaDescription = null;
        $this->canonicalUrl = null;
        $this->robotsIndex = true;
        $this->ogImage = null;
        $this->seoContent = null;
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        $products = Product::query()
            ->withCount('colorPrices')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.product-manager', [
            'products' => $products,
            'typeOptions' => ProductTypeEnum::options(),
            'workflowOptions' => [
                ['value' => '', 'label' => 'بدون شخصی‌سازی'],
                ['value' => CustomizationWorkflowEnum::BANK_CARD->value, 'label' => CustomizationWorkflowEnum::BANK_CARD->faLabel()],
                ['value' => CustomizationWorkflowEnum::FUEL_CARD->value, 'label' => CustomizationWorkflowEnum::FUEL_CARD->faLabel()],
            ],
        ])->layout('layouts.admin')->title('مدیریت محصولات');
    }
}
