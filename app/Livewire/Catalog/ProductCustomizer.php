<?php

namespace App\Livewire\Catalog;

use App\Enums\CustomizationWorkflowEnum;
use App\Models\CateDesign;
use App\Models\Product;
use App\Services\BankCard\BankCardCustomization;
use App\Services\CartService;
use App\Services\Customization\CardPresenter;
use App\Services\Customization\CustomizationWorkflowRegistry;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProductCustomizer extends Component
{
    public int $product_id;

    public ?int $color_id = null;

    public ?int $design_id = null;

    public ?int $design_image_id = null;

    public ?int $selected_category_id = null;

    public int $quantity = 1;

    // Step & View state
    public int $step = 1; // 1: Front design/color, 2: Back specifications

    public string $activeView = 'front'; // 'front' | 'back'

    // Customer customization preferences
    public string $card_number = '';

    public string $card_holder_name = '';

    public string $back_text = '';

    public string $cvv2 = '';

    public string $expiry_month = '';

    public string $expiry_year = '';

    public bool $security_cvv_enabled = false;

    public bool $security_expiry_enabled = false;

    public Product $product;

    public Collection $colorPrices;

    public Collection $catalog;

    public Collection $designOptions;

    public Collection $designImageOptions;

    public function mount(int $productId): void
    {
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

        $workflowRaw = $this->product->getRawOriginal('customization_workflow');
        $workflow = $workflowRaw !== null ? CustomizationWorkflowEnum::tryFrom((string) $workflowRaw) : null;

        if (! CustomizationWorkflowRegistry::isActive($workflow)) {
            abort(404);
        }

        $this->product_id = $productId;

        $this->colorPrices = $this->product->colorPrices;
        $this->color_id = $this->colorPrices->first()?->color_id;

        $this->refreshDesignData();

        if ($this->catalog->isNotEmpty()) {
            $this->selected_category_id = $this->catalog->first()->id;
        }
    }

    public function setStep(int $step): void
    {
        $this->step = in_array($step, [1, 2], true) ? $step : 1;
        $this->activeView = $this->step === 2 ? 'back' : 'front';
    }

    public function setActiveView(string $view): void
    {
        $this->activeView = in_array($view, ['front', 'back'], true) ? $view : 'front';
    }

    public function selectCategory(int $categoryId): void
    {
        $this->selected_category_id = $categoryId;
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

    public function toggleCvv(): void
    {
        $this->security_cvv_enabled = ! $this->security_cvv_enabled;
        if (! $this->security_cvv_enabled) {
            $this->cvv2 = '';
        }
    }

    public function toggleExpiry(): void
    {
        $this->security_expiry_enabled = ! $this->security_expiry_enabled;
        if (! $this->security_expiry_enabled) {
            $this->expiry_month = '';
            $this->expiry_year = '';
        }
    }

    // Presentation-only grouped display (e.g. "6274 0512 3456 7890").
    // Never persisted: the snapshot always keeps the canonical 16 ASCII digits.
    public function getDisplayCardNumberProperty(): string
    {
        return CardPresenter::presentCardNumber($this->card_number);
    }

    public function addToCart(CartService $cartService): void
    {
        $this->card_number = BankCardCustomization::canonicalizeCardNumber($this->card_number);

        $rules = [
            'product_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ] + BankCardCustomization::rulesFor(
            $this->security_cvv_enabled,
            $this->security_expiry_enabled,
        );

        $messages = BankCardCustomization::messages();

        $this->validate($rules, $messages);

        $customizationJson = [
            'security_cvv_enabled' => $this->security_cvv_enabled,
            'security_expiry_enabled' => $this->security_expiry_enabled,
        ];

        if (trim($this->card_number) !== '') {
            $customizationJson['card_number'] = trim($this->card_number);
        }

        if (trim($this->card_holder_name) !== '') {
            $customizationJson['card_holder_name'] = trim($this->card_holder_name);
        }

        if (trim($this->back_text) !== '') {
            $customizationJson['back_text'] = trim($this->back_text);
        }

        if ($this->security_cvv_enabled && trim($this->cvv2) !== '') {
            $customizationJson['cvv2'] = trim($this->cvv2);
        }

        if ($this->security_expiry_enabled) {
            if (trim($this->expiry_month) !== '') {
                $customizationJson['expiry_month'] = trim($this->expiry_month);
            }
            if (trim($this->expiry_year) !== '') {
                $customizationJson['expiry_year'] = trim($this->expiry_year);
            }
        }

        $cartService->addItem([
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'design_id' => $this->design_id,
            'design_image_id' => $this->design_image_id,
            'quantity' => $this->quantity,
            'customization_json' => $customizationJson,
        ]);

        session()->flash('success', 'محصول با موفقیت به سبد خرید اضافه شد.');

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

        if ($this->selected_category_id && ! $this->catalog->contains('id', $this->selected_category_id)) {
            $this->selected_category_id = $this->catalog->first()?->id;
        }
    }

    public function render()
    {
        $selectedPriceItem = $this->colorPrices->firstWhere('color_id', $this->color_id);
        $unitPrice = $selectedPriceItem?->price ?? $this->product->base_price ?? 0;
        $totalPrice = (int) $unitPrice * max(1, $this->quantity);

        return view('livewire.catalog.product-customizer', [
            'unitPrice' => $unitPrice,
            'totalPrice' => $totalPrice,
            'selectedColor' => $selectedPriceItem?->color,
        ]);
    }
}
