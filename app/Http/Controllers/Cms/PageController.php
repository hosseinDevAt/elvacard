<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\PageSlugHistory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    public function show(string $slug): View|RedirectResponse
    {
        $page = Page::query()
            ->active()
            ->where('slug', $slug)
            ->first();

        if ($page !== null) {
            return view('cms.pages.show', [
                'page' => $page,
            ]);
        }

        return $this->resolveHistoricalSlug($slug);
    }

    /**
     * Old slugs keep their permanent reservation. Only an existing, active
     * page may be the target of a 301; a deleted or deactivated page keeps its
     * URLs dead (404) and never leaks onto another page.
     */
    private function resolveHistoricalSlug(string $slug): RedirectResponse
    {
        $history = PageSlugHistory::query()
            ->where('slug', $slug)
            ->first();

        if ($history === null || $history->page_id === null) {
            abort(404);
        }

        $page = Page::query()
            ->active()
            ->whereKey($history->page_id)
            ->first();

        if ($page === null || $page->slug === $slug) {
            abort(404);
        }

        return redirect()->route('pages.show', $page->slug, 301);
    }
}
