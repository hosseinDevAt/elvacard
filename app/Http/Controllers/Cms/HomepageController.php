<?php

namespace App\Http\Controllers\Cms;

use App\Enums\ArticleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\DesignCatalogService;
use Illuminate\Contracts\View\View;

class HomepageController extends Controller
{
    public function index(): View
    {
        $sections = HomepageSection::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        $sectionData = $this->loadSectionData($sections);

        return view('cms.homepage.index', [
            'sections' => $sections,
            'sectionData' => $sectionData,
        ]);
    }

    private function loadSectionData($sections): array
    {
        $limits = [
            'featured_products' => 8,
            'featured_designs' => 8,
            'faq' => 8,
            'newest_products' => 8,
            'articles' => 3,
        ];

        $productIds = [];
        $designIds = [];

        foreach ($sections as $section) {
            $settings = is_array($section->settings) ? $section->settings : [];

            $type = $section->section_type?->value;

            if ($type !== null && array_key_exists($type, $limits)) {
                $limits[$type] = (int) ($settings['limit'] ?? $limits[$type]);
            }

            match ($type) {
                'featured_products' => $productIds = array_merge($productIds, (array) ($settings['product_ids'] ?? [])),
                'featured_designs' => $designIds = array_merge($designIds, (array) ($settings['design_ids'] ?? [])),
                default => null,
            };
        }

        $featuredProductsLimit = $limits['featured_products'];
        $featuredDesignsLimit = $limits['featured_designs'];
        $faqLimit = $limits['faq'];
        $newestLimit = $limits['newest_products'];
        $articlesLimit = $limits['articles'];

        $products = $this->productsByIds($productIds)->take($featuredProductsLimit);

        $newestProducts = $this->newestProducts($newestLimit);

        $designs = app(DesignCatalogService::class)
            ->visibleDesigns($designIds)
            ->load([
                'images' => fn ($q) => $q
                    ->active()
                    ->with(['color' => fn ($cq) => $cq->active()])
                    ->orderBy('sort_order'),
                'category' => fn ($cq) => $cq->active(),
            ]);

        if ($designIds === []) {
            $designs = $designs
                ->sortBy(fn ($design) => [$design->sort_order, -$design->id])
                ->take($featuredDesignsLimit)
                ->values();
        }

        $designs = $designs->keyBy('id');

        $faqs = $this->faqs($faqLimit);

        $articles = Article::query()
            ->where('status', ArticleStatusEnum::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($articlesLimit)
            ->get();

        return [
            'featured_products' => ['products' => $products],
            'featured_designs' => ['designs' => $designs],
            'faq' => ['faqs' => $faqs],
            'newest_products' => ['products' => $newestProducts],
            'articles' => ['articles' => $articles],
        ];
    }

    private function productsByIds(array $productIds)
    {
        if (! $productIds) {
            return collect();
        }

        return Product::query()
            ->active()
            ->purchasable()
            ->whereIn('id', array_unique($productIds))
            ->withCatalog()
            ->get()
            ->keyBy('id');
    }

    private function newestProducts(?int $limit)
    {
        return Product::query()
            ->active()
            ->purchasable()
            ->withCatalog()
            ->limit((int) $limit)
            ->get();
    }

    private function faqs(?int $faqLimit)
    {
        return FaqItem::query()
            ->active()
            ->orderBy('sort_order')
            ->limit((int) $faqLimit)
            ->get();
    }
}
