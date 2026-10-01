<?php

use App\Models\SiteSetting;
use App\Services\IconManager;
use App\Support\Dates\DateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

if (! function_exists('jalali_date')) {
    /**
     * Format a Carbon value as a Jalali date in the configured business timezone.
     *
     * Supported formats: date, datetime, time, year, month, yearmonth, long.
     */
    function jalali_date(CarbonInterface|string|null $value, string $format = 'date'): string
    {
        if ($value === null) {
            return '';
        }

        $carbon = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return app(DateService::class)->match($carbon, $format);
    }
}

if (! function_exists('jalali_relative')) {
    /**
     * Persian relative time ("۳ ساعت پیش", "دیروز", "۲ روز پیش").
     */
    function jalali_relative(CarbonInterface|string|null $value): string
    {
        if ($value === null) {
            return '';
        }

        $carbon = $value instanceof CarbonInterface ? $value : Carbon::parse($value);

        return app(DateService::class)->relativeForHumans($carbon);
    }
}

if (! function_exists('jalali_now')) {
    /**
     * Current Jalali date/datetime/time string in business timezone.
     */
    function jalali_now(string $format = 'date'): string
    {
        return app(DateService::class)->match(Carbon::now(), $format);
    }
}

if (! function_exists('fa_digits')) {
    /**
     * Convert ASCII digits to Persian (Farsi-Extended) digits.
     * Only intended for calendar/date UI.
     */
    function fa_digits(string|int|float $value): string
    {
        return app(DateService::class)->digits($value);
    }
}

if (! function_exists('site_setting')) {
    /**
     * Get a public site setting by key, cast according to its `type` column.
     * Only public settings are cached; private settings are never cached.
     * The cache is invalidated on create/update/delete via the model hook.
     */
    function site_setting(string $key, mixed $default = null): mixed
    {
        $publicSettings = Cache::remember('settings.public', 3600, function () {
            $map = [];

            foreach (SiteSetting::query()->where('is_public', true)->get(['key', 'value', 'type']) as $setting) {
                $map[$setting->key] = ['value' => $setting->value, 'type' => $setting->type];
            }

            return $map;
        });

        if (! isset($publicSettings[$key]) || $publicSettings[$key]['value'] === null) {
            return $default;
        }

        $value = $publicSettings[$key]['value'];
        $type = $publicSettings[$key]['type'];

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'json' => json_decode($value, true) ?? $default,
            default => $value,
        };
    }
}

if (! function_exists('safe_url')) {
    /**
     * Return the URL only if it uses a safe scheme (http/https or site-relative).
     * Returns null for dangerous schemes (javascript:, data:, vbscript:),
     * protocol-relative URLs (//evil.com), backslash variants (/\evil.com, \\evil.com),
     * and invalid URL formats.
     */
    function safe_url(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        // Reject control characters, newlines, and unencoded whitespace
        if (preg_match('/[\x00-\x1F\x7F\s]/', $url)) {
            return null;
        }

        // Reject any URL starting with a backslash (e.g. \evil.com, \\evil.com, \/evil.com)
        if (str_starts_with($url, '\\')) {
            return null;
        }

        // Site-relative URL: must start with a single slash '/'
        // Must NOT start with '//', '/\', or any other slash/backslash combination
        if (str_starts_with($url, '/')) {
            if (str_starts_with($url, '//') || str_starts_with($url, '/\\') || preg_match('#^/[/\\\\]#', $url)) {
                return null;
            }

            return $url;
        }

        // Absolute URL: must have a valid http or https scheme and a host
        $parsed = parse_url($url);
        if (! is_array($parsed)) {
            return null;
        }

        $scheme = strtolower($parsed['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        // Must start with http:// or https:// (not http:evil.com or http:/evil.com)
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $host = $parsed['host'] ?? '';
        if ($host === '' || str_contains($host, '\\')) {
            return null;
        }

        return $url;
    }
}

if (! function_exists('normalize_phone')) {
    /**
     * Normalize a phone number: strip separators, convert Persian/Arabic digits
     * to ASCII, and translate common Iranian international prefixes to the
     * local canonical form. The result is NOT validated — use
     * {@see is_valid_iranian_mobile()} for that.
     *
     * Prefix handling:
     *  +98…  → 0…
     *  0098… → 0…
     */
    function normalize_phone(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);

        $phone = strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (str_starts_with($phone, '+98')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '0098')) {
            $phone = '0'.substr($phone, 4);
        }

        return $phone;
    }
}

if (! function_exists('is_valid_iranian_mobile')) {
    /**
     * Whether the given (possibly non-canonical) phone number normalizes into
     * the canonical Iranian mobile form: exactly 11 ASCII digits starting with
     * "09" (e.g. "09123456789").
     *
     * Normalization is applied before the check — it will NOT fabricate
     * validity from garbage inputs.
     */
    function is_valid_iranian_mobile(?string $phone): bool
    {
        if (! is_string($phone) || trim($phone) === '') {
            return false;
        }

        return preg_match('/^09\d{9}$/', normalize_phone($phone)) === 1;
    }
}

if (! function_exists('site_icon_variant')) {
    /**
     * Resolve the admin-configured variant for a controllable icon slot.
     * Falls back to the slot default when nothing is stored or the stored
     * value is not in the server-side allowlist.
     */
    function site_icon_variant(string $key): string
    {
        return app(IconManager::class)->variant($key);
    }
}

if (! function_exists('site_icon_enabled')) {
    /**
     * Whether a controllable icon slot should be rendered. Always true for
     * slots that cannot be disabled by the admin.
     */
    function site_icon_enabled(string $key): bool
    {
        return app(IconManager::class)->enabled($key);
    }
}

if (! function_exists('site_favicon_url')) {
    /**
     * Public URL of the favicon. Returns the internal Elvacard fallback when
     * no favicon has been uploaded - the <link rel="icon"> is never absent.
     */
    function site_favicon_url(): string
    {
        $path = site_setting('site_favicon');

        return $path ? asset('storage/'.$path) : asset('favicon.svg');
    }
}

if (! function_exists('site_favicon_type')) {
    /**
     * Correct MIME type for the current favicon so SVG favicons use
     * image/svg+xml and old .ico files use image/x-icon.
     */
    function site_favicon_type(): string
    {
        $path = site_setting('site_favicon');

        $ext = $path ? strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) : 'svg';

        return match ($ext) {
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }
}

if (! function_exists('icon_class')) {
    /**
     * Resolves the class attribute of an icon component.
     *
     * Tailwind emits size utilities in a fixed stylesheet order, so a component
     * default such as `h-5 w-5` silently beats a caller's `h-4 w-4` however the
     * two are merged. The caller therefore always wins: an explicit `$size`
     * prop, or the caller's own height/width utility, is used as-is, and
     * `$default` only applies when the caller expressed no size at all. Every
     * icon component resolves its classes through this helper so all of them
     * behave identically.
     */
    function icon_class(?string $callerClass, string $default = 'h-5 w-5', ?string $size = null): string
    {
        $classes = trim((string) preg_replace('/\s+/', ' ', (string) $callerClass));

        if ($size !== null && trim($size) !== '') {
            return trim($size.' '.$classes);
        }

        if (preg_match('/(?:^|\s)[hw]-(?:\d|\.|\[)/', $classes) === 1) {
            return $classes;
        }

        return trim($default.' '.$classes);
    }
}
