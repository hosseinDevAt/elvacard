<?php

namespace App\Livewire\Admin;

use App\Models\ProductCategory;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCategoryManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $name = '';

    public string $slug = '';

    public bool $slugManuallyChanged = false;

    public bool $isActive = true;

    public int $sortOrder = 0;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:120',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                function ($attribute, $value, $fail) {
                    $slug = (string) $value;

                    if ($this->editingId) {
                        $existing = ProductCategory::find($this->editingId);
                        if ($existing && $existing->slug !== $slug) {
                            $fail('امکان تغییر نامک دسته‌بندی وجود ندارد.');

                            return;
                        }
                    }

                    $exists = ProductCategory::query()
                        ->where('slug', $slug)
                        ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('این نامک قبلاً ثبت شده و تکراری است.');
                    }
                },
            ],
            'isActive' => 'boolean',
            'sortOrder' => 'integer|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'نام دسته‌بندی الزامی است.',
            'slug.required' => 'نامک دسته‌بندی الزامی است.',
            'slug.regex' => 'نامک فقط می‌تواند شامل حروف کوچک انگلیسی، اعداد، خط تیره (-) و زیرخط (_) باشد.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'نام دسته‌بندی',
            'slug' => 'نامک انگلیسی',
        ];
    }

    public function updatedName(string $value): void
    {
        if (! $this->editingId && (! $this->slugManuallyChanged || trim($this->slug) === '')) {
            $suggested = Str::slug($value);
            if ($suggested !== '') {
                $this->slug = $suggested;
            }
        }
    }

    public function updatedSlug(string $value): void
    {
        $this->slugManuallyChanged = true;
    }

    public function save(): void
    {
        $normalizedSlug = strtolower(trim($this->slug ?? ''));

        if ($this->editingId) {
            $category = ProductCategory::find($this->editingId);

            if (! $category) {
                session()->flash('error', 'دسته‌بندی محصول موردنظر یافت نشد');

                return;
            }

            $originalSlug = (string) $category->slug;

            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $normalizedSlug = $originalSlug;
            }

            $this->slug = $normalizedSlug;
        } else {
            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $fallbackBase = trim($this->name);
                $normalizedSlug = $this->uniqueSlug(
                    $fallbackBase !== '' ? $fallbackBase : 'category',
                    ProductCategory::class,
                    null,
                    'category',
                );
            }

            $this->slug = $normalizedSlug;
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
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
        $this->slug = (string) $category->slug;
        $this->slugManuallyChanged = false;
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
        $this->slug = '';
        $this->slugManuallyChanged = false;
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
