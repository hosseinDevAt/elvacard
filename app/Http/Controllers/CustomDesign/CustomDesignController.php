<?php

namespace App\Http\Controllers\CustomDesign;

use App\Enums\CustomizationWorkflowEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductColorPrice;
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
        $hasActivePricing = ProductColorPrice::query()
            ->where(function ($q) {
                $q->where(function ($wq) {
                    $wq->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)
                        ->whereNull('product_id');
                })->orWhereHas('product', fn ($pq) => $pq->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)->where('is_active', true));
            })
            ->where('is_active', true)
            ->exists();

        abort_if(! $hasActivePricing, 404, 'سرویس طراحی کارت بانکی در حال حاضر در دسترس نیست.');

        $product = Product::query()
            ->where('customization_workflow', CustomizationWorkflowEnum::BANK_CARD->value)
            ->where('is_active', true)
            ->first(['id', 'name']);

        $designSlug = $request->query('design');
        $colorId = $request->query('color');
        $variantId = $request->query('variant') ?? $request->query('variant_id');

        return view('custom-design.bank', [
            'product' => $product,
            'workflow' => CustomizationWorkflowEnum::BANK_CARD->value,
            'initialDesignSlug' => is_string($designSlug) && trim($designSlug) !== '' ? trim($designSlug) : null,
            'initialColorId' => is_numeric($colorId) ? (int) $colorId : null,
            'initialVariantId' => is_numeric($variantId) ? (int) $variantId : null,
        ]);
    }

    public function fuel(): View
    {
        $hasActivePricing = ProductColorPrice::query()
            ->where(function ($q) {
                $q->where(function ($wq) {
                    $wq->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
                        ->whereNull('product_id');
                })->orWhereHas('product', fn ($pq) => $pq->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)->where('is_active', true));
            })
            ->where('is_active', true)
            ->exists();

        abort_if(! $hasActivePricing, 404, 'هیچ محصول کارت سوخت قابل سفارشی‌سازی فعالی یافت نشد.');

        $product = Product::query()
            ->where('customization_workflow', CustomizationWorkflowEnum::FUEL_CARD->value)
            ->where('is_active', true)
            ->first(['id', 'name']);

        return view('custom-design.fuel', [
            'product' => $product,
            'workflow' => CustomizationWorkflowEnum::FUEL_CARD->value,
        ]);
    }
}
