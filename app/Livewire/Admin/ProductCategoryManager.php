<?php

namespace App\Livewire\Admin;

use App\Models\ProductCategory;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCategoryManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $name = '';

    public bool $isActive = true;

    public int $sortOrder = 0;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected $rules = [
        'name' => 'required|string|min:1|max:120',
        'isActive' => 'boolean',
        'sortOrder' => 'integer|min:0',
    ];

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->uniqueSlug($this->name, ProductCategory::class, $this->editingId, 'category'),
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ];

        if ($this->editingId) {
            $productCategory = ProductCategory::find($this->editingId);

            if (! $productCategory) {
                session()->flash('error', 'دسته‌بندی محصول موردنظر یافت نشد');

                return;
            }

            $productCategory->update($data);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            ProductCategory::create($data);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Opens the form for a brand new category. Always resets first: otherwise
     * opening the form right after editing another row keeps the previous
     * editingId and silently overwrites that category on save.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->name = $category->name;
        $this->isActive = (bool) $category->is_active;
        $this->sortOrder = (int) $category->sort_order;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $category = ProductCategory::find($id);

        if (! $category) {
            session()->flash('error', 'دسته‌بندی موردنظر یافت نشد');

            return;
        }

        if ($category->products()->exists()) {
            session()->flash('error', 'این دسته‌بندی در محصولات استفاده شده است و قابل حذف نیست.');

            return;
        }

        $category->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.product-category-manager', [
            'categories' => ProductCategory::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت دسته‌بندی محصولات');
    }
}
