<?php

namespace App\Livewire\Catalog;

use App\Models\CateDesign;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProductCustomizer extends Component
{
    public int $product_id;
    public ?int $color_id = null;
    public ?int $design_id = null;
    public ?int $design_image_id = null;
    public int $quantity = 1;
    public array $customization_json = [];

    public Product $product;
    public Collection $colorPrices;
    public Collection $catalog;
    public Collection $designOptions;
    public Collection $designImageOptions;

    public function mount(int $productId): void
    {
        $this->product_id = $productId;

        $this->product = Product::query()
            ->active()
            ->with([
                'colorPrices' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with([
                        'color' => fn ($colorQuery) => $colorQuery
                            ->active()
                            ->orderBy('sort_order'),
                    ])
                    ->orderBy('price'),
            ])
            ->findOrFail($productId);

        $this->colorPrices = $this->product->colorPrices;
        $this->color_id = $this->colorPrices->first()?->color_id;

        $this->refreshDesignData();
    }

    public function selectColor(int $colorId): void
    {
        $this->color_id = $colorId;
        $this->design_id = null;
        $this->design_image_id = null;

        $this->refreshDesignData();
    }

    public function selectDesign(int $designId): void
    {
        $this->design_id = $designId;

        $images = $this->designImageOptions->where('design_id', $designId)->values();
        $this->design_image_id = $images->first()?->id;
    }

    public function selectDesignImage(int $designImageId): void
    {
        $image = $this->designImageOptions->firstWhere('id', $designImageId);

        if (! $image) {
            return;
        }

        $this->design_image_id = $designImageId;
        $this->design_id = $image->design_id;
    }

    public function addToCart(CartService $cartService): void
    {
        $this->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $cartService->addItem([
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'design_id' => $this->design_id,
            'design_image_id' => $this->design_image_id,
            'quantity' => $this->quantity,
            'customization_json' => $this->customization_json,
        ]);

        session()->flash('success', 'Item added to cart.');

        $this->redirectRoute('cart.index', navigate: true);
    }

    private function refreshDesignData(): void
    {
        $selectedColorId = $this->color_id;

        $this->catalog = CateDesign::query()
            ->active()
            ->with([
                'designs' => fn ($designQuery) => $designQuery
                    ->active()
                    ->with([
                        'images' => fn ($imageQuery) => $imageQuery
                            ->active()
                            ->when($selectedColorId, function ($query) use ($selectedColorId) {
                                $query->whereHas('compatibilities', function ($compatibilityQuery) use ($selectedColorId) {
                                    $compatibilityQuery
                                        ->where('card_color_id', $selectedColorId)
                                        ->where('is_allowed', true);
                                });
                            })
                            ->with([
                                'color' => fn ($colorQuery) => $colorQuery->active(),
                                'compatibilities' => fn ($compatibilityQuery) => $compatibilityQuery
                                    ->where('is_allowed', true)
                                    ->with('cardColor'),
                            ])
                            ->orderBy('sort_order'),
                    ])
                    ->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();

        $this->designOptions = $this->catalog
            ->pluck('designs')
            ->flatten(1)
            ->filter(fn ($design) => $design->images->isNotEmpty())
            ->values();

        $this->designImageOptions = $this->designOptions
            ->pluck('images')
            ->flatten(1)
            ->values();

        if ($this->designOptions->isEmpty()) {
            $this->design_id = null;
            $this->design_image_id = null;

            return;
        }

        $this->design_id = $this->design_id && $this->designOptions->contains('id', $this->design_id)
            ? $this->design_id
            : $this->designOptions->first()->id;

        $imagesForDesign = $this->designImageOptions->where('design_id', $this->design_id)->values();

        $this->design_image_id = $this->design_image_id && $imagesForDesign->contains('id', $this->design_image_id)
            ? $this->design_image_id
            : $imagesForDesign->first()?->id;
    }

    public function render()
    {
        $this->customization_json = [
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'design_id' => $this->design_id,
            'design_image_id' => $this->design_image_id,
        ];

        return view('livewire.catalog.product-customizer');
    }
}
