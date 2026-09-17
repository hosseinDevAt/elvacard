<?php

namespace App\Livewire\Admin;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleSlugHistory;
use App\Services\StoredFileManager;
use App\Services\SvgSanitizer;
use App\Support\Concerns\AuthorizesAdminActions;
use App\Support\Concerns\GeneratesUniqueSlug;
use App\Support\Dates\DateService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ArticleManager extends Component
{
    use AuthorizesAdminActions;
    use GeneratesUniqueSlug;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public ?int $articleCategoryId = null;

    public string $title = '';

    public ?string $excerpt = null;

    public string $content = '';

    public $coverImageUpload;

    public bool $removeCoverImage = false;

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
            'coverImageUpload' => 'nullable|image:allow_svg|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'status' => 'required|in:'.implode(',', array_column(ArticleStatusEnum::cases(), 'value')),
            'publishedAt' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }

                    if (! app(DateService::class)->isValidDate($value)) {
                        $fail('تاریخ انتشار نامعتبر است.');
                    }
                },
            ],
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
        return $this->uniqueSlug(
            $value,
            Article::class,
            $ignoreId,
            'article',
            fn (string $candidate): bool => ArticleSlugHistory::query()->where('slug', $candidate)->exists(),
        );
    }

    public function save(): void
    {
        $this->validate();

        $this->articleCategoryId = $this->articleCategoryId !== null && $this->articleCategoryId !== '' ? $this->articleCategoryId : null;
        $this->excerpt = $this->excerpt !== null && trim($this->excerpt) !== '' ? trim($this->excerpt) : null;
        $this->publishedAt = $this->publishedAt !== null && trim($this->publishedAt) !== '' ? $this->publishedAt : null;
        $this->metaTitle = $this->metaTitle !== null && trim($this->metaTitle) !== '' ? trim($this->metaTitle) : null;
        $this->metaDescription = $this->metaDescription !== null && trim($this->metaDescription) !== '' ? trim($this->metaDescription) : null;
        $this->canonicalUrl = $this->canonicalUrl !== null && trim($this->canonicalUrl) !== '' ? trim($this->canonicalUrl) : null;

        $previousSlug = null;
        $oldCoverImage = null;

        if ($this->editingId) {
            $article = Article::find($this->editingId);
            $previousSlug = $article?->slug;
            // The persisted cover path is NEVER taken from client input: it is
            // always the database value unless a freshly uploaded file (or an
            // explicit remove) replaces it.
            $oldCoverImage = $article?->cover_image;
        }

        $coverImage = $oldCoverImage;

        if ($this->coverImageUpload) {
            $coverImage = $this->coverImageUpload->store('articles', 'public');

            if (strtolower((string) $this->coverImageUpload->getClientOriginalExtension()) === 'svg') {
                $this->sanitizeStoredSvg($coverImage, 'coverImageUpload');
            }
        }

        if ($this->removeCoverImage) {
            $coverImage = null;
        }

        $dates = app(DateService::class);

        $slug = $this->generateUniqueSlug($this->title, $this->editingId);

        $data = [
            'article_category_id' => $this->articleCategoryId,
            'title' => $this->title,
            'slug' => $slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'cover_image' => $coverImage,
            'status' => $this->status,
            'published_at' => $this->publishedAt !== null ? $dates->fromJalali($this->publishedAt) : null,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'canonical_url' => $this->canonicalUrl,
            'robots_index' => $this->robotsIndex,
        ];

        if ($this->editingId) {
            Article::find($this->editingId)->update($data);

            if ($previousSlug !== null && $previousSlug !== $slug) {
                ArticleSlugHistory::query()->firstOrCreate(
                    ['slug' => $previousSlug],
                    ['article_id' => $this->editingId],
                );
            }

            if ($oldCoverImage !== null && $oldCoverImage !== $coverImage) {
                app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
                    [$oldCoverImage],
                    fn (string $path): bool => Article::query()->where('cover_image', $path)->exists(),
                );
            }

            session()->flash('success', 'مقاله با موفقیت ویرایش شد');
        } else {
            Article::create($data);
            session()->flash('success', 'مقاله با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    /**
     * Re-write a stored SVG through the sanitizer. A dangerous or malformed
     * SVG is deleted and the upload rejected with a validation error.
     */
    private function sanitizeStoredSvg(string $path, string $property): void
    {
        $disk = Storage::disk('public');
        $content = $disk->get($path);

        if (! is_string($content)) {
            $disk->delete($path);
            throw ValidationException::withMessages([$property => 'خواندن فایل SVG ممکن نشد.']);
        }

        $clean = app(SvgSanitizer::class)->sanitize($content);

        if ($clean === null) {
            $disk->delete($path);
            throw ValidationException::withMessages([$property => 'محتوای فایل SVG نامعتبر یا ناامن است.']);
        }

        $disk->put($path, $clean);
    }

    public function edit(int $id): void
    {
        $article = Article::find($id);
        $dates = app(DateService::class);
        $this->editingId = $id;
        $this->articleCategoryId = $article->article_category_id;
        $this->title = $article->title;
        $this->excerpt = $article->excerpt;
        $this->content = $article->content;
        $this->coverImage = $article->cover_image;
        $this->removeCoverImage = false;
        $this->status = $article->status->value;
        $this->publishedAt = $article->published_at ? $dates->ascii($dates->jDateTime($article->published_at)) : null;
        $this->metaTitle = $article->meta_title;
        $this->metaDescription = $article->meta_description;
        $this->canonicalUrl = $article->canonical_url;
        $this->robotsIndex = $article->robots_index;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $article = Article::find($id);

        if (! $article) {
            session()->flash('error', 'مقاله موردنظر یافت نشد');

            return;
        }

        $coverImage = $article->cover_image;

        $article->delete();

        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            [$coverImage],
            fn (string $path): bool => Article::query()->where('cover_image', $path)->exists(),
        );

        session()->flash('success', 'مقاله با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->articleCategoryId = null;
        $this->title = '';
        $this->excerpt = null;
        $this->content = '';
        $this->coverImageUpload = null;
        $this->removeCoverImage = false;
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
