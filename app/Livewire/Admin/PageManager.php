<?php

namespace App\Livewire\Admin;

use App\Models\Page;
use App\Services\StoredFileManager;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class PageManager extends Component
{
    use AuthorizesAdminActions;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $pageType = '';

    public string $title = '';

    public string $content = '';

    public $imageUpload;

    public ?string $imagePath = null;

    public ?string $metaTitle = null;

    public ?string $metaDescription = null;

    public ?string $canonicalUrl = null;

    public bool $robotsIndex = true;

    public bool $isActive = true;

    public ?int $editingId = null;

    public bool $showForm = false;

    protected function rules(): array
    {
        return [
            'pageType' => 'required|string|max:255',
            'title' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:1',
            'imageUpload' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
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
            'isActive' => 'boolean',
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
            $base = 'page-'.Str::lower(Str::random(8));
        }

        $slug = $base;

        while (Page::query()
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

        if ($this->imageUpload) {
            $this->imagePath = $this->imageUpload->store('pages', 'public');
        }

        $this->imagePath = $this->imagePath !== null && trim($this->imagePath) !== '' ? trim($this->imagePath) : null;
        $this->metaTitle = $this->metaTitle !== null && trim($this->metaTitle) !== '' ? trim($this->metaTitle) : null;
        $this->metaDescription = $this->metaDescription !== null && trim($this->metaDescription) !== '' ? trim($this->metaDescription) : null;
        $this->canonicalUrl = $this->canonicalUrl !== null && trim($this->canonicalUrl) !== '' ? trim($this->canonicalUrl) : null;

        $data = [
            'page_type' => $this->pageType,
            'title' => $this->title,
            'slug' => $this->generateUniqueSlug($this->title, $this->editingId),
            'content' => $this->content,
            'image_path' => $this->imagePath,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'canonical_url' => $this->canonicalUrl,
            'robots_index' => $this->robotsIndex,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            Page::find($this->editingId)->update($data);
            session()->flash('success', 'صفحه با موفقیت ویرایش شد');
        } else {
            Page::create($data);
            session()->flash('success', 'صفحه با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $page = Page::find($id);
        $this->editingId = $id;
        $this->pageType = $page->page_type;
        $this->title = $page->title;
        $this->content = $page->content;
        $this->imagePath = $page->image_path;
        $this->metaTitle = $page->meta_title;
        $this->metaDescription = $page->meta_description;
        $this->canonicalUrl = $page->canonical_url;
        $this->robotsIndex = $page->robots_index;
        $this->isActive = $page->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $page = Page::find($id);

        if (! $page) {
            session()->flash('error', 'صفحه موردنظر یافت نشد');

            return;
        }

        $imagePath = $page->image_path;

        $page->delete();

        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            [$imagePath],
            fn (string $path): bool => Page::query()->where('image_path', $path)->exists(),
        );

        session()->flash('success', 'صفحه با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->pageType = '';
        $this->title = '';
        $this->content = '';
        $this->imageUpload = null;
        $this->imagePath = null;
        $this->metaTitle = null;
        $this->metaDescription = null;
        $this->canonicalUrl = null;
        $this->robotsIndex = true;
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.page-manager', [
            'pages' => Page::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('slug', 'like', "%{$this->search}%");
                    });
                })
                ->latest()
                ->paginate(15),
        ])->layout('layouts.admin')->title('مدیریت صفحات');
    }
}
