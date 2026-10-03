<?php

namespace App\Livewire\Catalog;

use App\Enums\PaymentStatusEnum;
use App\Models\Color;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'category', history: true)]
    public ?string $category = null;

    #[Url(as: 'search', history: true)]
    public ?string $search = '';

    #[Url(as: 'color_id', history: true)]
    public ?int $colorId = null;

    #[Url(as: 'min_price', history: true)]
    public ?int $minPrice = null;

    #[Url(as: 'max_price', history: true)]
    public ?int $maxPrice = null;

    #[Url(as: 'sort', history: true)]
    public ?string $sort = 'newest';

    public string $viewMode = 'grid';

    public function mount(
        ?string $category = null,
        ?string $search = null,
        ?int $colorId = null,
        ?int $minPrice = null,
        ?int $maxPrice = null,
        ?string $sort = null,
    ): void {
        if ($this->category === null && $category !== null && $category !== '') {
            $this->category = $category;
        }

        if ($this->search === '' && $search !== null && $search !== '') {
            $this->search = trim($search);
        }

        if ($this->colorId === null && $colorId !== null) {
            $this->colorId = $colorId;
        }

        if ($this->minPrice === null && $minPrice !== null) {
            $this->minPrice = $minPrice;
        }

        if ($this->maxPrice === null && $maxPrice !== null) {
            $this->maxPrice = $maxPrice;
        }

        $validSorts = ['newest', 'cheapest', 'expensive', 'popular'];
        if ($this->sort === 'newest' && $sort !== null && in_array($sort, $validSorts, true)) {
            $this->sort = $sort;
        }
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedColorId(): void
    {
        $this->resetPage();
    }

    public function updatedMinPrice(): void
    {
        $this->resetPage();
    }

    public function updatedMaxPrice(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function selectCategory(?string $slug): void
    {
        $this->category = ($this->category === $slug || empty($slug)) ? null : $slug;
        $this->resetPage();
    }

    public function selectColor(?int $id): void
    {
        $this->colorId = ($this->colorId === $id) ? null : $id;
        $this->resetPage();
    }

    public function setSort(string $sort): void
    {
        $valid = ['newest', 'cheapest', 'expensive', 'popular'];
        $this->sort = in_array($sort, $valid, true) ? $sort : 'newest';
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['grid', 'compact'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function clearCategory(): void
    {
        $this->category = null;
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearColor(): void
    {
        $this->colorId = null;
        $this->resetPage();
    }

    public function clearPrice(): void
    {
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->resetPage();
    }

    public function clearSort(): void
    {
        $this->sort = 'newest';
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->category = null;
        $this->search = '';
        $this->colorId = null;
        $this->minPrice = null;
        $this->maxPrice = null;
        $this->sort = 'newest';
        $this->resetPage();
    }

    public function render(): View
    {
        $selectedCategory = null;
        if (is_string($this->category) && $this->category !== '') {
            $selectedCategory = ProductCategory::query()
                ->active()
                ->where('slug', $this->category)
                ->first(['id', 'name', 'slug']);
        }

        $query = Product::query()
            ->active()
            ->whereNull('customization_workflow')
            ->purchasable();

        $searchTerm = trim($this->search);
        if ($searchTerm !== '') {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhere('slug', 'like', "%{$searchTerm}%");
            });
        }

        if ($selectedCategory !== null) {
            $query->where('product_category_id', $selectedCategory->id);
        }

        if ($this->colorId) {
            $query->whereHas('colorPrices', function ($q) {
                $q->where('color_id', $this->colorId)->where('is_active', true);
            });
        }

        $effectivePrice = 'COALESCE(
            (SELECT MIN(pcp.price) FROM product_color_prices AS pcp
             WHERE pcp.product_id = products.id AND pcp.is_active = 1),
            products.base_price)';

        $min = (int) $this->minPrice;
        $max = (int) $this->maxPrice;
        if ($min > 0 || $max > 0) {
            $query->where(function ($q) use ($effectivePrice, $min, $max) {
                $q->whereRaw($effectivePrice.' IS NOT NULL');
                if ($min > 0) {
                    $q->whereRaw($effectivePrice.' >= '.$min);
                }
                if ($max > 0) {
                    $q->whereRaw($effectivePrice.' <= '.$max);
                }
            });
        }

        switch ($this->sort) {
            case 'cheapest':
                $query->orderByRaw($effectivePrice.' ASC');
                break;

            case 'expensive':
                $query->orderByRaw($effectivePrice.' DESC');
                break;

            case 'popular':
                $query->withCount([
                    'orderItems as sold_count' => fn ($q) => $q
                        ->whereHas('order', fn ($order) => $order->where('payment_status', PaymentStatusEnum::PAID)),
                ])->orderByDesc('sold_count');
                break;

            default:
                $query->latest();
        }

        $products = $query
            ->with('category:id,name')
            ->withCatalog()
            ->paginate(12);

        $categories = ProductCategory::query()
            ->active()
            ->withCount([
                'products as active_products_count' => fn ($q) => $q
                    ->active()
                    ->whereNull('customization_workflow')
                    ->purchasable(),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $totalActiveProducts = Product::query()
            ->active()
            ->whereNull('customization_workflow')
            ->purchasable()
            ->count();

        $colors = Color::query()->active()->orderBy('sort_order')->get();

        $selectedColor = $this->colorId ? $colors->firstWhere('id', $this->colorId) : null;

        $activeFiltersCount = ($searchTerm !== '' ? 1 : 0)
            + ($selectedCategory !== null ? 1 : 0)
            + ($this->colorId ? 1 : 0)
            + (($min > 0 || $max > 0) ? 1 : 0)
            + ($this->sort !== 'newest' ? 1 : 0);

        return view('livewire.catalog.product-catalog', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'totalActiveProducts' => $totalActiveProducts,
            'colors' => $colors,
            'selectedColor' => $selectedColor,
            'activeFiltersCount' => $activeFiltersCount,
        ]);
    }
}
