<?php

namespace App\Livewire\Catalog;

use App\Enums\CustomizationWorkflowEnum;
use App\Livewire\Forms\BankCardWorkspace;
use App\Models\CateDesign;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Services\CartService;
use App\Services\Customization\CardPresenter;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\DesignCatalogService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCustomizer extends Component
{
    use WithPagination;

    public int $product_id;

    public ?int $color_id = null;

    public ?int $design_id = null;

    public ?int $design_image_id = null;

    public ?int $selected_category_id = null;

    public int $quantity = 1;

    // Step & View state
    public int $step = 1; // 1: Front design/color, 2: Back specifications

    public string $activeView = 'front'; // 'front' | 'back'

    // Bank card workspace owns card-specific state, validation, and payload.
    public BankCardWorkspace $bankCard;

    public int $basePrice = 0;

    public array $colorPrices = [];

    public array $categories = [];

    public ?string $workflow = null;

    public array $designImages = [];

    public function mount(int $productId): void
    {
        $product = Product::query()
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

        $workflowRaw = $product->getRawOriginal('customization_workflow');
        $workflow = $workflowRaw !== null ? CustomizationWorkflowEnum::tryFrom((string) $workflowRaw) : null;

        if (! CustomizationWorkflowRegistry::isActive($workflow)) {
            abort(404);
        }

        $this->product_id = $productId;
        $this->workflow = $workflow?->value;
        $this->basePrice = (int) $product->base_price;

        $this->colorPrices = collect($product->colorPrices)
            ->map(fn ($price) => [
                'color_id' => $price->color_id,
                'name' => $price->color?->name,
                'color_hex' => $price->color?->code_hex,
                'price' => (int) $price->price,
            ])
            ->values()
            ->all();

        $this->color_id = $this->colorPrices[0]['color_id'] ?? null;

        $this->refreshCategories();

        // The design catalog is never loaded into state: the first page is
        // resolved server-side during render, which also auto-selects the first
        // design and its images.
        $this->selected_category_id = $this->categories[0]['id'] ?? null;
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
        // UX defense only: the authoritative visibility guarantee lives in
        // DesignCatalogService, because selected_category_id is a public
        // Livewire property that can be hydrated from a crafted payload.
        if (! in_array($categoryId, array_column($this->categories, 'id'), true)) {
            return;
        }

        $this->selected_category_id = $categoryId;
        $this->resetPage();
    }

    public function selectColor(int $colorId): void
    {
        $this->color_id = $colorId;
        $this->design_id = null;
        $this->design_image_id = null;

        // Resets pagination: the render resolves the (already cate-scoped)
        // first page for the new color and re-selects the first design.
        $this->resetPage();
    }

    public function selectDesign(int $designId): void
    {
        $allowedImageIds = $this->allowedImageIds();

        if (! app(DesignCatalogService::class)->isDesignInCatalog(
            $designId,
            $this->currentCategoryId(),
            $allowedImageIds,
            $this->catalogWorkflow(),
        )) {
            return;
        }

        $this->design_id = $designId;

        $this->refreshSelectedDesignImages($allowedImageIds);
    }

    public function selectDesignImage(int $designImageId): void
    {
        $image = collect($this->designImages)->firstWhere('id', $designImageId);

        if (! $image) {
            return;
        }

        $this->design_image_id = $designImageId;
        $this->design_id = $image['design_id'];
    }

    public function toggleCvv(): void
    {
        $this->bankCard->toggleCvv();
    }

    public function toggleExpiry(): void
    {
        $this->bankCard->toggleExpiry();
    }

    // Presentation-only grouped display (e.g. "6274 0512 3456 7890").
    // Never persisted: the snapshot always keeps the canonical 16 ASCII digits.
    public function getDisplayCardNumberProperty(): string
    {
        return CardPresenter::presentCardNumber($this->bankCard->card_number);
    }

    protected function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function addToCart(CartService $cartService): void
    {
        $this->bankCard->canonicalize();

        // Commerce rules plus the bank card workspace rules run in one validate
        // call (Livewire Form sub-validation owns the card-specific rules).
        $this->validate();

        $cartService->addItem([
            'product_id' => $this->product_id,
            'color_id' => $this->color_id,
            'design_id' => $this->design_id,
            'design_image_id' => $this->design_image_id,
            'quantity' => $this->quantity,
            'customization_json' => $this->bankCard->customizationJson(),
        ]);

        session()->flash('success', 'محصول با موفقیت به سبد خرید اضافه شد.');

        $this->redirectRoute('cart.index', navigate: true);
    }

    private function refreshCategories(): void
    {
        $this->categories = CateDesign::query()
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn (CateDesign $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Loads (and persists in state) only the selected design's images.
     * Previously every compatible image of every design was hydrated here.
     */
    private function refreshSelectedDesignImages(?Collection $allowedImageIds): void
    {
        if ($this->design_id === null) {
            $this->designImages = [];
            $this->design_image_id = null;

            return;
        }

        $images = DesignImage::query()
            ->where('design_id', $this->design_id)
            ->where('is_active', true)
            ->when($allowedImageIds !== null, fn ($query) => $query->whereIn('id', $allowedImageIds))
            ->with([
                'color' => fn ($colorQuery) => $colorQuery->active(),
            ])
            ->orderBy('sort_order')
            ->get(['id', 'design_id', 'color_id', 'image_path']);

        $this->designImages = $images
            ->map(fn (DesignImage $image) => [
                'id' => $image->id,
                'design_id' => $image->design_id,
                'color_id' => $image->color_id,
                'color_name' => $image->color?->name,
                'color_hex' => $image->color?->code_hex,
                'image_path' => $image->image_path,
            ])
            ->values()
            ->all();

        $imageIds = array_column($this->designImages, 'id');

        $this->design_image_id = ($this->design_image_id && in_array($this->design_image_id, $imageIds, true))
            ? $this->design_image_id
            : ($imageIds[0] ?? null);
    }

    private function allowedImageIds(): ?Collection
    {
        if ($this->color_id === null) {
            return null;
        }

        return DesignColorCompatibility::query()
            ->where('card_color_id', $this->color_id)
            ->where('is_allowed', true)
            ->pluck('design_image_id');
    }

    private function currentCategoryId(): int
    {
        return $this->selected_category_id ?? ($this->categories[0]['id'] ?? 0);
    }

    private function catalogWorkflow(): ?CustomizationWorkflowEnum
    {
        return $this->workflow !== null ? CustomizationWorkflowEnum::tryFrom($this->workflow) : null;
    }

    public function render()
    {
        $allowedImageIds = $this->allowedImageIds();

        $designs = app(DesignCatalogService::class)->paginate(
            $this->currentCategoryId(),
            $this->color_id,
            $allowedImageIds,
            $this->catalogWorkflow(),
        );

        // The catalog is not part of Livewire state, so a null selection is
        // normalized server-side during render (auto-select the first design
        // of the current page and load its image chips).
        if ($this->design_id === null && $designs->isNotEmpty()) {
            $this->design_id = $designs->items()[0]['id'];
            $this->refreshSelectedDesignImages($allowedImageIds);
        }

        $selectedPriceItem = collect($this->colorPrices)->firstWhere('color_id', $this->color_id);
        $unitPrice = (int) ($selectedPriceItem['price'] ?? $this->basePrice);
        $totalPrice = $unitPrice * max(1, $this->quantity);

        return view('livewire.catalog.product-customizer', [
            'unitPrice' => $unitPrice,
            'totalPrice' => $totalPrice,
            'selectedColor' => $selectedPriceItem,
            'designs' => $designs,
        ]);
    }
}
