<?php

namespace App\Services\Customization;

use App\Enums\CustomizationWorkflowEnum;
use App\Models\CateDesign;
use App\Models\Color;
use App\Models\Design;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\DesignCatalogService;
use Illuminate\Support\Collection;

class ProductPurchaseabilityService
{
    /**
     * Activation gate for customization products. Reads the database only, so
     * no client-hydrated property can vouch for readiness. An empty result
     * means activation is allowed. A null product id means the product is not
     * persisted yet and must be saved inactive before its price and design are
     * defined.
     */
    public static function activationBlockers(?int $productId): array
    {
        if ($productId === null) {
            return ['محصول کارت بانکی را ابتدا بدون فعال‌سازی ذخیره کنید؛ سپس رنگ، قیمت و طرح کارت را تعریف کرده و در نهایت آن را فعال کنید.'];
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return ['محصول یافت نشد.'];
        }

        $workflow = $product->customization_workflow;

        if ($workflow === null) {
            return [];
        }

        if (! CustomizationWorkflowRegistry::isActive($workflow)) {
            return ['فرآیند شخصی‌سازی این محصول فعال نیست؛ محصول قابل فروش نیست و نمی‌تواند فعال ذخیره شود.'];
        }

        if ($workflow === CustomizationWorkflowEnum::FUEL_CARD) {
            return FuelCardActivationService::readinessErrors($productId);
        }

        return self::bankCardReadinessErrors($productId);
    }

    /**
     * Whether removing the given price row (or, for an edit, having the row
     * stop being an active source) would leave an active product without any
     * purchasable price source. Returns a blocking Persian message when that
     * would happen, null otherwise. Fuel invariants keep their dedicated
     * guards and inactive products are never constrained.
     */
    public static function priceRowChangeBlocker(int $productId, ?int $excludePriceId, ?int $rowColorId, bool $rowActive): ?string
    {
        $product = Product::query()->find($productId);

        if ($product === null || ! $product->is_active) {
            return null;
        }

        if ($product->customization_workflow === CustomizationWorkflowEnum::FUEL_CARD) {
            return null;
        }

        $beforeColors = self::activeCardColors($productId);

        $afterColors = ProductColorPrice::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->when($excludePriceId !== null, fn ($query) => $query->where('id', '!=', $excludePriceId))
            ->whereHas('color', fn ($query) => $query->where('is_active', true))
            ->pluck('color_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (
            $excludePriceId !== null
            && $rowActive
            && $rowColorId !== null
            && Color::query()->whereKey($rowColorId)->where('is_active', true)->exists()
        ) {
            $afterColors[] = $rowColorId;
        }

        $afterColors = array_values(array_unique($afterColors));

        if (self::productIsPurchasable($product, $beforeColors) && ! self::productIsPurchasable($product, $afterColors)) {
            return 'این محصول فعال فقط یک مسیر قیمت رنگ معتبر دارد؛ ابتدا یک رنگ و قیمت جایگزین تعریف کنید یا محصول را غیرفعال کنید.';
        }

        return null;
    }

    /**
     * Whether deactivating the color would remove the only purchasable price
     * path of one or more active products. Returns a blocking Persian message
     * when it would, null otherwise.
     */
    public static function colorDeactivationBlocker(int $colorId): ?string
    {
        $color = Color::query()->find($colorId);

        if ($color === null || ! $color->is_active) {
            return null;
        }

        $affected = [];

        Product::query()
            ->active()
            ->whereHas('colorPrices', fn ($query) => $query->where('color_id', $colorId)->where('is_active', true))
            ->get(['id', 'name', 'base_price', 'customization_workflow'])
            ->each(function (Product $product) use ($colorId, &$affected) {
                $beforeColors = self::activeCardColors($product->id);
                $afterColors = array_values(array_filter($beforeColors, fn ($id) => (int) $id !== (int) $colorId));

                if (self::productIsPurchasable($product, $beforeColors) && ! self::productIsPurchasable($product, $afterColors)) {
                    $affected[] = $product->name;
                }
            });

        return self::formatAffectedMessage(
            $affected,
            'این رنگ تنها مسیر خرید معتبر این محصولات فعال است و قابل غیرفعال‌سازی نیست:'
        );
    }

    /**
     * Whether deactivating the design would remove the only purchasable design
     * of one or more active products. Returns a blocking Persian message when
     * it would, null otherwise.
     */
    public static function designDeactivationBlocker(int $designId): ?string
    {
        $design = Design::query()->find($designId);

        if ($design === null || ! $design->is_active) {
            return null;
        }

        return self::formatAffectedMessage(
            self::namesBrokenByDesignRemoval([$designId]),
            'این طرح، تنها طرح قابل خرید این محصولات فعال است و با غیرفعال‌سازی آن امکان خرید وجود ندارد:'
        );
    }

    /**
     * Whether deactivating the category would remove the only purchasable
     * design of one or more active products. Returns a blocking Persian
     * message when it would, null otherwise.
     */
    public static function categoryDeactivationBlocker(int $categoryId): ?string
    {
        $category = CateDesign::query()->find($categoryId);

        if ($category === null || ! $category->is_active) {
            return null;
        }

        $designIds = $category->designs()->pluck('id')->all();

        if ($designIds === []) {
            return null;
        }

        return self::formatAffectedMessage(
            self::namesBrokenByDesignRemoval($designIds),
            'این دسته‌بندی شامل تنها طرح قابل خرید این محصولات فعال است و قابل غیرفعال‌سازی نیست:'
        );
    }

    /**
     * Whether deleting the image would remove the last active image of its
     * design or the only allowed design option of one or more active products.
     * Returns a blocking Persian message when it would, null otherwise.
     */
    public static function designImageRemovalBlocker(int $designImageId): ?string
    {
        $image = DesignImage::with('design')->find($designImageId);

        if ($image === null || ! $image->is_active) {
            return null;
        }

        $activeImageCount = DesignImage::query()
            ->where('design_id', $image->design_id)
            ->where('is_active', true)
            ->count();

        if ($activeImageCount === 1) {
            return 'این تصویر آخرین تصویر فعال طرح است؛ پیش از حذف، ابتدا یک تصویر فعال جایگزین برای طرح تعریف کنید.';
        }

        return self::formatAffectedMessage(
            self::namesBrokenByImageRemoval([$image->id]),
            'این تصویر تنها گزینه مجاز طرح برای این محصولات فعال است و قابل حذف نیست:'
        );
    }

    /**
     * Whether disallowing the compatibility would remove the only allowed
     * design option of one or more active products. Returns a blocking
     * Persian message when it would, null otherwise.
     */
    public static function compatibilityRemovalBlocker(int $designImageId, int $cardColorId): ?string
    {
        $compatibility = DesignColorCompatibility::query()
            ->where('design_image_id', $designImageId)
            ->where('card_color_id', $cardColorId)
            ->first();

        if ($compatibility === null || ! $compatibility->is_allowed) {
            return null;
        }

        $affected = [];

        Product::query()
            ->active()
            ->whereNotNull('customization_workflow')
            ->whereHas('colorPrices', fn ($query) => $query->where('color_id', $cardColorId)->where('is_active', true))
            ->get(['id', 'name'])
            ->each(function (Product $product) use ($designImageId, &$affected) {
                $colors = self::activeCardColors($product->id);

                if ($colors === []) {
                    return;
                }

                if (self::hasPurchasableDesignForColors($colors) && ! self::hasPurchasableDesignForColors($colors, [$designImageId])) {
                    $affected[] = $product->name;
                }
            });

        return self::formatAffectedMessage(
            $affected,
            'این سازگاری تنها گزینه مجاز طرح برای رنگ کارت این محصولات فعال است و قابل حذف نیست:'
        );
    }

    private static function bankCardReadinessErrors(int $productId): array
    {
        $errors = [];

        $activeColors = self::activeCardColors($productId);

        if ($activeColors === []) {
            $errors[] = 'کارت بانکی به حداقل یک رنگ و قیمت فعال نیاز دارد؛ در «قیمت رنگ محصولات» یک رنگ فعال تعریف کنید.';
        } elseif (! self::hasPurchasableDesignForColors($activeColors)) {
            $errors[] = 'کارت بانکی به حداقل یک طرح قابل خرید نیاز دارد؛ طرح باید فعال، در دسته فعال، و دارای تصویر فعال مجاز برای یکی از رنگ‌های فعال محصول باشد.';
        }

        return $errors;
    }

    private static function productIsPurchasable(Product $product, array $cardColorIds): bool
    {
        if ($product->customization_workflow === null) {
            if ($product->base_price !== null) {
                return true;
            }

            return $cardColorIds !== [];
        }

        return self::hasPurchasableDesignForColors($cardColorIds);
    }

    private static function activeCardColors(int $productId): array
    {
        return ProductColorPrice::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->whereHas('color', fn ($query) => $query->where('is_active', true))
            ->pluck('color_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private static function allowedImageIdsForColor(int $cardColorId): Collection
    {
        return DesignColorCompatibility::query()
            ->where('card_color_id', $cardColorId)
            ->where('is_allowed', true)
            ->pluck('design_image_id');
    }

    private static function hasPurchasableDesignForColors(array $colorIds, array $excludedImageIds = [], array $excludedDesignIds = []): bool
    {
        if ($colorIds === []) {
            return false;
        }

        $excludedDesignImages = $excludedDesignIds === []
            ? []
            : DesignImage::query()->whereIn('design_id', $excludedDesignIds)->pluck('id')->all();

        foreach ($colorIds as $colorId) {
            $allowed = self::allowedImageIdsForColor((int) $colorId);

            if ($excludedImageIds !== []) {
                $allowed = $allowed->diff($excludedImageIds);
            }

            if ($excludedDesignImages !== []) {
                $allowed = $allowed->diff($excludedDesignImages);
            }

            if (app(DesignCatalogService::class)->hasPurchasableDesign(null, $allowed)) {
                return true;
            }
        }

        return false;
    }

    private static function namesBrokenByDesignRemoval(array $designIds): array
    {
        $affected = [];

        Product::query()
            ->active()
            ->whereNotNull('customization_workflow')
            ->get(['id', 'name'])
            ->each(function (Product $product) use ($designIds, &$affected) {
                $colors = self::activeCardColors($product->id);

                if ($colors === []) {
                    return;
                }

                if (self::hasPurchasableDesignForColors($colors) && ! self::hasPurchasableDesignForColors($colors, [], $designIds)) {
                    $affected[] = $product->name;
                }
            });

        return $affected;
    }

    private static function namesBrokenByImageRemoval(array $imageIds): array
    {
        $affected = [];

        Product::query()
            ->active()
            ->whereNotNull('customization_workflow')
            ->get(['id', 'name'])
            ->each(function (Product $product) use ($imageIds, &$affected) {
                $colors = self::activeCardColors($product->id);

                if ($colors === []) {
                    return;
                }

                if (self::hasPurchasableDesignForColors($colors) && ! self::hasPurchasableDesignForColors($colors, $imageIds)) {
                    $affected[] = $product->name;
                }
            });

        return $affected;
    }

    private static function formatAffectedMessage(array $productNames, string $lead): ?string
    {
        if ($productNames === []) {
            return null;
        }

        $names = array_slice($productNames, 0, 3);

        return $lead.' '.implode('، ', $names).'. ابتدا مسیر جایگزین را تعریف کنید یا محصولات را غیرفعال کنید.';
    }
}
