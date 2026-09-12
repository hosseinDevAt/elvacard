<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type');
        $colorId = $request->integer('color_id');
        $minPrice = $request->integer('min_price');
        $maxPrice = $request->integer('max_price');
        $sort = $request->query('sort', 'newest');

        $validSorts = ['newest', 'cheapest', 'expensive', 'popular'];
        if (! in_array($sort, $validSorts, true)) {
            $sort = 'newest';
        }

        $query = Product::query()
            ->active();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($type) {
            $query->ofType($type);
        }

        if ($colorId) {
            $query->whereHas('colorPrices', function ($q) use ($colorId) {
                $q->where('color_id', $colorId)->where('is_active', true);
            });
        }

        if ($minPrice > 0 || $maxPrice > 0) {
            $query->whereHas('colorPrices', function ($q) use ($minPrice, $maxPrice) {
                $q->where('is_active', true);
                if ($minPrice > 0) {
                    $q->where('price', '>=', $minPrice);
                }
                if ($maxPrice > 0) {
                    $q->where('price', '<=', $maxPrice);
                }
            });
        }

        switch ($sort) {
            case 'cheapest':
                $query->orderByRaw('(SELECT MIN(pcp.price) FROM product_color_prices AS pcp WHERE pcp.product_id = products.id AND pcp.is_active = 1) ASC');
                break;

            case 'expensive':
                $query->orderByRaw('(SELECT MIN(pcp.price) FROM product_color_prices AS pcp WHERE pcp.product_id = products.id AND pcp.is_active = 1) DESC');
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
            ->withCatalog()
            ->paginate(12)
            ->withQueryString();

        return view('catalog.products.index', [
            'products' => $products,
            'selectedType' => $type,
            'selectedColorId' => $colorId,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'search' => $search,
            'sort' => $sort,
            'types' => ProductTypeEnum::cases(),
            'colors' => Color::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function designLanding(): View
    {
        $categories = CateDesign::query()
            ->active()
            ->withCount(['designs' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        $totalDesigns = $categories->sum('designs_count');

        return view('catalog.designs.landing', [
            'categories' => $categories,
            'totalDesigns' => $totalDesigns,
            'totalColors' => Color::query()->active()->count(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $selectedColorId = $request->integer('color_id');

        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->withCatalog()
            ->firstOrFail();

        $designCatalog = $this->designCatalogQuery($selectedColorId);

        return view('catalog.products.show', [
            'product' => $product,
            'designCatalog' => $designCatalog,
            'selectedColorId' => $selectedColorId,
        ]);
    }

    public function designCatalog(Request $request): View
    {
        $selectedColorId = $request->integer('color_id');
        $categorySlug = $request->query('category');

        $catalog = $this->designCatalogQuery($selectedColorId, $categorySlug);

        return view('catalog.designs.index', [
            'catalog' => $catalog,
            'selectedColorId' => $selectedColorId,
            'selectedCategory' => $categorySlug,
        ]);
    }

    private function designCatalogQuery(?int $selectedColorId = null, ?string $categorySlug = null)
    {
        return CateDesign::query()
            ->active()
            ->when($categorySlug, fn ($query) => $query->where('slug', $categorySlug))
            ->with([
                'designs' => fn ($designQuery) => $designQuery
                    ->active()
                    ->with([
                        'images' => fn ($imageQuery) => $imageQuery
                            ->active()
                            ->when($selectedColorId, function ($query) use ($selectedColorId) {
                                $query->whereHas('compatibilities', function ($compatibilityQuery) use ($selectedColorId) {
                                    $compatibilityQuery
                                        ->where('card_color_id', $selectedColorId)
                                        ->where('is_allowed', true);
                                });
                            })
                            ->with([
                                'color' => fn ($colorQuery) => $colorQuery->active(),
                                'compatibilities' => fn ($compatibilityQuery) => $compatibilityQuery
                                    ->where('is_allowed', true)
                                    ->with('cardColor'),
                            ])
                            ->orderBy('sort_order'),
                    ])
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();
    }
}
