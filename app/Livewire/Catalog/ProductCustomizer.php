<?php

namespace App\Livewire\Catalog;

use App\Models\CateDesign;
use App\Models\Product;
use App\Services\CartService;
use App\Services\SvgSanitizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductCustomizer extends Component
{
    use WithFileUploads;

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

    public bool $qr_code_enabled = false;

    public $qr_code_file = null;

    public ?string $qr_code_path = null;

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

    public function toggleQrCode(): void
    {
        $this->qr_code_enabled = ! $this->qr_code_enabled;
        if (! $this->qr_code_enabled) {
            $this->removeQrCode();
        }
    }

    public function updatedQrCodeFile(): void
    {
        if (! $this->qr_code_enabled || ! $this->qr_code_file) {
            return;
        }

        $this->validate([
            'qr_code_file' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $extension = strtolower($this->qr_code_file->getClientOriginalExtension());
        if ($extension === 'svg') {
            $sanitizer = new SvgSanitizer;
            $content = file_get_contents($this->qr_code_file->getRealPath());
            $sanitized = $sanitizer->sanitize($content ?: '');
            if (! $sanitized) {
                $this->addError('qr_code_file', 'فایل SVG انتخاب شده نامعتبر یا ناامن است.');
                $this->qr_code_file = null;

                return;
            }
        }

        $this->deleteStoredQrCode();

        // QR codes are user-sensitive content: store on the private "local"
        // disk (storage/app/private) so they are never publicly served from
        // the public disk. A signed/session-owned route renders the preview.
        $storedPath = $this->qr_code_file->store('customizations/qr_codes', 'local');
        $this->qr_code_path = $storedPath;
    }

    public function removeQrCode(): void
    {
        $this->deleteStoredQrCode();
        $this->qr_code_file = null;
        $this->qr_code_enabled = false;
    }

    private function deleteStoredQrCode(): void
    {
        if ($this->qr_code_path && Storage::disk('local')->exists($this->qr_code_path)) {
            Storage::disk('local')->delete($this->qr_code_path);
        }

        $this->qr_code_path = null;
    }

    private function canonicalizeCardNumber(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        // Presentation separators (spaces/dashes) and Persian/Arabic digit glyphs
        // are tolerated on input but the canonical snapshot form is exactly 16
        // ASCII digits without separators.
        $value = strtr(trim($value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-]+/', '', $value) ?? '';
    }

    // Presentation-only grouped display (e.g. "6274 0512 3456 7890").
    // Never persisted: the snapshot always keeps the canonical 16 ASCII digits.
    public function getDisplayCardNumberProperty(): string
    {
        return self::presentCardNumber($this->card_number);
    }

    public static function presentCardNumber(?string $value): string
    {
        $value = strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return trim(preg_replace('/(.{4})(?=.)/', '$1 ', $digits) ?? '');
    }

    // Fixed layout slots for the back card. Presentation-only constants; the
    // layout is decided by the design, never by the user, so the snapshot
    // never stores user-controlled positions. Normalized (0.0 - 1.0) so the
    // fixed positions scale with the card on every viewport.
    public static function fixedSlots(): array
    {
        return [
            'card_number' => ['x' => 0.08, 'y' => 0.42],
            'card_holder_name' => ['x' => 0.08, 'y' => 0.78],
            'back_text' => ['x' => 0.08, 'y' => 0.62],
            'cvv2' => ['x' => 0.72, 'y' => 0.78],
            'expiry' => ['x' => 0.48, 'y' => 0.78],
            'qr_code' => ['x' => 0.76, 'y' => 0.15],
        ];
    }

    public function addToCart(CartService $cartService): void
    {
        $this->card_number = $this->canonicalizeCardNumber($this->card_number);

        $currentYearShort = (int) date('y');
        $yearRange = 'between:'.$currentYearShort.','.($currentYearShort + 10);

        $rules = [
            'product_id' => ['required', 'integer', 'min:1'],
            'color_id' => ['required', 'integer', 'min:1'],
            'design_id' => ['required', 'integer', 'min:1'],
            'design_image_id' => ['nullable', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'card_number' => ['nullable', 'string', 'digits:16'],
            'card_holder_name' => ['nullable', 'string', 'max:100'],
            'back_text' => ['nullable', 'string', 'max:255'],
            'security_cvv_enabled' => ['boolean'],
            'security_expiry_enabled' => ['boolean'],
            'qr_code_enabled' => ['boolean'],
        ];

        if ($this->security_cvv_enabled) {
            $rules['cvv2'] = ['nullable', 'string', 'digits_between:3,4'];
        }

        if ($this->security_expiry_enabled) {
            $rules['expiry_month'] = ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])$/'];
            $rules['expiry_year'] = ['nullable', 'string', 'integer', 'digits:2', $yearRange];
        }

        $messages = [
            'card_number.digits' => 'شماره کارت باید دقیقاً ۱۶ رقمی باشد.',
            'cvv2.digits_between' => 'CVV2 باید ۳ تا ۴ رقم باشد.',
            'expiry_month.regex' => 'ماه انقضا باید بین ۰۱ تا ۱۲ باشد.',
            'expiry_year.digits' => 'سال انقضا باید دو رقم باشد.',
            'expiry_year.between' => 'سال انقضا باید در بازه معتبر باشد.',
        ];

        $this->validate($rules, $messages);

        $customizationJson = [
            'security_cvv_enabled' => $this->security_cvv_enabled,
            'security_expiry_enabled' => $this->security_expiry_enabled,
            'qr_code_enabled' => $this->qr_code_enabled,
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

        if ($this->qr_code_enabled && $this->qr_code_path) {
            $customizationJson['qr_code_path'] = $this->qr_code_path;
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
