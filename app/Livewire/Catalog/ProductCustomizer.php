<?php

namespace App\Livewire\Catalog;

use App\Enums\CustomizationWorkflowEnum;
use App\Livewire\Forms\BankCardWorkspace;
use App\Livewire\Forms\FuelCardWorkspace;
use App\Models\CateDesign;
use App\Models\DesignColorCompatibility;
use App\Models\DesignImage;
use App\Models\Product;
use App\Models\ProductColorPrice;
use App\Services\CartService;
use App\Services\CustomDesign\CustomDesignService;
use App\Services\Customization\CardPresenter;
use App\Services\Customization\CustomizationWorkflowRegistry;
use App\Services\Customization\ProductPurchaseabilityService;
use App\Services\DesignCatalogService;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductCustomizer extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?int $product_id = null;

    public ?int $color_id = null;

    public ?int $design_id = null;

    public ?int $design_image_id = null;

    public ?int $selected_category_id = null;

    public int $quantity = 1;

    // Custom Design engine state
    public string $designSource = 'catalog'; // 'catalog' | 'custom'

    public $customImage = null;

    public ?string $customDesignToken = null;

    public ?string $customDesignOriginalName = null;

    public ?int $customDesignFileSize = null;

    public string $customLayoutMode = 'centered'; // 'centered' | 'full_card'

    public ?string $customDesignErrorMessage = null;

    // Step & View state
    public int $step = 1; // 1: Front design/color, 2: Back specifications

    public string $activeView = 'front'; // 'front' | 'back'

    // Bank card workspace owns card-specific state, validation, and payload.
    public BankCardWorkspace $bankCard;

    // Fuel card workspace is the independent Fuel boundary (no routing yet;
    // orchestration happens in a later phase).
    public FuelCardWorkspace $fuelCard;

    public int $basePrice = 0;

    public array $colorPrices = [];

    public array $categories = [];

    public ?string $workflow = null;

    public array $designImages = [];

    public function mount(
        ?int $productId = null,
        ?string $workflow = null,
        ?string $initialDesignSlug = null,
        ?int $initialColorId = null,
        ?int $initialVariantId = null,
    ): void {
        $resolvedWorkflow = null;
        $product = null;

        if ($workflow !== null && trim($workflow) !== '') {
            $resolvedWorkflow = CustomizationWorkflowEnum::tryFrom(trim($workflow));
        }

        if ($productId !== null && $productId > 0) {
            $product = Product::query()->active()->find($productId);
            if ($product !== null) {
                $rawWorkflow = $product->getRawOriginal('customization_workflow');
                if ($resolvedWorkflow === null && $rawWorkflow !== null) {
                    $resolvedWorkflow = CustomizationWorkflowEnum::tryFrom((string) $rawWorkflow);
                }
            }
        }

        if ($resolvedWorkflow !== null) {
            if (! CustomizationWorkflowRegistry::isActive($resolvedWorkflow)) {
                abort(404);
            }

            $this->product_id = $product?->id ?? $productId;
            $this->workflow = $resolvedWorkflow->value;
            $this->basePrice = (int) ($product?->base_price ?? 0);

            $colorPricesQuery = ProductColorPrice::query()
                ->where('customization_workflow', $resolvedWorkflow->value)
                ->where('is_active', true)
                ->whereHas('color', fn ($query) => $query->where('is_active', true))
                ->with([
                    'color' => fn ($colorQuery) => $colorQuery
                        ->active()
                        ->orderBy('sort_order'),
                ])
                ->orderBy('price');

            if ($colorPricesQuery->clone()->doesntExist() && $this->product_id !== null) {
                $colorPricesQuery = ProductColorPrice::query()
                    ->where('product_id', $this->product_id)
                    ->where('is_active', true)
                    ->whereHas('color', fn ($query) => $query->where('is_active', true))
                    ->with([
                        'color' => fn ($colorQuery) => $colorQuery
                            ->active()
                            ->orderBy('sort_order'),
                    ])
                    ->orderBy('price');
            }

            $rawPrices = $colorPricesQuery->get();

            if ($rawPrices->isEmpty()) {
                abort(404);
            }

            if ($resolvedWorkflow === CustomizationWorkflowEnum::FUEL_CARD && $rawPrices->count() !== 1) {
                abort(404);
            }

            $this->colorPrices = $rawPrices
                ->map(fn ($price) => [
                    'color_id' => $price->color_id,
                    'name' => $price->color?->name,
                    'color_hex' => $price->color?->code_hex,
                    'price' => (int) $price->price,
                    'is_scratched' => (bool) ($price->color?->is_scratched ?? false),
                ])
                ->values()
                ->all();
        } else {
            if ($productId === null) {
                abort(404);
            }

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

            if (! ProductPurchaseabilityService::isPurchasable($productId)) {
                abort(404);
            }

            $this->product_id = $productId;
            $this->workflow = null;
            $this->basePrice = (int) $product->base_price;

            $this->colorPrices = collect($product->colorPrices)
                ->map(fn ($price) => [
                    'color_id' => $price->color_id,
                    'name' => $price->color?->name,
                    'color_hex' => $price->color?->code_hex,
                    'price' => (int) $price->price,
                    'is_scratched' => (bool) ($price->color?->is_scratched ?? false),
                ])
                ->values()
                ->all();
        }

        $initialDesignSlug = $initialDesignSlug ?? request()->query('design');
        $initialColorParam = $initialColorId ?? request()->query('color');
        $initialColorId = is_numeric($initialColorParam) ? (int) $initialColorParam : null;

        $requestedVariantParam = $initialVariantId ?? request()->query('variant') ?? request()->query('variant_id');
        $requestedVariantId = is_numeric($requestedVariantParam) ? (int) $requestedVariantParam : null;
        $requestedDesignColorId = request()->query('design_color');
        $requestedDesignColorId = is_numeric($requestedDesignColorId) ? (int) $requestedDesignColorId : null;

        $this->refreshCategories();

        if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::FUEL_CARD->value) {
            $this->color_id = $this->colorPrices[0]['color_id'] ?? null;
            $this->designSource = 'catalog';
            $this->selected_category_id = $this->categories[0]['id'] ?? null;
            return;
        }

        $this->selected_category_id = $this->categories[0]['id'] ?? null;

        if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::BANK_CARD->value) {
            $this->initializeBankCardCustomization(
                $initialDesignSlug,
                $initialColorId,
                $requestedVariantId,
                $requestedDesignColorId
            );
        } else {
            $this->color_id = $this->colorPrices[0]['color_id'] ?? null;
        }
    }

    private function initializeBankCardCustomization(
        ?string $initialDesignSlug,
        ?int $initialColorId,
        ?int $requestedVariantId,
        ?int $requestedDesignColorId
    ): void {
        $defaultColorId = collect($this->colorPrices)->first(function ($cp) {
            return DesignColorCompatibility::query()
                ->where('card_color_id', $cp['color_id'])
                ->where('is_allowed', true)
                ->whereHas('designImage', function ($q) {
                    $q->where('is_active', true)
                        ->whereHas('design', fn ($dq) => $dq->where('is_active', true));
                })
                ->exists();
        })['color_id'] ?? ($this->colorPrices[0]['color_id'] ?? null);

        $hasExplicitColor = $initialColorId !== null
            && collect($this->colorPrices)->pluck('color_id')->contains($initialColorId);

        // If no design slug is provided, initialize standard color selection
        if (! is_string($initialDesignSlug) || trim($initialDesignSlug) === '') {
            $this->color_id = $hasExplicitColor ? $initialColorId : $defaultColorId;
            return;
        }

        $designService = app(DesignCatalogService::class);
        $design = $designService->findVisibleBySlug(trim($initialDesignSlug));

        if ($design === null) {
            // Invalid or unavailable design: fallback safely, do not substitute unrelated design
            $this->color_id = $hasExplicitColor ? $initialColorId : $defaultColorId;
            return;
        }

        if ($requestedVariantId !== null) {
            // 1. Resolve requested variant and verify it belongs to this exact design and is active
            $requestedVariant = DesignImage::query()
                ->where('id', $requestedVariantId)
                ->where('design_id', $design->id)
                ->where('is_active', true)
                ->first();

            if ($requestedVariant === null) {
                // Invalid, inactive, or belongs to another design:
                // Fall back safely without silently picking an unrelated design or variant
                $this->color_id = $hasExplicitColor ? $initialColorId : $defaultColorId;
                return;
            }

            // 2. Query active card colors explicitly compatible with this exact variant
            $compatibleCardColorIds = DesignColorCompatibility::query()
                ->where('design_image_id', $requestedVariant->id)
                ->where('is_allowed', true)
                ->whereHas('cardColor', fn ($cq) => $cq->where('is_active', true))
                ->pluck('card_color_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $selectedCardColorId = null;
            $allowedImageIds = null;

            if ($hasExplicitColor) {
                // Explicit card color requested via query param
                if (in_array($initialColorId, $compatibleCardColorIds, true)) {
                    $candAllowed = $this->allowedImageIds($initialColorId);
                    if ($candAllowed !== null && in_array($requestedVariant->id, $candAllowed->all(), true)) {
                        $selectedCardColorId = $initialColorId;
                        $allowedImageIds = $candAllowed;
                    }
                } else {
                    // Explicit color is incompatible with requested variant: safe fallback
                    $this->color_id = $initialColorId;
                    return;
                }
            } else {
                // Select the first compatible active physical card color according to product's colorPrices order
                foreach ($this->colorPrices as $cp) {
                    $candColorId = (int) $cp['color_id'];
                    if (in_array($candColorId, $compatibleCardColorIds, true)) {
                        $candAllowed = $this->allowedImageIds($candColorId);
                        if ($candAllowed !== null && in_array($requestedVariant->id, $candAllowed->all(), true)) {
                            $selectedCardColorId = $candColorId;
                            $allowedImageIds = $candAllowed;
                            break;
                        }
                    }
                }
            }

            if ($selectedCardColorId === null) {
                // Requested variant is not compatible with ANY card color on this product
                $this->color_id = $hasExplicitColor ? $initialColorId : $defaultColorId;
                $this->addError('design_image_id', 'نسخه تصویری انتخابی در حال حاضر با هیچ‌یک از رنگ‌های کارت موجود سازگار نیست.');
                return;
            }

            // Both card color and requested design variant are validated and compatible:
            $this->color_id = $selectedCardColorId;
            $this->design_id = (int) $design->id;
            $this->refreshSelectedDesignImages($allowedImageIds);
            $this->design_image_id = (int) $requestedVariant->id;
            $this->selected_category_id = (int) $design->cate_design_id;
            $this->designSource = 'catalog';
            return;
        }

        // Determine compatible physical card color when no specific variant ID was requested:
        $selectedCardColorId = null;
        $allowedImageIds = null;

        if ($hasExplicitColor) {
            // Explicit card color requested via query param (backwards-compatibility)
            $candidateAllowed = $this->allowedImageIds($initialColorId);
            if ($designService->isDesignInCatalog($design->id, $design->cate_design_id, $candidateAllowed, $this->catalogWorkflow())) {
                $selectedCardColorId = $initialColorId;
                $allowedImageIds = $candidateAllowed;
            } else {
                // Incompatible with explicitly requested color: safe fallback
                $this->color_id = $initialColorId;
                return;
            }
        } else {
            // No explicit card color requested (New Quick Design UX)
            // 1. Try default physical card color according to product/pricing rules
            if ($defaultColorId !== null) {
                $candidateAllowed = $this->allowedImageIds($defaultColorId);
                if ($designService->isDesignInCatalog($design->id, $design->cate_design_id, $candidateAllowed, $this->catalogWorkflow())) {
                    $selectedCardColorId = $defaultColorId;
                    $allowedImageIds = $candidateAllowed;
                }
            }

            // 2. If default color is incompatible with every variant of the requested design,
            // choose the first compatible physical card color deterministically from active color prices
            if ($selectedCardColorId === null) {
                foreach ($this->colorPrices as $cp) {
                    $candColorId = (int) $cp['color_id'];
                    if ($candColorId === $defaultColorId) {
                        continue;
                    }
                    $candidateAllowed = $this->allowedImageIds($candColorId);
                    if ($designService->isDesignInCatalog($design->id, $design->cate_design_id, $candidateAllowed, $this->catalogWorkflow())) {
                        $selectedCardColorId = $candColorId;
                        $allowedImageIds = $candidateAllowed;
                        break;
                    }
                }
            }
        }

        if ($selectedCardColorId === null) {
            // Design is not compatible with ANY card color on this product
            $this->color_id = $defaultColorId;
            return;
        }

        $this->color_id = $selectedCardColorId;
        $this->design_id = (int) $design->id;
        $this->refreshSelectedDesignImages($allowedImageIds);

        if ($requestedDesignColorId !== null) {
            $matchingImage = collect($this->designImages)->firstWhere('color_id', $requestedDesignColorId);
            if ($matchingImage) {
                $this->design_image_id = (int) $matchingImage['id'];
                $this->selected_category_id = (int) $design->cate_design_id;
                $this->designSource = 'catalog';
            } else {
                $this->design_id = null;
                $this->design_image_id = null;
                $this->designImages = [];
            }
        } else {
            // Standard Quick Design: refreshSelectedDesignImages auto-selected first compatible variant
            $this->selected_category_id = (int) $design->cate_design_id;
            $this->designSource = 'catalog';
        }
    }

    public function setStep(int $step): void
    {
        if ($step === 2) {
            if ($this->designSource === 'custom' && empty($this->customDesignToken)) {
                $this->customDesignErrorMessage = 'لطفاً ابتدا تصویر طرح اختصاصی خود را بارگذاری کنید.';
                $this->addError('customImage', 'لطفاً ابتدا تصویر طرح اختصاصی خود را بارگذاری کنید.');
                return;
            }

            if ($this->designSource === 'catalog' && $this->design_id === null) {
                $this->addError('design_id', 'لطفاً ابتدا یک طرح از میان طرح‌های آماده انتخاب کنید.');
                return;
            }
        }

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
        // Fuel color is configuration-bound: the browser can never pick a color
        // other than the single active ProductColorPrice row of the product.
        // The workflow is re-resolved from the product row so a crafted payload
        // cannot bypass the lock.
        if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::FUEL_CARD->value) {
            $configuredColorId = $this->colorPrices[0]['color_id'] ?? null;

            if ($configuredColorId === null || (int) $colorId !== (int) $configuredColorId) {
                return;
            }

            $this->color_id = $colorId;
            $this->design_id = null;
            $this->design_image_id = null;
            $this->resetPage();

            return;
        }

        $this->color_id = $colorId;

        if ($this->design_id !== null && $this->designSource === 'catalog') {
            $allowedImageIds = $this->allowedImageIds();
            $isCompatible = app(DesignCatalogService::class)->isDesignInCatalog(
                $this->design_id,
                $this->currentCategoryId(),
                $allowedImageIds,
                $this->catalogWorkflow()
            );

            if ($isCompatible) {
                $this->refreshSelectedDesignImages($allowedImageIds);
            } else {
                $this->design_id = null;
                $this->design_image_id = null;
                $this->designImages = [];
            }
        } else {
            $this->design_id = null;
            $this->design_image_id = null;
            $this->designImages = [];
        }

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

    public function setDesignSource(string $source): void
    {
        if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::FUEL_CARD->value && $source === 'custom') {
            return;
        }

        if (! in_array($source, ['catalog', 'custom'], true)) {
            return;
        }

        $this->designSource = $source;

        if ($source === 'catalog' && $this->design_id === null) {
            $allowedImageIds = $this->allowedImageIds();
            $designs = app(DesignCatalogService::class)->paginate(
                $this->currentCategoryId(),
                $this->color_id,
                $allowedImageIds,
                $this->catalogWorkflow(),
            );
            if ($designs->isNotEmpty()) {
                $this->design_id = $designs->items()[0]['id'];
                $this->refreshSelectedDesignImages($allowedImageIds);
            }
        }
    }

    public function updatedCustomImage(): void
    {
        $this->customDesignErrorMessage = null;

        if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::FUEL_CARD->value) {
            $this->customImage = null;
            $this->customDesignErrorMessage = 'امکان آپلود طرح اختصاصی برای کارت سوخت وجود ندارد.';
            $this->addError('customImage', $this->customDesignErrorMessage);
            return;
        }

        if (! $this->customImage) {
            return;
        }

        try {
            $service = app(CustomDesignService::class);
            $stored = $service->validateAndStoreTemp(
                $this->customImage,
                $this->authoritativeWorkflow() ?? 'BANK_CARD'
            );

            if ($this->customDesignToken && $this->customDesignToken !== $stored['token']) {
                $service->removeTempDesign($this->customDesignToken);
            }

            $this->customDesignToken = $stored['token'];
            $this->customDesignOriginalName = $stored['original_name'];
            $this->customDesignFileSize = $stored['size'] ?? null;
            $this->customDesignErrorMessage = null;
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->customDesignErrorMessage = $e->validator->errors()->first('customImage') ?: $e->getMessage();
            $this->customImage = null;
        } catch (\Throwable $e) {
            $this->customDesignErrorMessage = 'خطایی در پردازش تصویر رخ داد. لطفاً دوباره تلاش کنید.';
            $this->customImage = null;
        }
    }

    public function removeCustomImage(): void
    {
        if ($this->customDesignToken) {
            app(CustomDesignService::class)->removeTempDesign($this->customDesignToken);
        }

        $this->customDesignToken = null;
        $this->customDesignOriginalName = null;
        $this->customDesignFileSize = null;
        $this->customImage = null;
        $this->customDesignErrorMessage = null;
    }

    public function getFormattedCustomDesignFileSizeProperty(): string
    {
        $size = $this->customDesignFileSize;

        if ($size === null && $this->customDesignToken) {
            $meta = session("custom_designs.{$this->customDesignToken}");
            $size = $meta['size'] ?? null;
        }

        if (! $size) {
            return '';
        }

        if ($size >= 1048576) {
            return fa_digits(number_format($size / 1048576, 1)) . ' مگابایت';
        }

        return fa_digits(number_format(round($size / 1024))) . ' کیلوبایت';
    }

    public function setCustomLayoutMode(string $mode): void
    {
        if (in_array($mode, ['centered', 'full_card'], true)) {
            $this->customLayoutMode = $mode;
        }
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

    // Presentation-only grouped display for IBAN (e.g. "IR12 0120 0000 0000 0012 3456 78").
    public function getDisplayIbanProperty(): string
    {
        return CardPresenter::presentIban($this->bankCard->iban);
    }

    public function getDisplayIranPlateProperty(): array
    {
        return \App\Services\FuelCard\FuelCardCustomization::formatIranPlateDisplay(
            $this->fuelCard->plate_number,
            [
                'part1' => $this->fuelCard->plate_part1,
                'letter' => $this->fuelCard->plate_letter,
                'part2' => $this->fuelCard->plate_part2,
                'iran' => $this->fuelCard->plate_iran,
            ]
        );
    }

    protected function rules(): array
    {
        if ($this->designSource === 'custom') {
            return [
                'product_id' => ['nullable', 'integer'],
                'color_id' => ['required', 'integer', 'min:1'],
                'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            ];
        }

        return [
            'product_id' => ['nullable', 'integer'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function addToCart(CartService $cartService): void
    {
        // The workflow is re-resolved from the product row (never client-hydrated
        // state), and each workflow owns its canonicalization and customization
        // snapshot. No dynamic class or view resolution happens here.
        switch ($this->authoritativeWorkflow()) {
            case CustomizationWorkflowEnum::BANK_CARD->value:
                $this->bankCard->canonicalize();
                $this->bankCard->validate();
                $customization = $this->bankCard->customizationJson();
                if ($this->bankCard->security_cvv_enabled && trim($this->bankCard->cvv2) !== '') {
                    $customization['cvv2'] = trim($this->bankCard->cvv2);
                }
                break;

            case CustomizationWorkflowEnum::FUEL_CARD->value:
                $this->fuelCard->canonicalize();
                $this->fuelCard->validate();
                $customization = $this->fuelCard->customizationJson();
                break;

            default:
                $customization = [];
                break;
        }

        if ($this->designSource === 'custom') {
            if ($this->authoritativeWorkflow() === CustomizationWorkflowEnum::FUEL_CARD->value) {
                $this->designSource = 'catalog';
                $this->addError('customImage', 'امکان آپلود طرح اختصاصی برای کارت سوخت وجود ندارد.');
                return;
            }

            if (empty($this->customDesignToken)) {
                $this->customDesignErrorMessage = 'لطفاً ابتدا تصویر طرح اختصاصی خود را بارگذاری کنید.';
                $this->addError('customImage', 'لطفاً ابتدا تصویر طرح اختصاصی خود را بارگذاری کنید.');
                return;
            }

            $this->validate([
                'product_id' => ['nullable', 'integer'],
                'color_id' => ['required', 'integer', 'min:1'],
                'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            ]);

            $cartService->addItem([
                'workflow' => $this->authoritativeWorkflow(),
                'customization_workflow' => $this->authoritativeWorkflow(),
                'product_id' => $this->product_id > 0 ? $this->product_id : null,
                'color_id' => $this->color_id,
                'design_id' => null,
                'design_image_id' => null,
                'is_custom_design' => true,
                'custom_design_token' => $this->customDesignToken,
                'layout_mode' => $this->customLayoutMode,
                'quantity' => $this->quantity,
                'customization_json' => $customization,
            ]);

            session()->flash('success', 'محصول با موفقیت به سبد خرید اضافه شد.');

            $this->redirectRoute('cart.index', navigate: true);
            return;
        }

        $this->validate([
            'product_id' => ['nullable', 'integer'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        if ($this->designSource === 'catalog') {
            if ($this->design_id === null || $this->design_image_id === null) {
                $this->addError('design_id', 'لطفاً یک طرح معتبر انتخاب کنید.');
                return;
            }

            $isCompatible = DesignColorCompatibility::query()
                ->where('design_image_id', $this->design_image_id)
                ->where('card_color_id', $this->color_id)
                ->where('is_allowed', true)
                ->exists();

            if (! $isCompatible) {
                $this->addError('design_image_id', 'طرح انتخابی با این رنگ کارت سازگار نیست.');
                return;
            }
        }

        $cartService->addItem([
            'workflow' => $this->authoritativeWorkflow(),
            'customization_workflow' => $this->authoritativeWorkflow(),
            'product_id' => $this->product_id > 0 ? $this->product_id : null,
            'color_id' => $this->color_id,
            'design_id' => $this->design_id,
            'design_image_id' => $this->design_image_id,
            'quantity' => $this->quantity,
            'customization_json' => $customization,
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
            ->get(['id', 'design_id', 'color_id', 'image_path', 'layout_mode']);

        $this->designImages = $images
            ->map(fn (DesignImage $image) => [
                'id' => $image->id,
                'design_id' => $image->design_id,
                'color_id' => $image->color_id,
                'color_name' => $image->color?->name,
                'color_hex' => $image->color?->code_hex,
                'image_path' => $image->image_path,
                'layout_mode' => $image->layout_mode instanceof \App\Enums\DesignImageLayoutModeEnum
                    ? $image->layout_mode->value
                    : ($image->layout_mode ?: 'centered'),
            ])
            ->values()
            ->all();

        $imageIds = array_column($this->designImages, 'id');

        $this->design_image_id = ($this->design_image_id && in_array($this->design_image_id, $imageIds, true))
            ? $this->design_image_id
            : ($imageIds[0] ?? null);
    }

    private function allowedImageIds(?int $colorId = null): ?Collection
    {
        $targetColorId = $colorId ?? $this->color_id;

        if ($targetColorId === null) {
            return null;
        }

        return DesignColorCompatibility::query()
            ->where('card_color_id', $targetColorId)
            ->where('is_allowed', true)
            ->pluck('design_image_id');
    }

    private function currentCategoryId(): int
    {
        return $this->selected_category_id ?? ($this->categories[0]['id'] ?? 0);
    }

    /**
     * Server-authoritative workflow for orchestration decisions. Read live from
     * the product row (raw column) so a client-hydrated "$this->workflow" can
     * never steer branching, payload building, or color locking.
     */
    private function authoritativeWorkflow(): ?string
    {
        if ($this->product_id !== null && $this->product_id > 0) {
            $product = Product::query()->find($this->product_id);
            if ($product !== null) {
                $raw = $product->getRawOriginal('customization_workflow');
                if ($raw !== null) {
                    return (string) $raw;
                }
            }
        }

        if ($this->workflow !== null) {
            $enum = CustomizationWorkflowEnum::tryFrom($this->workflow);
            if ($enum !== null) {
                return $enum->value;
            }
        }

        return null;
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
        // normalized server-side during render when browsing catalog (auto-select the first design
        // of the current page and load its image chips).
        if ($this->designSource === 'catalog' && $this->design_id === null && $designs->isNotEmpty()) {
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
            'customDesignPreviewUrl' => $this->customDesignToken
                ? route('custom-design.preview', ['token' => $this->customDesignToken])
                : null,
        ]);
    }
}
