<?php

namespace App\Livewire\Admin;

use App\Enums\HomepageSectionTypeEnum;
use App\Models\FaqItem;
use App\Models\HomepageSection;
use App\Models\Product;
use App\Services\DesignCatalogService;
use App\Support\Concerns\AuthorizesAdminActions;
use Livewire\Component;

class HomepageSectionManager extends Component
{
    use AuthorizesAdminActions;

    public string $search = '';

    public string $sectionType = 'hero';

    public ?string $title = null;

    public ?string $content = null;

    public int $sortOrder = 0;

    public bool $isActive = true;

    public array $productIds = [];

    public array $designIds = [];

    public ?int $faqLimit = null;

    public ?int $limit = null;

    public ?string $backgroundColor = null;

    public ?string $backgroundImage = null;

    public ?string $ctaText = null;

    public ?string $ctaUrl = null;

    public ?int $editingId = null;

    public bool $showForm = false;

    private const SETTING_CHEAT_SHEET = [
        'background_color' => 'رنگ پس‌زمینه (مثال: #1a1a2e)',
        'background_image' => 'مسیر تصویر پس‌زمینه',
        'cta_text' => 'متن دکمه',
        'cta_url' => 'لینک دکمه',
    ];

    protected function rules(): array
    {
        $types = implode(',', array_map(fn ($case) => $case->value, HomepageSectionTypeEnum::cases()));

        $rules = [
            'sectionType' => 'required|in:'.$types,
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'sortOrder' => 'required|integer|min:0',
            'isActive' => 'boolean',
        ];

        if ($this->sectionType === 'featured_products') {
            $rules['productIds'] = ['nullable', 'array'];
            $rules['productIds.*'] = ['integer', 'exists:products,id'];
            $rules['limit'] = 'nullable|integer|min:1|max:100';
        } elseif ($this->sectionType === 'featured_designs') {
            $rules['designIds'] = ['nullable', 'array'];
            $rules['designIds.*'] = ['integer', 'exists:designs,id'];
            $rules['limit'] = 'nullable|integer|min:1|max:100';
        } elseif ($this->sectionType === 'faq') {
            $rules['faqLimit'] = 'nullable|integer|min:1|max:100';
        } else {
            $rules['backgroundColor'] = 'nullable|string|max:50|regex:/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/';
            $rules['backgroundImage'] = 'nullable|string|max:255';
            $rules['ctaText'] = 'nullable|string|max:100';
            $rules['ctaUrl'] = [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value === null || trim($value) === '') {
                        return;
                    }

                    $value = trim($value);

                    if (str_starts_with(strtolower($value), '//')) {
                        $fail('لینک پروتکل‌نسبی (//...) مجاز نیست.');

                        return;
                    }

                    if (safe_url($value) === null) {
                        $fail('لینک باید با http://، https:// یا / شروع شود.');
                    }
                },
            ];
        }

        return $rules;
    }

    public function updatedSearch(): void
    {
        // no pagination; search is a filter only
    }

    public function updatedSectionType(): void
    {
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate();

        $settings = $this->buildSettings();

        $data = [
            'section_type' => $this->sectionType,
            'title' => $this->title !== null && trim($this->title) !== '' ? trim($this->title) : null,
            'content' => $this->content !== null && trim($this->content) !== '' ? trim($this->content) : null,
            'settings' => $settings,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];

        if ($this->editingId) {
            HomepageSection::find($this->editingId)->update($data);
            session()->flash('success', 'بخش صفحه اصلی با موفقیت ویرایش شد');
        } else {
            HomepageSection::create($data);
            session()->flash('success', 'بخش صفحه اصلی با موفقیت اضافه شد');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    private function buildSettings(): array
    {
        $settings = [];

        switch ($this->sectionType) {
            case 'featured_products':
                $settings['product_ids'] = array_values(array_unique(array_filter(array_map('intval', $this->productIds))));
                if ($this->limit) {
                    $settings['limit'] = $this->limit;
                }
                break;

            case 'featured_designs':
                $settings['design_ids'] = array_values(array_unique(array_filter(array_map('intval', $this->designIds))));
                if ($this->limit) {
                    $settings['limit'] = $this->limit;
                }
                break;

            case 'faq':
                if ($this->faqLimit) {
                    $settings['limit'] = $this->faqLimit;
                }
                break;

            default:
                if ($this->backgroundColor !== null && trim($this->backgroundColor) !== '') {
                    $settings['background_color'] = trim($this->backgroundColor);
                }
                if ($this->backgroundImage !== null && trim($this->backgroundImage) !== '') {
                    $settings['background_image'] = trim($this->backgroundImage);
                }
                if ($this->ctaText !== null && trim($this->ctaText) !== '') {
                    $settings['cta_text'] = trim($this->ctaText);
                }
                if ($this->ctaUrl !== null && trim($this->ctaUrl) !== '') {
                    $settings['cta_url'] = trim($this->ctaUrl);
                }
        }

        return $settings;
    }

    public function edit(int $id): void
    {
        $section = HomepageSection::find($id);
        $settings = is_array($section->settings) ? $section->settings : [];

        $this->editingId = $id;
        $this->sectionType = $section->section_type?->value;
        $this->title = $section->title;
        $this->content = $section->content;
        $this->sortOrder = $section->sort_order;
        $this->isActive = $section->is_active;

        $this->productIds = array_map('intval', (array) ($settings['product_ids'] ?? []));
        $this->designIds = array_map('intval', (array) ($settings['design_ids'] ?? []));
        $this->faqLimit = $settings['limit'] ?? null;
        $this->limit = in_array($this->sectionType, ['featured_products', 'featured_designs'], true)
            ? (int) ($settings['limit'] ?? 6)
            : null;
        $this->backgroundColor = $settings['background_color'] ?? null;
        $this->backgroundImage = $settings['background_image'] ?? null;
        $this->ctaText = $settings['cta_text'] ?? null;
        $this->ctaUrl = $settings['cta_url'] ?? null;

        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        HomepageSection::find($id)->delete();
        session()->flash('success', 'بخش صفحه اصلی با موفقیت حذف شد');
    }

    public function resetForm(): void
    {
        $this->sectionType = 'hero';
        $this->title = null;
        $this->content = null;
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->productIds = [];
        $this->designIds = [];
        $this->faqLimit = null;
        $this->limit = null;
        $this->backgroundColor = null;
        $this->backgroundImage = null;
        $this->ctaText = null;
        $this->ctaUrl = null;
        $this->editingId = null;
    }

    public function render()
    {
        return view('livewire.admin.homepage-section-manager', [
            'sections' => HomepageSection::query()
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($sub) {
                        $sub->where('title', 'like', "%{$this->search}%")
                            ->orWhere('content', 'like', "%{$this->search}%");
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'sectionTypes' => HomepageSectionTypeEnum::cases(),
            'products' => Product::query()->active()->purchasable()->orderBy('name')->get(),
            'designs' => app(DesignCatalogService::class)->visibleDesigns()->sortBy('name')->values(),
            'faqsCount' => FaqItem::query()->active()->count(),
            'settingCheatSheet' => self::SETTING_CHEAT_SHEET,
        ])->layout('layouts.admin')->title('مدیریت صفحه اصلی');
    }
}
