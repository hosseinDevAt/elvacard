<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\CustomizationWorkflowEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\ProductTypeEnum;
use App\Http\Controllers\Controller;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\DesignColorCompatibility;
use App\Models\Product;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\DesignCatalogService;
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
            ->active()
            ->purchasable();

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

        // The effective price is the lowest active color price when one exists,
        // otherwise the base price: exactly what the product card shows.
        $effectivePrice = 'COALESCE(
            (SELECT MIN(pcp.price) FROM product_color_prices AS pcp
             WHERE pcp.product_id = products.id AND pcp.is_active = 1),
            products.base_price)';

        if ($minPrice > 0 || $maxPrice > 0) {
            $query->where(function ($q) use ($effectivePrice, $minPrice, $maxPrice) {
                $q->whereRaw($effectivePrice.' IS NOT NULL');
                if ($minPrice > 0) {
                    $q->whereRaw($effectivePrice.' >= '.$minPrice);
                }
                if ($maxPrice > 0) {
                    $q->whereRaw($effectivePrice.' <= '.$maxPrice);
                }
            });
        }

        switch ($sort) {
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

        $workflowRaw = $product->getRawOriginal('customization_workflow');
        $workflow = $workflowRaw !== null ? CustomizationWorkflowEnum::tryFrom((string) $workflowRaw) : null;
        $hasCustomization = $workflowRaw !== null;
        $customizationAvailable = $workflow !== null && CustomizationWorkflowRegistry::isActive($workflow);

        // The storefront must never render a product the checkout cannot charge
        // (broken price path, missing design path, broken fuel readiness). The
        // only exception is the "not launched yet" amber state, which is itself
        // a non-actionable page kept deliberately visible.
        $purchasable = false;
        if (! $hasCustomization || $customizationAvailable) {
            $purchasable = ProductPurchaseabilityService::isPurchasable($product->id);
            abort_unless($purchasable, 404);
        }

        return view('catalog.products.show', [
            'product' => $product,
            'selectedColorId' => $selectedColorId,
            'hasCustomization' => $hasCustomization,
            'customizationAvailable' => $customizationAvailable,
            'purchasable' => $purchasable,
        ]);
    }

    public function designCatalog(Request $request): View
    {
        $selectedColorId = $request->integer('color_id');
        $categorySlug = $request->query('category');

        $categoryId = null;
        $selectedCategoryName = null;

        if (is_string($categorySlug) && $categorySlug !== '') {
            $category = CateDesign::query()
                ->active()
                ->where('slug', $categorySlug)
                ->first(['id', 'name']);

            if ($category) {
                $categoryId = (int) $category->id;
                $selectedCategoryName = $category->name;
            }
        }

        $allowedImageIds = null;
        if ($selectedColorId > 0) {
            $allowedImageIds = DesignColorCompatibility::query()
                ->where('card_color_id', $selectedColorId)
                ->where('is_allowed', true)
                ->pluck('design_image_id');
        }

        $catalog = app(DesignCatalogService::class)->paginatePublic(
            $categoryId,
            $selectedColorId > 0 ? $selectedColorId : null,
            $allowedImageIds,
        )->withQueryString();

        return view('catalog.designs.index', [
            'catalog' => $catalog,
            'selectedColorId' => $selectedColorId > 0 ? $selectedColorId : null,
            'selectedCategory' => $categorySlug,
            'selectedCategoryName' => $selectedCategoryName,
        ]);
    }
}
