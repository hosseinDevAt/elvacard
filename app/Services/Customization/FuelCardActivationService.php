<?php

namespace App\Services\Customization;

use App\Enums\CustomizationWorkflowEnum;
use App\Models\DesignColorCompatibility;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\DesignCatalogService;

class FuelCardActivationService
{
    /**
     * Full activation gate, expressed as Persian blocker messages. An empty
     * result means activation is allowed. Every decision reads the database
     * (workflow column, ProductColorPrice rows, design catalog) - never the
     * submitted workflow, color count, or design ids.
     */
    public static function activationBlockers(?int $productId): array
    {
        if ($productId === null) {
            return ['محصول کارت سوخت را ابتدا بدون فعال‌سازی ذخیره کنید، سپس رنگ، قیمت و طرح کارت را تعریف کرده و در نهایت آن را فعال کنید.'];
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return ['محصول یافت نشد.'];
        }

        if ((string) $product->getRawOriginal('customization_workflow') !== CustomizationWorkflowEnum::FUEL_CARD->value) {
            return ['برای فعال‌سازی کارت سوخت، ابتدا محصول را بدون فعال‌سازی به فرآیند سوخت منتقل کنید؛ سپس رنگ، قیمت و طرح را تعریف کرده و در نهایت آن را فعال کنید.'];
        }

        return self::readinessErrors($productId);
    }

    /**
     * Database-backed readiness invariants for a fuel product. An empty result
     * means the product is activation-ready.
     */
    public static function readinessErrors(int $productId): array
    {
        $product = Product::query()->find($productId);

        if ($product === null) {
            return ['محصول یافت نشد.'];
        }

        if ((string) $product->getRawOriginal('customization_workflow') !== CustomizationWorkflowEnum::FUEL_CARD->value) {
            return ['فرآیند شخصی‌سازی محصول باید کارت سوخت باشد تا بتوان آن را فعال کرد.'];
        }

        if (blank($product->getAttribute('name'))) {
            return ['نام محصول معتبر نیست؛ ابتدا محصول را تکمیل کنید.'];
        }

        $errors = [];

        $activeColorPrices = ProductColorPrice::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->with('color')
            ->get();

        if ($activeColorPrices->isEmpty()) {
            $errors[] = 'کارت سوخت به یک رنگ فعال نیاز دارد؛ در «قیمت رنگ محصولات» یک رنگ فعال تعریف کنید.';
        } elseif ($activeColorPrices->count() > 1) {
            $errors[] = 'کارت سوخت باید دقیقاً یک رنگ و قیمت فعال داشته باشد؛ رنگ‌های فعال اضافی را غیرفعال کنید.';
        } else {
            $color = $activeColorPrices->first()->color;

            if ($color === null || ! (bool) $color->is_active) {
                $errors[] = 'رنگ فعال محصول باید در فهرست رنگ‌ها نیز فعال باشد؛ ابتدا رنگ را در بخش رنگ‌ها فعال کنید.';
            } else {
                $allowedImageIds = DesignColorCompatibility::query()
                    ->where('card_color_id', $color->id)
                    ->where('is_allowed', true)
                    ->pluck('design_image_id');

                if (! app(DesignCatalogService::class)->hasPurchasableDesign(null, $allowedImageIds)) {
                    $errors[] = 'کارت سوخت به حداقل یک طرح قابل خرید نیاز دارد؛ طرح باید فعال، در دسته فعال، و دارای تصویر فعال مجاز برای رنگ کارت باشد.';
                }
            }
        }

        return $errors;
    }
}
