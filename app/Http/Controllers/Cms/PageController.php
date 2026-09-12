<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('cms.pages.show', [
            'page' => $page,
        ]);
    }
}