<?php

namespace App\Http\Controllers\CustomDesign;

use App\Enums\CustomizationWorkflowEnum;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Customization\ProductPurchaseabilityService;
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
        abort_unless(ProductPurchaseabilityService::isWorkflowPurchasable(CustomizationWorkflowEnum::BANK_CARD), 404, 'سرویس طراحی کارت بانکی در حال حاضر در دسترس نیست.');

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
        abort_unless(ProductPurchaseabilityService::isWorkflowPurchasable(CustomizationWorkflowEnum::FUEL_CARD), 404, 'هیچ محصول کارت سوخت قابل سفارشی‌سازی فعالی یافت نشد.');

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
