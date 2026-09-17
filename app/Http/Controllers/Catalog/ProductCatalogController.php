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
use App\Models\ProductSlugHistory;
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

    public function show(Request $request, string $slug)
    {
        $selectedColorId = $request->integer('color_id');

        $product = Product::query()
            ->active()
            ->where('slug', $slug)
            ->withCatalog()
            ->first();

        if ($product === null) {
            return $this->resolveHistoricalSlug($slug);
        }

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

        $canonicalUrl = $this->canonicalUrl($product);

        return view('catalog.products.show', [
            'product' => $product,
            'selectedColorId' => $selectedColorId,
            'hasCustomization' => $hasCustomization,
            'customizationAvailable' => $customizationAvailable,
            'purchasable' => $purchasable,
            'canonicalUrl' => $canonicalUrl,
            'ogImageUrl' => $this->publicAssetUrl($product->og_image ?: $product->main_image),
            'schemaJson' => $this->productSchemaJson($product, $purchasable, $canonicalUrl),
        ]);
    }

    /**
     * Old slugs keep their permanent reservation. Only an existing, active
     * product may be the target of a 301; a deleted or deactivated product
     * keeps its URLs dead (404) and never leaks onto another product.
     */
    private function resolveHistoricalSlug(string $slug)
    {
        $history = ProductSlugHistory::query()->where('slug', $slug)->first();

        if ($history === null || $history->product_id === null) {
            abort(404);
        }

        $product = Product::query()
            ->active()
            ->whereKey($history->product_id)
            ->first();

        if ($product === null || $product->slug === $slug) {
            abort(404);
        }

        return redirect()->route('catalog.products.show', $product->slug, 301);
    }

    private function canonicalUrl(Product $product): string
    {
        $scheme = $product->canonical_url !== null && $product->canonical_url !== ''
            ? parse_url($product->canonical_url, PHP_URL_SCHEME)
            : null;

        if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
            return $product->canonical_url;
        }

        return route('catalog.products.show', $product->slug);
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $scheme = parse_url($path, PHP_URL_SCHEME);

        if (is_string($scheme) && in_array(strtolower($scheme), ['http', 'https'], true)) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    private function productSchemaJson(Product $product, bool $purchasable, string $canonicalUrl): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'url' => $canonicalUrl,
        ];

        $description = $product->meta_description ?: $product->description;

        if ($description !== null && $description !== '') {
            $schema['description'] = $description;
        }

        $imageUrl = $this->publicAssetUrl($product->main_image ?: $product->og_image);

        if ($imageUrl !== null) {
            $schema['image'] = $imageUrl;
        }

        $offers = $this->productOffers($product, $purchasable);

        if ($offers !== null) {
            $schema['offers'] = $offers;
        }

        return json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    /**
     * Prices are color-aware, so a single flat price would be misleading. The
     * checkout charges the selected color price, or the base price when no
     * color is chosen on a commerce product; card products always charge the
     * color price. The structured data mirrors that truthful price set: one
     * Offer for a single effective price, otherwise an AggregateOffer with the
     * real low/high range of the buyable variants. Unpurchasable pages expose
     * no offer at all.
     */
    private function productOffers(Product $product, bool $purchasable): ?array
    {
        if (! $purchasable) {
            return null;
        }

        $prices = $product->colorPrices->pluck('price')->map(fn ($price) => (int) $price)->all();

        if ($product->customization_workflow === null && $product->base_price !== null) {
            $prices[] = (int) $product->base_price;
        }

        $prices = array_values(array_unique($prices));
        sort($prices);

        if ($prices === []) {
            return null;
        }

        if (count($prices) === 1) {
            return [
                '@type' => 'Offer',
                'price' => $prices[0],
                'priceCurrency' => 'IRT',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return [
            '@type' => 'AggregateOffer',
            'lowPrice' => $prices[0],
            'highPrice' => $prices[count($prices) - 1],
            'priceCurrency' => 'IRT',
            'offerCount' => count($prices),
            'availability' => 'https://schema.org/InStock',
        ];
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
