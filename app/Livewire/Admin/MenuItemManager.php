<?php

namespace App\Livewire\Admin;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\Design;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class MenuItemManager extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterMenuId = null;

    public ?int $menuId = null;
    public string $itemType = 'url';
    public string $title = '';
    public ?int $targetId = null;
    public ?string $customUrl = null;
    public string $target = '_self';
    public int $sortOrder = 0;
    public bool $isActive = true;

    public ?int $editingId = null;
    public bool $showForm = false;

    private const ALLOWED_TYPES = ['url', 'page', 'product', 'design', 'article'];

    protected function rules(): array
    {
        $rules = [
            'menuId' => 'required|exists:menus,id',
            'itemType' => 'required|in:'.implode(',', self::ALLOWED_TYPES),
            'title' => 'required|string|min:1|max:255',
            'targetId' => [
                'nullable',
                'integer',
            ],
            'customUrl' => [
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
            'target' => 'required|in:_self,_blank',
            'sortOrder' => 'integer',
            'isActive' => 'boolean',
        ];

        if ($this->itemType === 'url') {
            $rules['customUrl'][] = 'required';
        } else {
            $rules['targetId'][] = 'required';
            $rules['targetId'][] = match ($this->itemType) {
                'page' => 'exists:pages,id',
                'product' => 'exists:products,id',
                'design' => 'exists:designs,id',
                'article' => 'exists:articles,id',
                default => 'integer',
            };
        }

        return $rules;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMenuId(): void
    {
        $this->resetPage();
    }

    public function updatedItemType(): void
    {
        $this->targetId = null;
        $this->customUrl = null;
        $this->resetValidation(['targetId', 'customUrl']);
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $existing = MenuItem::find($this->editingId);

            if ($existing?->route_key) {
                session()->flash('error', 'این آیتم منو سیستمی است و فقط از طریق «ظاهر سایت» تغییر می‌کند.');
                return;
            }
        }

        $this->customUrl = $this->customUrl !== null && trim($this->customUrl) !== '' ? trim($this->customUrl) : null;

        $data = [
            'menu_id' => $this->menuId,
            'item_type' => $this->itemType,
            'title' => $this->title,
            'target_id' => $this->itemType === 'url' ? null : $this->targetId,
            'custom_url' => $this->itemType === 'url' ? $this->customUrl : null,
            'target' => $this->target,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            MenuItem::find($this->editingId)->update($data);
            session()->flash('success', 'آیتم منو با موفقیت ویرایش شد');
        } else {
            MenuItem::create($data);
            session()->flash('success', 'آیتم منو با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function edit(int $id): void
    {
        $item = MenuItem::find($id);
        $this->editingId = $id;
        $this->menuId = $item->menu_id;
        $this->itemType = $item->item_type->value;
        $this->title = $item->title;
        $this->targetId = $item->target_id;
        $this->customUrl = $item->custom_url;
        $this->target = $item->target;
        $this->sortOrder = $item->sort_order;
        $this->isActive = $item->is_active;
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $item = MenuItem::find($id);

        if ($item?->route_key) {
            session()->flash('error', 'این آیتم منو سیستمی است و قابل حذف نیست.');
            return;
        }

        $item?->delete();
        session()->flash('success', 'آیتم منو با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->menuId = null;
        $this->itemType = 'url';
        $this->title = '';
        $this->targetId = null;
        $this->customUrl = null;
        $this->target = '_self';
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.menu-item-manager', [
            'items' => MenuItem::query()
                ->with('menu')
                ->when($this->filterMenuId, fn ($query) => $query->where('menu_id', $this->filterMenuId))
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('custom_url', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('sort_order')
                ->paginate(15),
            'menus' => Menu::query()->orderBy('name')->get(),
            'pages' => Page::query()->active()->orderBy('title')->get(),
            'products' => Product::query()->active()->orderBy('name')->get(),
            'designs' => Design::query()->active()->orderBy('name')->get(),
            'articles' => Article::query()
                ->where('status', ArticleStatusEnum::PUBLISHED->value)
                ->where(function ($query) {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->orderBy('title')
                ->get(),
        ])->layout('layouts.admin')->title('آیتم‌های منو');
    }
}