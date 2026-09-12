<?php

namespace App\Livewire\Admin;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppearanceManager extends Component
{
    use WithFileUploads;

    // Identity
    public ?string $siteName = null;
    public $siteLogo;
    public $siteFavicon;
    public ?string $siteLogoPath = null;
    public ?string $siteFaviconPath = null;

    // Homepage banners
    public ?string $bannerTitle1 = null;
    public ?string $bannerSubtitle1 = null;
    public ?string $bannerCtaText1 = null;
    public ?string $bannerCtaUrl1 = null;
    public $bannerImage1;
    public ?string $bannerImagePath1 = null;

    public ?string $bannerTitle2 = null;
    public ?string $bannerSubtitle2 = null;
    public ?string $bannerCtaText2 = null;
    public ?string $bannerCtaUrl2 = null;
    public $bannerImage2;
    public ?string $bannerImagePath2 = null;

    public ?string $bannerTitle3 = null;
    public ?string $bannerSubtitle3 = null;
    public ?string $bannerCtaText3 = null;
    public ?string $bannerCtaUrl3 = null;
    public $bannerImage3;
    public ?string $bannerImagePath3 = null;

    // Footer
    public ?string $footerAboutText = null;
    public ?string $contactPhone = null;
    public ?string $contactEmail = null;
    public ?string $contactAddress = null;

    // Header menu items (id => [title, sort_order, is_active])
    public array $menuItems = [];

    public bool $saved = false;

    protected function rules(): array
    {
        return [
            'siteName' => 'required|string|max:255',
            'siteLogo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'siteFavicon' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,ico|max:2048',
            'bannerImage1' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'bannerImage2' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'bannerImage3' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'bannerTitle1' => 'nullable|string|max:255',
            'bannerSubtitle1' => 'nullable|string|max:500',
            'bannerCtaText1' => 'nullable|string|max:100',
            'bannerTitle2' => 'nullable|string|max:255',
            'bannerSubtitle2' => 'nullable|string|max:500',
            'bannerCtaText2' => 'nullable|string|max:100',
            'bannerTitle3' => 'nullable|string|max:255',
            'bannerSubtitle3' => 'nullable|string|max:500',
            'bannerCtaText3' => 'nullable|string|max:100',
            'bannerCtaUrl1' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'bannerCtaUrl2' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'bannerCtaUrl3' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'footerAboutText' => 'nullable|string|max:1000',
            'contactPhone' => 'nullable|string|max:50',
            'contactEmail' => 'nullable|email|max:255',
            'contactAddress' => 'nullable|string|max:500',
            'menuItems' => 'required|array|min:1',
            'menuItems.*.title' => 'required|string|max:255',
            'menuItems.*.sort_order' => 'required|integer|min:0',
            'menuItems.*.is_active' => 'boolean',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'siteName' => 'نام سایت',
            'siteLogo' => 'لوگوی سایت',
            'siteFavicon' => 'فاوآیکون سایت',
            'bannerImage1' => 'تصویر بنر اول',
            'bannerImage2' => 'تصویر بنر دوم',
            'bannerImage3' => 'تصویر بنر سوم',
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
        $this->siteName = (string) site_setting('site_name', config('app.name'));
        $this->siteLogoPath = site_setting('site_logo');
        $this->siteFaviconPath = site_setting('site_favicon');
        $this->footerAboutText = site_setting('footer_about_text');
        $this->contactPhone = site_setting('contact_phone');
        $this->contactEmail = site_setting('contact_email');
        $this->contactAddress = site_setting('contact_address');

        $this->loadBanner(1);
        $this->loadBanner(2);
        $this->loadBanner(3);

        $this->menuItems = MenuItem::query()
            ->whereHas('menu', fn ($q) => $q->where('location', 'header'))
            ->whereNotNull('route_key')
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (MenuItem $item) => [
                $item->id => [
                    'title' => $item->title,
                    'sort_order' => $item->sort_order,
                    'is_active' => $item->is_active,
                    'route_key' => $item->route_key,
                ],
            ])
            ->toArray();
    }

    private function loadBanner(int $index): void
    {
        $banner = site_setting("homepage_banner_{$index}", []);
        $banner = is_array($banner) ? $banner : [];

        $this->{"bannerTitle{$index}"} = $banner['title'] ?? null;
        $this->{"bannerSubtitle{$index}"} = $banner['subtitle'] ?? null;
        $this->{"bannerCtaText{$index}"} = $banner['cta_text'] ?? null;
        $this->{"bannerCtaUrl{$index}"} = $banner['cta_url'] ?? null;
        $this->{"bannerImagePath{$index}"} = $banner['image_path'] ?? null;
    }

    public function save(): void
    {
        $this->validate();

        $this->putSetting('site_name', $this->siteName, 'string');

        $this->maybeUpload('siteLogo', 'site_logo', 'siteLogoPath', 'appearance');
        $this->maybeUpload('siteFavicon', 'site_favicon', 'siteFaviconPath', 'appearance');

        $this->putSetting('footer_about_text', $this->footerAboutText, 'string');
        $this->putSetting('contact_phone', $this->contactPhone, 'string');
        $this->putSetting('contact_email', $this->contactEmail, 'string');
        $this->putSetting('contact_address', $this->contactAddress, 'string');

        for ($index = 1; $index <= 3; $index++) {
            $banner = [
                'image_path' => $this->maybeUpload("bannerImage{$index}", null, "bannerImagePath{$index}", 'appearance'),
                'title' => $this->{"bannerTitle{$index}"},
                'subtitle' => $this->{"bannerSubtitle{$index}"},
                'cta_text' => $this->{"bannerCtaText{$index}"},
                'cta_url' => $this->{"bannerCtaUrl{$index}"},
            ];

            $this->putSetting("homepage_banner_{$index}", json_encode($banner, JSON_UNESCAPED_UNICODE), 'json');
        }

        $this->saveMenuItems();

        session()->flash('success', 'ظاهر سایت با موفقیت ذخیره شد');
        $this->saved = true;
    }

    /**
     * Upload the temp file (if present) to the public disk and return/update its path.
     */
    private function maybeUpload(string $property, ?string $settingKey, string $pathProperty, string $directory): ?string
    {
        $file = $this->{$property};

        if (! $file) {
            return $this->{$pathProperty} ?: null;
        }

        $path = $file->store($directory, 'public');

        if ($this->{$pathProperty} && Storage::disk('public')->exists($this->{$pathProperty})) {
            Storage::disk('public')->delete($this->{$pathProperty});
        }

        $this->{$pathProperty} = $path;

        if ($settingKey) {
            $this->putSetting($settingKey, $path, 'string');
        }

        $this->reset($property);

        return $path;
    }

    private function putSetting(string $key, mixed $value, string $type): void
    {
        $setting = SiteSetting::firstOrNew(['key' => $key]);

        $setting->value = $value;
        $setting->type = $type;
        $setting->group = $this->settingGroup($key);
        $setting->is_public = true;

        $setting->save();
    }

    private function settingGroup(string $key): string
    {
        return match (true) {
            in_array($key, ['site_name', 'site_logo', 'site_favicon'], true) => 'identity',
            str_contains($key, 'banner') => 'homepage',
            str_contains($key, 'contact') => 'contact',
            default => 'footer',
        };
    }

    private function saveMenuItems(): void
    {
        foreach ($this->menuItems as $id => $data) {
            $item = MenuItem::find($id);

            if (! $item) {
                continue;
            }

            $item->update([
                'title' => $data['title'],
                'sort_order' => (int) $data['sort_order'],
                'is_active' => (bool) $data['is_active'],
            ]);
        }
    }

    public function render()
    {
        return view('livewire.admin.appearance-manager')
            ->layout('layouts.admin')
            ->title('ظاهر سایت');
    }
}