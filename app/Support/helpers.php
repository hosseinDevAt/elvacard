<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

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
     * Returns null for dangerous schemes like javascript:, data:, vbscript:.
     */
    function safe_url(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}

if (! function_exists('normalize_phone')) {
    /**
     * Normalize a phone number: strip separators and convert Persian/Arabic
     * digits to ASCII. Used to keep OTP/rate-limit keys consistent.
     */
    function normalize_phone(string $phone): string
    {
        $phone = trim($phone);
        $phone = str_replace([' ', '-', '(', ')'], '', $phone);

        return strtr($phone, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }
}
