<?php

namespace App\Livewire\Admin;

use App\Models\Color;
use App\Models\Product;
use App\Models\ProductColorPrice;
use Livewire\Component;
use Livewire\WithPagination;

class ProductColorPriceManager extends Component
{
    use WithPagination;

    public ?int $productId = null;
    public ?int $colorId = null;
    public ?int $price = null;
    public bool $isActive = true;

    public string $search = '';
    public ?int $editingId = null;
    public bool $showForm = false;

    protected $rules = [
        'productId' => 'required|integer|exists:products,id',
        'colorId' => 'required|integer|exists:colors,id',
        'price' => 'required|integer|min:0',
        'isActive' => 'boolean',
    ];

    protected $messages = [
        'productId.required' => 'محصول را انتخاب کنید.',
        'colorId.required' => 'رنگ را انتخاب کنید.',
        'price.required' => 'قیمت را وارد کنید.',
        'price.integer' => 'قیمت باید عدد صحیح باشد.',
        'price.min' => 'قیمت نمی‌تواند منفی باشد.',
    ];

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

        $data = [
            'product_id' => $this->productId,
            'color_id' => $this->colorId,
            'price' => $this->price,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            ProductColorPrice::find($this->editingId)->update($data);
            session()->flash('success', 'قیمت با موفقیت ویرایش شد');
        } else {
            ProductColorPrice::create($data);
            session()->flash('success', 'قیمت با موفقیت اضافه شد');
        }

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
        $priceItem = ProductColorPrice::find($id);

        if (! $priceItem) {
            session()->flash('error', 'قیمت موردنظر یافت نشد');

            return;
        }

        $priceItem->delete();
        session()->flash('success', 'قیمت با موفقیت حذف شد');
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
        $this->editingId = null;
    }

    public function render()
    {
        $prices = ProductColorPrice::query()
            ->with('product', 'color')
            ->when($this->search !== '', fn ($query) => $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', '%'.$this->search.'%')))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.product-color-price-manager', [
            'prices' => $prices,
            'productOptions' => $this->productOptions,
            'colorOptions' => $this->colorOptions,
        ])->layout('layouts.admin')->title('قیمت رنگ محصولات');
    }
}