<?php

namespace App\Livewire\Admin;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleManager extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $articleCategoryId = null;
    public string $title = '';
    public ?string $excerpt = null;
    public string $content = '';
    public ?string $coverImage = null;
    public string $status = ArticleStatusEnum::DRAFT->value;
    public ?string $publishedAt = null;
    public ?string $metaTitle = null;
    public ?string $metaDescription = null;
    public ?string $canonicalUrl = null;
    public bool $robotsIndex = true;

    public ?int $editingId = null;
    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'title' => 'required|string|min:1|max:255',
            'articleCategoryId' => 'nullable|exists:article_categories,id',
            'excerpt' => 'nullable|string',
            'content' => 'required|string|min:1',
            'coverImage' => 'nullable|string|max:2048',
            'status' => 'required|in:'.implode(',', array_column(ArticleStatusEnum::cases(), 'value')),
            'publishedAt' => 'nullable|date',
            'metaTitle' => 'nullable|string|max:255',
            'metaDescription' => 'nullable|string',
            'canonicalUrl' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }

                    $value = trim($value);

                    if (str_starts_with(strtolower($value), '//')) {
                        $fail('لینک پروتکل‌نسبی (//...) مجاز نیست.');
                        return;
                    }

                    if (safe_url($value) === null) {
                        $fail('لینک باید با http://، https:// یا / شروع شود.');
                    }
                },
            ],
            'robotsIndex' => 'boolean',
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
            $base = 'article-'.Str::lower(Str::random(8));
        }

        $slug = $base;

        while (Article::query()
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

        $this->articleCategoryId = $this->articleCategoryId !== null && $this->articleCategoryId !== '' ? $this->articleCategoryId : null;
        $this->excerpt = $this->excerpt !== null && trim($this->excerpt) !== '' ? trim($this->excerpt) : null;
        $this->coverImage = $this->coverImage !== null && trim($this->coverImage) !== '' ? trim($this->coverImage) : null;
        $this->publishedAt = $this->publishedAt !== null && trim($this->publishedAt) !== '' ? $this->publishedAt : null;
        $this->metaTitle = $this->metaTitle !== null && trim($this->metaTitle) !== '' ? trim($this->metaTitle) : null;
        $this->metaDescription = $this->metaDescription !== null && trim($this->metaDescription) !== '' ? trim($this->metaDescription) : null;
        $this->canonicalUrl = $this->canonicalUrl !== null && trim($this->canonicalUrl) !== '' ? trim($this->canonicalUrl) : null;

        $data = [
            'article_category_id' => $this->articleCategoryId,
            'title' => $this->title,
            'slug' => $this->generateUniqueSlug($this->title, $this->editingId),
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'cover_image' => $this->coverImage,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'canonical_url' => $this->canonicalUrl,
            'robots_index' => $this->robotsIndex,
        ];

        if ($this->editingId) {
            Article::find($this->editingId)->update($data);
            session()->flash('success', 'مقاله با موفقیت ویرایش شد');
        } else {
            Article::create($data);
            session()->flash('success', 'مقاله با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $article = Article::find($id);
        $this->editingId = $id;
        $this->articleCategoryId = $article->article_category_id;
        $this->title = $article->title;
        $this->excerpt = $article->excerpt;
        $this->content = $article->content;
        $this->coverImage = $article->cover_image;
        $this->status = $article->status->value;
        $this->publishedAt = $article->published_at?->format('Y-m-d\TH:i');
        $this->metaTitle = $article->meta_title;
        $this->metaDescription = $article->meta_description;
        $this->canonicalUrl = $article->canonical_url;
        $this->robotsIndex = $article->robots_index;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        Article::find($id)->delete();
        session()->flash('success', 'مقاله با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->articleCategoryId = null;
        $this->title = '';
        $this->excerpt = null;
        $this->content = '';
        $this->coverImage = null;
        $this->status = ArticleStatusEnum::DRAFT->value;
        $this->publishedAt = null;
        $this->metaTitle = null;
        $this->metaDescription = null;
        $this->canonicalUrl = null;
        $this->robotsIndex = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.article-manager', [
            'articles' => Article::query()
                ->with('category')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('content', 'like', "%{$this->search}%");
                    });
                })
                ->latest()
                ->paginate(15),
            'categories' => ArticleCategory::query()->orderBy('name')->get(),
        ])->layout('layouts.admin')->title('مدیریت مقالات');
    }
}