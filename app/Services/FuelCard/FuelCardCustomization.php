<?php

namespace App\Services\FuelCard;

class FuelCardCustomization
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * The definitive Fuel allow-list: owner/name, car information, VIN,
     * system and its identifier, plate number, and chip info/size. Every
     * submitted key outside this list (including every Bank-card field and
     * product/color/design metadata) is stripped by sanitize().
     */
    private const ALLOWED_KEYS = [
        'owner_name',
        'car_info',
        'vin',
        'system_name',
        'system_identifier',
        'plate_number',
        'chip_info',
    ];

    public static function rulesFor(): array
    {
        return [
            'owner_name' => ['nullable', 'string', 'max:100'],
            'car_info' => ['nullable', 'string', 'max:255'],
            'vin' => ['nullable', 'string', 'regex:/^[A-Z0-9]{17}$/'],
            'system_name' => ['nullable', 'string', 'max:100'],
            'system_identifier' => ['nullable', 'string', 'max:64'],
            'plate_number' => ['nullable', 'string', 'max:20'],
            'chip_info' => ['nullable', 'string', 'max:100'],
        ];
    }

    public static function messages(): array
    {
        return [
            'owner_name.max' => 'نام مالک کارت باید حداکثر ۱۰۰ کاراکتر باشد.',
            'car_info.max' => 'اطلاعات خودرو باید حداکثر ۲۵۵ کاراکتر باشد.',
            'vin.regex' => 'شماره شاسی باید دقیقاً ۱۷ کاراکتر (اعداد و حروف انگلیسی) باشد.',
            'system_name.max' => 'نام سامانه سوخت باید حداکثر ۱۰۰ کاراکتر باشد.',
            'system_identifier.max' => 'شناسه سامانه سوخت باید حداکثر ۶۴ کاراکتر باشد.',
            'plate_number.max' => 'شماره پلاک باید حداکثر ۲۰ کاراکتر باشد.',
            'chip_info.max' => 'اطلاعات چیپ باید حداکثر ۱۰۰ کاراکتر باشد.',
        ];
    }

    public static function sanitize(array $payload): array
    {
        $rawCustomization = is_array($payload['customization_json'] ?? null)
            ? $payload['customization_json']
            : [];

        $sanitized = [];

        foreach (self::ALLOWED_KEYS as $key) {
            if (! array_key_exists($key, $rawCustomization)) {
                continue;
            }

            $value = $rawCustomization[$key];

            if ($key === 'vin') {
                $canonical = self::canonicalizeVin($value);

                if (preg_match('/^[A-Z0-9]{17}$/', $canonical) === 1) {
                    $sanitized[$key] = $canonical;
                }

                continue;
            }

            if ($key === 'plate_number') {
                $canonical = self::canonicalizePlate($value);

                if ($canonical !== '') {
                    $sanitized[$key] = $canonical;
                }

                continue;
            }

            if (is_string($value)) {
                $text = self::canonicalizeText($value, self::maxLength($key));

                if ($text !== '') {
                    $sanitized[$key] = $text;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Normalizes a VIN: trims, maps Persian/Arabic digits to ASCII, uppercases
     * letters, and removes every non-alphanumeric character. The result is only
     * stored when it is exactly 17 ASCII alphanumeric characters.
     */
    public static function canonicalizeVin(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = mb_strtoupper(strtr(trim($value), self::DIGIT_MAP), 'UTF-8');

        return (string) preg_replace('/[^A-Z0-9]/', '', $value);
    }

    /**
     * Normalizes a plate number: trims, maps Persian/Arabic digits to ASCII,
     * uppercases letters, and collapses inner whitespace to a single space.
     */
    public static function canonicalizePlate(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = mb_strtoupper(strtr(trim($value), self::DIGIT_MAP), 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    public static function canonicalizeText(?string $value, int $max): string
    {
        if ($value === null) {
            return '';
        }

        $text = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        return mb_substr($text, 0, $max);
    }

    private static function maxLength(string $key): int
    {
        return match ($key) {
            'owner_name' => 100,
            'car_info' => 255,
            'system_name' => 100,
            'system_identifier' => 64,
            'chip_info' => 100,
            default => 100,
        };
    }
}
