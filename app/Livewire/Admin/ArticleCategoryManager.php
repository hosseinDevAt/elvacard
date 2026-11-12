<?php

namespace App\Livewire\Admin;

use App\Models\ArticleCategory;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleCategoryManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public string $slug = '';

    public bool $slugManuallyChanged = false;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_-]+$/',
                function ($attribute, $value, $fail) {
                    $slug = (string) $value;

                    if ($this->editingId) {
                        $existing = ArticleCategory::find($this->editingId);
                        if ($existing && $existing->slug !== $slug) {
                            $fail('امکان تغییر نامک دسته‌بندی وجود ندارد.');

                            return;
                        }
                    }

                    $exists = ArticleCategory::query()
                        ->where('slug', $slug)
                        ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
                        ->exists();

                    if ($exists) {
                        $fail('این نامک قبلاً ثبت شده و تکراری است.');
                    }
                },
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'slug.required' => 'وارد کردن نامک دسته‌بندی الزامی است.',
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $normalizedSlug = strtolower(trim($this->slug ?? ''));

        if ($this->editingId) {
            $articleCategory = ArticleCategory::find($this->editingId);

            if (! $articleCategory) {
                session()->flash('error', 'دسته‌بندی مقاله موردنظر یافت نشد');

                return;
            }

            $originalSlug = (string) $articleCategory->slug;

            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $normalizedSlug = $originalSlug;
            }

            $this->slug = $normalizedSlug;
        } else {
            if (! $this->slugManuallyChanged || $normalizedSlug === '') {
                $fallbackBase = trim($this->name);
                $normalizedSlug = $this->uniqueSlug(
                    $fallbackBase !== '' ? $fallbackBase : 'article-category',
                    ArticleCategory::class,
                    null,
                    'article-category',
                );
            }

            $this->slug = $normalizedSlug;
        }

        $this->validate();

        if ($this->editingId) {
            $articleCategory = ArticleCategory::find($this->editingId);

            if (! $articleCategory) {
                session()->flash('error', 'دسته‌بندی مقاله موردنظر یافت نشد');

                return;
            }

            $articleCategory->update([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            ArticleCategory::create([
                'name' => $this->name,
                'slug' => $this->slug,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $category = ArticleCategory::find($id);

        if (! $category) {
            session()->flash('error', 'دسته‌بندی مقاله موردنظر یافت نشد');

            return;
        }

        $this->editingId = $id;
        $this->name = $category->name;
        $this->slug = (string) $category->slug;
        $this->slugManuallyChanged = false;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $articleCategory = ArticleCategory::find($id);

        if (! $articleCategory) {
            session()->flash('error', 'دسته‌بندی مقاله موردنظر یافت نشد');

            return;
        }

        $articleCategory->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->slug = '';
        $this->slugManuallyChanged = false;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.article-category-manager', [
            'categories' => ArticleCategory::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('name', 'like', "%{$this->search}%")
                            ->orWhere('slug', 'like', "%{$this->search}%");
                    });
                })
                ->withCount('articles')
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت دسته‌بندی مقالات');
    }
}
