<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use App\Services\SvgSanitizer;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class BannerManager extends Component
{
    use AuthorizesAdminActions;
    use WithFileUploads;

    /**
     * Dynamic collection of all banners.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $banners = [];

    /**
     * Temporary desktop uploads keyed by banner ID.
     */
    public array $desktopUploads = [];

    /**
     * Temporary mobile uploads keyed by banner ID.
     */
    public array $mobileUploads = [];

    // =========================================================================
    // Legacy public properties for backward compatibility with existing tests
    // =========================================================================
    public ?string $bannerTitle1 = null;
    public ?string $bannerSubtitle1 = null;
    public ?string $bannerCtaText1 = null;
    public ?string $bannerCtaUrl1 = null;
    public $bannerImage1;
    public ?string $bannerImagePath1 = null;
    public $bannerMobileImage1;
    public ?string $bannerMobileImagePath1 = null;
    public ?string $bannerTextColor1 = '#ffffff';
    public ?string $bannerButtonBgColor1 = '#ffde5b';
    public ?string $bannerButtonTextColor1 = '#010619';

    public ?string $bannerTitle2 = null;
    public ?string $bannerSubtitle2 = null;
    public ?string $bannerCtaText2 = null;
    public ?string $bannerCtaUrl2 = null;
    public $bannerImage2;
    public ?string $bannerImagePath2 = null;
    public $bannerMobileImage2;
    public ?string $bannerMobileImagePath2 = null;
    public ?string $bannerTextColor2 = '#ffffff';
    public ?string $bannerButtonBgColor2 = '#ffde5b';
    public ?string $bannerButtonTextColor2 = '#010619';

    public ?string $bannerTitle3 = null;
    public ?string $bannerSubtitle3 = null;
    public ?string $bannerCtaText3 = null;
    public ?string $bannerCtaUrl3 = null;
    public $bannerImage3;
    public ?string $bannerImagePath3 = null;
    public $bannerMobileImage3;
    public ?string $bannerMobileImagePath3 = null;
    public ?string $bannerTextColor3 = '#ffffff';
    public ?string $bannerButtonBgColor3 = '#ffde5b';
    public ?string $bannerButtonTextColor3 = '#010619';

    public bool $saved = false;

    public function mount(): void
    {
        $this->loadBanners();
    }

    public function loadBanners(): void
    {
        $loaded = homepage_banners();
        $this->banners = [];

        foreach ($loaded as $banner) {
            if (! is_array($banner)) {
                continue;
            }

            $this->banners[] = [
                'id' => $banner['id'] ?? (string) Str::uuid(),
                'title' => $banner['title'] ?? null,
                'subtitle' => $banner['subtitle'] ?? null,
                'cta_text' => $banner['cta_text'] ?? null,
                'cta_url' => $banner['cta_url'] ?? null,
                'image_path' => $banner['image_path'] ?? null,
                'mobile_image_path' => $banner['mobile_image_path'] ?? null,
                'text_color' => filled($banner['text_color'] ?? null) ? $banner['text_color'] : '#ffffff',
                'button_bg_color' => filled($banner['button_bg_color'] ?? null) ? $banner['button_bg_color'] : '#ffde5b',
                'button_text_color' => filled($banner['button_text_color'] ?? null) ? $banner['button_text_color'] : '#010619',
            ];
        }

        $this->syncLegacyFromCollection();
    }

    public function addBanner(): void
    {
        $newId = (string) Str::uuid();
        $this->banners[] = [
            'id' => $newId,
            'title' => '',
            'subtitle' => '',
            'cta_text' => '',
            'cta_url' => '',
            'image_path' => null,
            'mobile_image_path' => null,
            'text_color' => '#ffffff',
            'button_bg_color' => '#ffde5b',
            'button_text_color' => '#010619',
        ];

        $this->syncLegacyFromCollection();
    }

    public function removeBanner(int $index): void
    {
        if (! isset($this->banners[$index])) {
            return;
        }

        $banner = $this->banners[$index];
        $id = $banner['id'] ?? null;

        if (! empty($banner['image_path'])) {
            $this->deletePhysicalFileIfNotUsed($banner['image_path'], $id, 'image_path');
        }
        if (! empty($banner['mobile_image_path'])) {
            $this->deletePhysicalFileIfNotUsed($banner['mobile_image_path'], $id, 'mobile_image_path');
        }

        unset($this->banners[$index]);
        if ($id) {
            unset($this->desktopUploads[$id]);
            unset($this->mobileUploads[$id]);
        }

        $this->banners = array_values($this->banners);
        $this->syncLegacyFromCollection();
        session()->flash('success', 'بنر با موفقیت حذف شد.');
    }

    public function moveUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->banners[$index])) {
            return;
        }

        $temp = $this->banners[$index];
        $this->banners[$index] = $this->banners[$index - 1];
        $this->banners[$index - 1] = $temp;
        $this->banners = array_values($this->banners);
        $this->syncLegacyFromCollection();
    }

    public function moveDown(int $index): void
    {
        if ($index >= count($this->banners) - 1 || ! isset($this->banners[$index])) {
            return;
        }

        $temp = $this->banners[$index];
        $this->banners[$index] = $this->banners[$index + 1];
        $this->banners[$index + 1] = $temp;
        $this->banners = array_values($this->banners);
        $this->syncLegacyFromCollection();
    }

    public function removeDesktopImage(int $index): void
    {
        if (! isset($this->banners[$index])) {
            return;
        }

        $path = $this->banners[$index]['image_path'] ?? null;
        $id = $this->banners[$index]['id'] ?? null;

        if ($path) {
            $this->deletePhysicalFileIfNotUsed($path, $id, 'image_path');
        }

        $this->banners[$index]['image_path'] = null;
        if ($id && isset($this->desktopUploads[$id])) {
            unset($this->desktopUploads[$id]);
        }

        $this->syncLegacyFromCollection();
        session()->flash('success', 'تصویر دسکتاپ بنر حذف شد.');
    }

    public function removeMobileImage(int $index): void
    {
        if (! isset($this->banners[$index])) {
            return;
        }

        $path = $this->banners[$index]['mobile_image_path'] ?? null;
        $id = $this->banners[$index]['id'] ?? null;

        if ($path) {
            $this->deletePhysicalFileIfNotUsed($path, $id, 'mobile_image_path');
        }

        $this->banners[$index]['mobile_image_path'] = null;
        if ($id && isset($this->mobileUploads[$id])) {
            unset($this->mobileUploads[$id]);
        }

        $this->syncLegacyFromCollection();
        session()->flash('success', 'تصویر موبایل بنر حذف شد.');
    }

    public function removeBannerImage(int $index): void
    {
        if (! in_array($index, [1, 2, 3], true)) {
            return;
        }

        $this->removeDesktopImage($index - 1);
        $this->save();
        session()->flash('success', "تصویر بنر شماره {$index} حذف شد");
    }

    public function removeBannerMobileImage(int $index): void
    {
        if (! in_array($index, [1, 2, 3], true)) {
            return;
        }

        $this->removeMobileImage($index - 1);
        $this->save();
        session()->flash('success', "تصویر موبایل بنر شماره {$index} حذف شد.");
    }

    protected function rules(): array
    {
        $hexRegex = 'regex:/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/';

        $rules = [
            'banners' => 'array',
            'banners.*.title' => 'nullable|string|max:255',
            'banners.*.subtitle' => 'nullable|string|max:500',
            'banners.*.cta_text' => 'nullable|string|max:100',
            'banners.*.cta_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'banners.*.text_color' => ['nullable', $hexRegex],
            'banners.*.button_bg_color' => ['nullable', $hexRegex],
            'banners.*.button_text_color' => ['nullable', $hexRegex],
            'desktopUploads.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'mobileUploads.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        // Legacy rules so existing test sets pass validation
        for ($i = 1; $i <= 3; $i++) {
            $rules["bannerImage{$i}"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048';
            $rules["bannerMobileImage{$i}"] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048';
            $rules["bannerTitle{$i}"] = 'nullable|string|max:255';
            $rules["bannerSubtitle{$i}"] = 'nullable|string|max:500';
            $rules["bannerCtaText{$i}"] = 'nullable|string|max:100';
            $rules["bannerCtaUrl{$i}"] = ['nullable', 'string', 'max:255', $this->safeUrlRule()];
            $rules["bannerTextColor{$i}"] = ['nullable', $hexRegex];
            $rules["bannerButtonBgColor{$i}"] = ['nullable', $hexRegex];
            $rules["bannerButtonTextColor{$i}"] = ['nullable', $hexRegex];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'regex' => 'کد رنگ باید یک مقدار هگز معتبر باشد (مانند #ffffff یا #fff).',
            'mimes' => 'تصویر باید با یکی از پسوندهای jpeg, png, jpg, webp باشد.',
            'max' => 'حجم تصویر نباید بیشتر از ۲ مگابایت باشد.',
        ];
    }

    private function safeUrlRule(): callable
    {
        return function ($attribute, $value, $fail) {
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
        };
    }

    public function save(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $idx = $i - 1;
            if ($this->{"bannerImage{$i}"}) {
                $this->ensureBannerExists($idx);
                $this->desktopUploads[$this->banners[$idx]['id']] = $this->{"bannerImage{$i}"};
                $this->{"bannerImage{$i}"} = null;
            }
            if ($this->{"bannerMobileImage{$i}"}) {
                $this->ensureBannerExists($idx);
                $this->mobileUploads[$this->banners[$idx]['id']] = $this->{"bannerMobileImage{$i}"};
                $this->{"bannerMobileImage{$i}"} = null;
            }
        }
        $this->validate();

        foreach ($this->banners as $index => &$banner) {
            $id = $banner['id'] ?? (string) Str::uuid();
            $banner['id'] = $id;

            // Handle Desktop Upload via desktopUploads
            if (! empty($this->desktopUploads[$id])) {
                $file = $this->desktopUploads[$id];
                $path = $file->store('appearance', 'public');
                $this->sanitizeSvgIfNeeded($file, $path, "desktopUploads.{$id}");

                $oldPath = $banner['image_path'] ?? null;
                if ($oldPath && $oldPath !== $path) {
                    $this->deletePhysicalFileIfNotUsed($oldPath, $id, 'image_path');
                }
                $banner['image_path'] = $path;
                unset($this->desktopUploads[$id]);
            }

            // Handle Mobile Upload via mobileUploads
            if (! empty($this->mobileUploads[$id])) {
                $file = $this->mobileUploads[$id];
                $path = $file->store('appearance', 'public');
                $this->sanitizeSvgIfNeeded($file, $path, "mobileUploads.{$id}");

                $oldPath = $banner['mobile_image_path'] ?? null;
                if ($oldPath && $oldPath !== $path) {
                    $this->deletePhysicalFileIfNotUsed($oldPath, $id, 'mobile_image_path');
                }
                $banner['mobile_image_path'] = $path;
                unset($this->mobileUploads[$id]);
            }

            $banner['text_color'] = filled($banner['text_color'] ?? null) ? $banner['text_color'] : '#ffffff';
            $banner['button_bg_color'] = filled($banner['button_bg_color'] ?? null) ? $banner['button_bg_color'] : '#ffde5b';
            $banner['button_text_color'] = filled($banner['button_text_color'] ?? null) ? $banner['button_text_color'] : '#010619';
        }
        unset($banner);

        $this->banners = array_values($this->banners);

        // Save dynamic collection
        $this->putSetting('homepage_banners', json_encode($this->banners, JSON_UNESCAPED_UNICODE), 'json');

        // Synchronize legacy slots 1..3 for backward compatibility
        for ($i = 1; $i <= 3; $i++) {
            $legacy = $this->banners[$i - 1] ?? null;
            $this->putSetting("homepage_banner_{$i}", json_encode($legacy, JSON_UNESCAPED_UNICODE), 'json');
        }

        $this->syncLegacyFromCollection();

        session()->flash('success', 'بنرهای صفحه اصلی با موفقیت ذخیره شدند.');
        $this->saved = true;
    }

    public function updated($property, $value = null): void
    {
        if (preg_match('/^banner(Title|Subtitle|CtaText|CtaUrl|TextColor|ButtonBgColor|ButtonTextColor)([1-3])$/', $property, $m)) {
            $field = Str::snake($m[1]);
            $idx = (int) $m[2] - 1;
            $this->ensureBannerExists($idx);
            $this->banners[$idx][$field] = $value;
        }

        if (preg_match('/^banner(Image|MobileImage)([1-3])$/', $property, $m)) {
            $idx = (int) $m[2] - 1;
            $this->ensureBannerExists($idx);
            $id = $this->banners[$idx]['id'];
            if ($m[1] === 'Image') {
                $this->desktopUploads[$id] = $value;
            } else {
                $this->mobileUploads[$id] = $value;
            }
        }
    }

    private function ensureBannerExists(int $index): void
    {
        while (count($this->banners) <= $index) {
            $this->addBanner();
        }
    }

    private function syncLegacyFromCollection(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $banner = $this->banners[$i - 1] ?? null;

            $this->{"bannerTitle{$i}"} = $banner['title'] ?? null;
            $this->{"bannerSubtitle{$i}"} = $banner['subtitle'] ?? null;
            $this->{"bannerCtaText{$i}"} = $banner['cta_text'] ?? null;
            $this->{"bannerCtaUrl{$i}"} = $banner['cta_url'] ?? null;
            $this->{"bannerImagePath{$i}"} = $banner['image_path'] ?? null;
            $this->{"bannerMobileImagePath{$i}"} = $banner['mobile_image_path'] ?? null;
            $this->{"bannerTextColor{$i}"} = $banner['text_color'] ?? '#ffffff';
            $this->{"bannerButtonBgColor{$i}"} = $banner['button_bg_color'] ?? '#ffde5b';
            $this->{"bannerButtonTextColor{$i}"} = $banner['button_text_color'] ?? '#010619';
        }
    }

    private function deletePhysicalFileIfNotUsed(string $path, ?string $excludedBannerId, string $excludedField): void
    {
        // 1. Check current in-memory collection (the true source of state)
        foreach ($this->banners as $b) {
            $bId = $b['id'] ?? null;
            if (! ($bId === $excludedBannerId && $excludedField === 'image_path') && ($b['image_path'] ?? null) === $path) {
                return;
            }
            if (! ($bId === $excludedBannerId && $excludedField === 'mobile_image_path') && ($b['mobile_image_path'] ?? null) === $path) {
                return;
            }
        }

        // 2. Determine which legacy slot corresponds to $excludedBannerId
        $excludedLegacyIndex = null;
        foreach ($this->banners as $idx => $b) {
            if (($b['id'] ?? null) === $excludedBannerId) {
                $excludedLegacyIndex = $idx + 1;
                break;
            }
        }

        // 3. Check other legacy slots for shared files
        for ($i = 1; $i <= 3; $i++) {
            if ($i === $excludedLegacyIndex) {
                continue;
            }
            $legacy = site_setting("homepage_banner_{$i}");
            if (is_array($legacy)) {
                if (($legacy['image_path'] ?? null) === $path || ($legacy['mobile_image_path'] ?? null) === $path) {
                    return;
                }
            }
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function sanitizeSvgIfNeeded($file, string $path, string $property): void
    {
        $sanitizer = app(SvgSanitizer::class);
        if ($sanitizer->isSvg($file) || $sanitizer->isSvg(Storage::disk('public')->get($path), Storage::disk('public')->mimeType($path))) {
            $disk = Storage::disk('public');
            $content = $disk->get($path);
            if (! is_string($content)) {
                $disk->delete($path);
                throw ValidationException::withMessages([$property => 'خواندن فایل تصویر ممکن نشد.']);
            }
            $clean = $sanitizer->sanitize($content);
            if ($clean === null) {
                $disk->delete($path);
                throw ValidationException::withMessages([$property => 'محتوای تصویر نامعتبر یا ناامن است.']);
            }
            $disk->put($path, $clean);
        }
    }

    private function putSetting(string $key, mixed $value, string $type): void
    {
        $setting = SiteSetting::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->type = $type;
        $setting->group = 'homepage';
        $setting->is_public = true;
        $setting->save();
    }

    public static function defaultBanner(int $index): array
    {
        return AppearanceManager::defaultBanner($index);
    }

    public function render()
    {
        return view('livewire.admin.banner-manager')
            ->layout('layouts.admin')
            ->title('مدیریت بنرهای صفحه اصلی');
    }
}
