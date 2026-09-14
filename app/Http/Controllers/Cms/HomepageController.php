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
        $productIds = [];
        $designIds = [];
        $faqLimit = null;
        $newestLimit = null;
        $articlesLimit = null;
        $featuredDesignsLimit = null;

        foreach ($sections as $section) {
            $settings = is_array($section->settings) ? $section->settings : [];

            match ($section->section_type?->value) {
                'featured_products' => $productIds = array_merge($productIds, (array) ($settings['product_ids'] ?? [])),
                'featured_designs' => $designIds = array_merge($designIds, (array) ($settings['design_ids'] ?? [])),
                'faq' => $faqLimit = $settings['limit'] ?? null,
                'newest_products' => $newestLimit = $settings['limit'] ?? null,
                'articles' => $articlesLimit = $settings['limit'] ?? null,
                default => null,
            };

            if ($section->section_type?->value === 'featured_designs') {
                $featuredDesignsLimit = $settings['limit'] ?? null;
            }
        }

        $products = $this->productsByIds($productIds);

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
                ->when($featuredDesignsLimit, fn ($collection) => $collection->take((int) $featuredDesignsLimit))
                ->values();
        }

        $designs = $designs->keyBy('id');

        $faqs = $this->faqs($faqLimit);

        $articles = collect();
        if ($articlesLimit !== null) {
            $articles = Article::query()
                ->where('status', ArticleStatusEnum::PUBLISHED->value)
                ->where(function ($q) {
                    $q->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->with('category')
                ->orderBy('published_at', 'desc')
                ->orderBy('id', 'desc')
                ->when($articlesLimit, fn ($q) => $q->limit($articlesLimit))
                ->get();
        }

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
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get();
    }

    private function faqs(?int $faqLimit)
    {
        return FaqItem::query()
            ->active()
            ->orderBy('sort_order')
            ->when($faqLimit, fn ($q) => $q->limit($faqLimit))
            ->get();
    }
}
