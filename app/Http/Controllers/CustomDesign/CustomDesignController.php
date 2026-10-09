<?php

namespace App\Http\Controllers\CustomDesign;

use App\Enums\CustomizationWorkflowEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;

use Illuminate\Http\Request;

class CustomDesignController extends Controller
{
    public function landing(): View
    {
        return view('custom-design.landing');
    }

    public function bank(Request $request): View
    {
        $product = Product::query()
            ->active()
            ->purchasable()
            ->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->orderBy('id')
            ->first(['id', 'name']);

        abort_if($product === null, 404, 'سرویس طراحی کارت بانکی در حال حاضر در دسترس نیست.');

        $designSlug = $request->query('design');
        $colorId = $request->query('color');
        $variantId = $request->query('variant') ?? $request->query('variant_id');

        return view('custom-design.bank', [
            'product' => $product,
            'initialDesignSlug' => is_string($designSlug) && trim($designSlug) !== '' ? trim($designSlug) : null,
            'initialColorId' => is_numeric($colorId) ? (int) $colorId : null,
            'initialVariantId' => is_numeric($variantId) ? (int) $variantId : null,
        ]);
    }

    public function fuel(): View
    {
        $product = Product::query()
            ->active()
            ->purchasable()
            ->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->orderBy('id')
            ->first(['id', 'name']);

        abort_if($product === null, 404, 'هیچ محصول کارت سوخت قابل سفارشی‌سازی فعالی یافت نشد.');

        return view('custom-design.fuel', ['product' => $product]);
    }
}
