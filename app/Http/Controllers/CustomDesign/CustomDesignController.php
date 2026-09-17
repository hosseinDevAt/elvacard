<?php

namespace App\Http\Controllers\CustomDesign;

use App\Enums\CustomizationWorkflowEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

class CustomDesignController extends Controller
{
    public function landing(): View
    {
        return view('custom-design.landing');
    }

    public function bank(): View
    {
        $product = Product::query()
            ->active()
            ->purchasable()
            ->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->orderBy('id')
            ->first(['id', 'name']);

        abort_if($product === null, 404, 'سرویس طراحی کارت بانکی در حال حاضر در دسترس نیست.');

        return view('custom-design.bank', ['product' => $product]);
    }

    public function fuel(): View
    {
        return view('custom-design.fuel');
    }
}
