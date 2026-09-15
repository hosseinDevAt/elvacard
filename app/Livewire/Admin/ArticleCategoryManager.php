<?php

namespace App\Livewire\Admin;

use App\Models\ArticleCategory;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleCategoryManager extends Component
{
    use AuthorizesAdminActions;
    use WithPagination;

    public string $search = '';

    public string $name = '';

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:1|max:255',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value);

        if ($base === '') {
            $base = 'article-category-'.Str::lower(Str::random(8));
        }

        $slug = $base;

        while (ArticleCategory::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.Str::lower(Str::random(8));
        }

        return $slug;
    }

    public function save(): void
    {
        $this->validate();

        $slug = $this->generateUniqueSlug($this->name, $this->editingId);

        if ($this->editingId) {
            ArticleCategory::find($this->editingId)->update([
                'name' => $this->name,
                'slug' => $slug,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت ویرایش شد');
        } else {
            ArticleCategory::create([
                'name' => $this->name,
                'slug' => $slug,
            ]);
            session()->flash('success', 'دسته‌بندی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $category = ArticleCategory::find($id);
        $this->editingId = $id;
        $this->name = $category->name;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        ArticleCategory::find($id)->delete();
        session()->flash('success', 'دسته‌بندی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->name = '';
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
