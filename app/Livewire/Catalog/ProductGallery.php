<?php

namespace App\Livewire\Catalog;

use App\Models\Product;
use App\Services\Customization\ProductPurchaseabilityService;
use Livewire\Component;

class ProductGallery extends Component
{
    public int $product_id;

    public ?int $color_id = null;

    public int $quantity = 1;

    public int $selected_image_index = 0;

    public function mount(int $productId, ?int $colorId = null): void
    {
        $product = $this->resolveProduct($productId);

        if ($this->notMountable($product, $productId)) {
            abort(404);
        }

        $this->product_id = $productId;

        // The default color is established exactly once, before any render: an
        // explicit valid selection is honored, anything else (including stale or
        // foreign ids) falls back to the first active variant.
        $this->color_id = $colorId !== null && $product->colorPrices->contains(fn ($colorPrice) => (int) $colorPrice->color_id === $colorId)
            ? (int) $colorId
            : ($product->colorPrices->first() !== null ? (int) $product->colorPrices->first()->color_id : null);
    }

    public function selectColor(int $colorId): void
    {
        $product = $this->resolveProduct($this->product_id);

        if ($this->notMountable($product, $this->product_id)) {
            abort(404);
        }

        // The server stays authoritative: a color that is not an active color
        // of this product is ignored, never charged.
        if ($product->colorPrices->contains(fn ($colorPrice) => (int) $colorPrice->color_id === $colorId)) {
            $this->color_id = $colorId;
            $this->selected_image_index = 0;
        }
    }

    public function selectImage(int $index): void
    {
        $product = $this->resolveProduct($this->product_id);

        if ($this->notMountable($product, $this->product_id)) {
            abort(404);
        }

        $galleryCount = count($this->galleryPaths($product, $this->color_id));

        if ($galleryCount > 0) {
            $this->selected_image_index = max(0, min($index, $galleryCount - 1));
        }
    }

    public function updatedQuantity(int $value): void
    {
        $this->quantity = max(1, min($value, 20));
    }

    /**
     * The gallery mount gate is the store product detail gate: only a plain
     * Store product (no customization workflow) that the checkout can actually
     * charge gets a live gallery surface. Anything else must 404 here exactly
     * like ProductCustomizer 404s for products it does not own.
     */
    private function notMountable(?Product $product, int $productId): bool
    {
        if ($product === null) {
            return true;
        }

        $workflowRaw = $product->getRawOriginal('customization_workflow');

        if ($workflowRaw !== null) {
            return true;
        }

        return ! ProductPurchaseabilityService::isPurchasable($productId);
    }

    private function resolveProduct(int $productId): ?Product
    {
        return Product::query()
            ->active()
            ->whereNull('customization_workflow')
            ->withCatalog()
            ->with('images')
            ->find($productId);
    }

    public function render()
    {
        $product = $this->resolveProduct($this->product_id);

        if ($this->notMountable($product, $this->product_id)) {
            abort(404);
        }

        $activeColors = $product->colorPrices
            ->map(fn ($colorPrice) => [
                'color_id' => (int) $colorPrice->color_id,
                'name' => $colorPrice->color?->name,
                'color_hex' => $colorPrice->color?->code_hex,
                'price' => (int) $colorPrice->price,
            ])
            ->values()
            ->all();

        $selectedVariant = $this->color_id !== null
            ? $product->colorPrices->first(fn ($colorPrice) => (int) $colorPrice->color_id === $this->color_id)
            : null;

        // A variant that silently stopped being active mid-session falls back
        // to the first remaining active variant without touching component
        // state during render.
        if ($selectedVariant === null) {
            $selectedVariant = $product->colorPrices->first();
        }

        $effectiveColorId = $selectedVariant !== null ? (int) $selectedVariant->color_id : null;

        $gallery = $this->galleryPaths($product, $effectiveColorId);

        $unitPrice = $selectedVariant !== null
            ? (int) $selectedVariant->price
            : ($product->base_price !== null ? (int) $product->base_price : null);

        return view('livewire.catalog.product-gallery', [
            'product' => $product,
            'colors' => $activeColors,
            'hasColors' => $activeColors !== [],
            'selectedVariant' => $selectedVariant,
            'gallery' => $gallery,
            'mainImagePath' => $gallery[$this->selected_image_index] ?? ($gallery[0] ?? null),
            'unitPrice' => $unitPrice,
            'submitColorId' => $effectiveColorId,
        ]);
    }

    /**
     * A color's own images define its look; when a variant has none, the
     * product-level (untinted) images serve as the neutral gallery and the
     * legacy main image is the final fallback. The ordering is authoritative
     * from product_images.sort_order.
     */
    private function galleryPaths(Product $product, ?int $variantColorId): array
    {
        $paths = array_values($product->images
            ->filter(fn ($image) => (int) $image->color_id === $variantColorId)
            ->map(fn ($image) => $image->image_path)
            ->all());

        if ($paths === []) {
            $paths = array_values($product->images
                ->filter(fn ($image) => $image->color_id === null)
                ->map(fn ($image) => $image->image_path)
                ->all());
        }

        if ($paths === [] && $product->main_image) {
            $paths = [$product->main_image];
        }

        return $paths;
    }
}
