<?php

namespace App\Livewire\Admin;

use App\Models\SiteSetting;
use App\Services\SvgSanitizer;
use App\Support\Concerns\AuthorizesAdminActions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class BannerManager extends Component
{
    use AuthorizesAdminActions;
    use WithFileUploads;

    // Banner 1
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

    // Banner 2
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

    // Banner 3
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

    protected function rules(): array
    {
        $hexRegex = 'regex:/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/';
        $rules = [];

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

    protected function validationAttributes(): array
    {
        return [
            'bannerImage1' => 'تصویر دسکتاپ بنر اول',
            'bannerMobileImage1' => 'تصویر موبایل بنر اول',
            'bannerTitle1' => 'عنوان بنر اول',
            'bannerSubtitle1' => 'زیرعنوان بنر اول',
            'bannerCtaText1' => 'متن دکمه بنر اول',
            'bannerCtaUrl1' => 'لینک دکمه بنر اول',
            'bannerTextColor1' => 'رنگ متن بنر اول',
            'bannerButtonBgColor1' => 'رنگ پس‌زمینه دکمه بنر اول',
            'bannerButtonTextColor1' => 'رنگ متن دکمه بنر اول',

            'bannerImage2' => 'تصویر دسکتاپ بنر دوم',
            'bannerMobileImage2' => 'تصویر موبایل بنر دوم',
            'bannerTitle2' => 'عنوان بنر دوم',
            'bannerSubtitle2' => 'زیرعنوان بنر دوم',
            'bannerCtaText2' => 'متن دکمه بنر دوم',
            'bannerCtaUrl2' => 'لینک دکمه بنر دوم',
            'bannerTextColor2' => 'رنگ متن بنر دوم',
            'bannerButtonBgColor2' => 'رنگ پس‌زمینه دکمه بنر دوم',
            'bannerButtonTextColor2' => 'رنگ متن دکمه بنر دوم',

            'bannerImage3' => 'تصویر دسکتاپ بنر سوم',
            'bannerMobileImage3' => 'تصویر موبایل بنر سوم',
            'bannerTitle3' => 'عنوان بنر سوم',
            'bannerSubtitle3' => 'زیرعنوان بنر سوم',
            'bannerCtaText3' => 'متن دکمه بنر سوم',
            'bannerCtaUrl3' => 'لینک دکمه بنر سوم',
            'bannerTextColor3' => 'رنگ متن بنر سوم',
            'bannerButtonBgColor3' => 'رنگ پس‌زمینه دکمه بنر سوم',
            'bannerButtonTextColor3' => 'رنگ متن دکمه بنر سوم',
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

    public function mount(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->loadBanner($i);
        }
    }

    public static function defaultBanner(int $index): array
    {
        return AppearanceManager::defaultBanner($index);
    }

    private function loadBanner(int $index): void
    {
        $default = self::defaultBanner($index);
        $banner = site_setting("homepage_banner_{$index}", $default);
        $banner = is_array($banner) && ! empty($banner) ? $banner : $default;

        $this->{"bannerImagePath{$index}"} = $banner['image_path'] ?? null;
        $this->{"bannerMobileImagePath{$index}"} = $banner['mobile_image_path'] ?? null;
        $this->{"bannerTitle{$index}"} = $banner['title'] ?? null;
        $this->{"bannerSubtitle{$index}"} = $banner['subtitle'] ?? null;
        $this->{"bannerCtaText{$index}"} = $banner['cta_text'] ?? null;
        $this->{"bannerCtaUrl{$index}"} = $banner['cta_url'] ?? null;
        $this->{"bannerTextColor{$index}"} = $banner['text_color'] ?? '#ffffff';
        $this->{"bannerButtonBgColor{$index}"} = $banner['button_bg_color'] ?? '#ffde5b';
        $this->{"bannerButtonTextColor{$index}"} = $banner['button_text_color'] ?? '#010619';
    }

    public function removeBannerImage(int $index): void
    {
        if (! in_array($index, [1, 2, 3], true)) {
            return;
        }

        $path = $this->{"bannerImagePath{$index}"};

        if ($path) {
            if (! $this->isPathUsedByAnotherBanner($path, "homepage_banner_{$index}", 'image_path') && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->{"bannerImagePath{$index}"} = null;
        $this->reset("bannerImage{$index}");

        $default = self::defaultBanner($index);
        $banner = site_setting("homepage_banner_{$index}", $default);
        $banner = is_array($banner) ? $banner : $default;
        $banner['image_path'] = null;

        $this->putSetting("homepage_banner_{$index}", json_encode($banner, JSON_UNESCAPED_UNICODE), 'json');

        session()->flash('success', "تصویر دسکتاپ بنر شماره {$index} حذف شد.");
    }

    public function removeBannerMobileImage(int $index): void
    {
        if (! in_array($index, [1, 2, 3], true)) {
            return;
        }

        $path = $this->{"bannerMobileImagePath{$index}"};

        if ($path) {
            if (! $this->isPathUsedByAnotherBanner($path, "homepage_banner_{$index}", 'mobile_image_path') && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $this->{"bannerMobileImagePath{$index}"} = null;
        $this->reset("bannerMobileImage{$index}");

        $default = self::defaultBanner($index);
        $banner = site_setting("homepage_banner_{$index}", $default);
        $banner = is_array($banner) ? $banner : $default;
        $banner['mobile_image_path'] = null;

        $this->putSetting("homepage_banner_{$index}", json_encode($banner, JSON_UNESCAPED_UNICODE), 'json');

        session()->flash('success', "تصویر موبایل بنر شماره {$index} حذف شد.");
    }

    public function save(): void
    {
        $this->validate();

        for ($index = 1; $index <= 3; $index++) {
            $desktopPath = $this->maybeUpload("bannerImage{$index}", "bannerImagePath{$index}", 'appearance', $index, 'image_path');
            $mobilePath = $this->maybeUpload("bannerMobileImage{$index}", "bannerMobileImagePath{$index}", 'appearance', $index, 'mobile_image_path');

            $banner = [
                'image_path' => $desktopPath,
                'mobile_image_path' => $mobilePath,
                'title' => $this->{"bannerTitle{$index}"},
                'subtitle' => $this->{"bannerSubtitle{$index}"},
                'cta_text' => $this->{"bannerCtaText{$index}"},
                'cta_url' => $this->{"bannerCtaUrl{$index}"},
                'text_color' => $this->{"bannerTextColor{$index}"} ?: '#ffffff',
                'button_bg_color' => $this->{"bannerButtonBgColor{$index}"} ?: '#ffde5b',
                'button_text_color' => $this->{"bannerButtonTextColor{$index}"} ?: '#010619',
            ];

            $this->putSetting("homepage_banner_{$index}", json_encode($banner, JSON_UNESCAPED_UNICODE), 'json');
        }

        session()->flash('success', 'بنرهای صفحه اصلی با موفقیت ذخیره شدند.');
        $this->saved = true;
    }

    private function maybeUpload(string $property, string $pathProperty, string $directory, int $index, string $field): ?string
    {
        $file = $this->{$property};

        if (! $file) {
            return $this->{$pathProperty} ?: null;
        }

        $path = $file->store($directory, 'public');

        $sanitizer = app(SvgSanitizer::class);
        if ($sanitizer->isSvg($file) || $sanitizer->isSvg(Storage::disk('public')->get($path), Storage::disk('public')->mimeType($path))) {
            $this->sanitizeStoredSvg($path, $property);
        }

        $oldPath = $this->{$pathProperty};
        if ($oldPath && ! $this->isPathUsedByAnotherBanner($oldPath, "homepage_banner_{$index}", $field) && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->{$pathProperty} = $path;
        $this->reset($property);

        return $path;
    }

    private function sanitizeStoredSvg(string $path, string $property): void
    {
        $disk = Storage::disk('public');
        $content = $disk->get($path);

        if (! is_string($content)) {
            $disk->delete($path);
            throw ValidationException::withMessages([$property => 'خواندن فایل تصویر ممکن نشد.']);
        }

        $clean = app(SvgSanitizer::class)->sanitize($content);

        if ($clean === null) {
            $disk->delete($path);
            throw ValidationException::withMessages([$property => 'محتوای تصویر نامعتبر یا ناامن است.']);
        }

        $disk->put($path, $clean);
    }

    private function isPathUsedByAnotherBanner(string $path, string $excludedKey, string $excludedField): bool
    {
        for ($i = 1; $i <= 3; $i++) {
            $key = "homepage_banner_{$i}";

            // Check desktop in DB or component state
            $desktopDb = site_setting($key)['image_path'] ?? null;
            $desktopProp = $this->{"bannerImagePath{$i}"};
            if (! ($key === $excludedKey && $excludedField === 'image_path')) {
                if ($desktopDb === $path || $desktopProp === $path) {
                    return true;
                }
            }

            // Check mobile in DB or component state
            $mobileDb = site_setting($key)['mobile_image_path'] ?? null;
            $mobileProp = $this->{"bannerMobileImagePath{$i}"};
            if (! ($key === $excludedKey && $excludedField === 'mobile_image_path')) {
                if ($mobileDb === $path || $mobileProp === $path) {
                    return true;
                }
            }
        }

        return false;
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

    public function render()
    {
        return view('livewire.admin.banner-manager')
            ->layout('layouts.admin')
            ->title('مدیریت بنرهای صفحه اصلی');
    }
}
