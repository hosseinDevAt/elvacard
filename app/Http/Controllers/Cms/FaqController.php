<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\FaqItem;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = FaqItem::query()
            ->active()
            ->orderBy('sort_order')
            ->get();

        return view('cms.faq.index', [
            'faqs' => $faqs,
        ]);
    }
}