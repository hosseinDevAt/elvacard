<?php

namespace App\Http\Controllers\Cms;

use App\Enums\ArticleStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleSlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = $this->publishedQuery()
            ->with('category')
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('cms.articles.index', [
            'articles' => $articles,
        ]);
    }

    public function category(string $slug): View
    {
        $category = ArticleCategory::query()
            ->where('slug', $slug)
            ->firstOrFail();

        $articles = $this->publishedQuery()
            ->where('article_category_id', $category->id)
            ->with('category')
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('cms.articles.index', [
            'articles' => $articles,
            'category' => $category,
        ]);
    }

    public function show(string $slug): View|RedirectResponse
    {
        $article = $this->publishedQuery()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if ($article !== null) {
            return view('cms.articles.show', [
                'article' => $article,
            ]);
        }

        return $this->resolveHistoricalSlug($slug);
    }

    /**
     * Only an existing, published article may be the target of a 301; a draft,
     * future-scheduled, deleted or unpublished article keeps its URLs dead
     * (404) and never leaks onto another article.
     */
    private function resolveHistoricalSlug(string $slug): RedirectResponse
    {
        $history = ArticleSlugHistory::query()
            ->where('slug', $slug)
            ->first();

        if ($history === null || $history->article_id === null) {
            abort(404);
        }

        $article = $this->publishedQuery()
            ->whereKey($history->article_id)
            ->first();

        if ($article === null || $article->slug === $slug) {
            abort(404);
        }

        return redirect()->route('articles.show', $article->slug, 301);
    }

    private function publishedQuery(): Builder
    {
        return Article::query()
            ->where('status', ArticleStatusEnum::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }
}
