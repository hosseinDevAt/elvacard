<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->where('status', \App\Enums\ArticleStatusEnum::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->with('category')
            ->latest('published_at')
            ->paginate(10)
            ->withQueryString();

        return view('cms.articles.index', [
            'articles' => $articles,
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()
            ->where('status', \App\Enums\ArticleStatusEnum::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->where('slug', $slug)
            ->with('category')
            ->firstOrFail();

        return view('cms.articles.show', [
            'article' => $article,
        ]);
    }
}