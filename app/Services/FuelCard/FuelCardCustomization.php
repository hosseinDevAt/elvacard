<?php

namespace App\Services\FuelCard;

use Illuminate\Validation\Rule;

class FuelCardCustomization
{
    public const VALID_CHIP_SIZES = ['small', 'large'];

    public const VALID_VEHICLE_TYPES = [
        'کامیونت کشنده عمومی',
        'کامیونت عمومی',
        'سواری شخصی',
        'سواری عمومی',
        'مینی‌بوس عمومی',
        'موتورسیکلت شخصی',
    ];

    /**
     * Canonical order and labels for Fuel fulfillment read model.
     */
    public const CANONICAL_FULFILLMENT_LABELS = [
        'plate_number' => 'شماره پلاک',
        'owner_name' => 'نام مالک کارت',
        'phone_number' => 'شماره تلفن',
        'vehicle_type' => 'نوع وسیله نقلیه',
        'vehicle_model' => 'مدل خودرو',
        'oil_serial_number' => 'شماره سریال شرکت نفت',
        'vin' => 'شماره شاسی (VIN)',
        'chip_info' => 'سایز چیپ',
    ];

    /**
     * Historical labels preserved for legacy order compatibility.
     */
    public const HISTORICAL_FULFILLMENT_LABELS = [
        'owner_name' => 'نام مالک کارت',
        'car_info' => 'اطلاعات خودرو',
        'vin' => 'شماره شاسی (VIN)',
        'system_name' => 'نام سامانه سوخت',
        'system_identifier' => 'شناسه سامانه سوخت',
        'plate_number' => 'شماره پلاک',
        'chip_info' => 'سایز چیپ',
    ];

    public const FULFILLMENT_LABELS = self::CANONICAL_FULFILLMENT_LABELS;

    /**
     * Chip size labels.
     */
    public const CHIP_SIZE_LABELS = [
        'small' => 'کوچک',
        'large' => 'بزرگ',
    ];

    public const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Definitive allow-list for Fuel Card customization keys.
     */
    private const ALLOWED_KEYS = [
        'owner_name',
        'phone_number',
        'vehicle_type',
        'vehicle_model',
        'oil_serial_number',
        'car_info',
        'vin',
        'system_name',
        'system_identifier',
        'plate_number',
        'plate_parts',
        'chip_info',
    ];

    public static function rulesFor(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:100'],
            'phone_number' => ['required', 'string', 'regex:/^09[0-9]{9}$/'],
            'vehicle_type' => ['required', 'string', 'in:' . implode(',', self::VALID_VEHICLE_TYPES)],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'oil_serial_number' => ['nullable', 'string', 'max:50'],
            'plate_number' => ['nullable', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'regex:/^[A-Z0-9]{17}$/'],
            'chip_info' => ['required', 'string', 'in:small,large'],
            // Historical fields accepted if passed
            'car_info' => ['nullable', 'string', 'max:255'],
            'system_name' => ['nullable', 'string', 'max:100'],
            'system_identifier' => ['nullable', 'string', 'max:64'],
        ];
    }

    public static function messages(): array
    {
        return [
            'owner_name.required' => 'وارد کردن نام مالک کارت الزامی است.',
            'owner_name.max' => 'نام مالک کارت باید حداکثر ۱۰۰ کاراکتر باشد.',
            'phone_number.required' => 'وارد کردن شماره تلفن الزامی است.',
            'phone_number.regex' => 'شماره تلفن باید ۱۱ رقم و با ۰۹ شروع شود.',
            'vehicle_type.required' => 'انتخاب نوع وسیله نقلیه الزامی است.',
            'vehicle_type.in' => 'نوع وسیله نقلیه انتخاب‌شده نامعتبر است.',
            'vehicle_model.max' => 'مدل خودرو باید حداکثر ۱۰۰ کاراکتر باشد.',
            'oil_serial_number.max' => 'شماره سریال شرکت نفت باید حداکثر ۵۰ کاراکتر باشد.',
            'plate_number.max' => 'شماره پلاک باید حداکثر ۵۰ کاراکتر باشد.',
            'vin.regex' => 'شماره شاسی باید دقیقاً ۱۷ کاراکتر (اعداد و حروف انگلیسی) باشد.',
            'chip_info.required' => 'انتخاب سایز چیپ کارت سوخت الزامی است.',
            'chip_info.in' => 'سایز چیپ انتخاب‌شده نامعتبر است (فقط کوچک یا بزرگ).',
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

            if ($key === 'chip_info') {
                $canonicalChip = self::canonicalizeChipInfo($value);

                if (in_array($canonicalChip, self::VALID_CHIP_SIZES, true)) {
                    $sanitized[$key] = $canonicalChip;
                }

                continue;
            }

            if ($key === 'phone_number') {
                $canonicalPhone = self::canonicalizePhone($value);

                if ($canonicalPhone !== '') {
                    $sanitized[$key] = $canonicalPhone;
                }

                continue;
            }

            if ($key === 'vehicle_type') {
                $type = is_string($value) ? trim($value) : '';

                if (in_array($type, self::VALID_VEHICLE_TYPES, true)) {
                    $sanitized[$key] = $type;
                }

                continue;
            }

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

            if ($key === 'plate_parts' && is_array($value)) {
                $sanitized[$key] = [
                    'part1' => self::canonicalizeDigits($value['part1'] ?? ''),
                    'letter' => is_string($value['letter'] ?? null) ? trim($value['letter']) : '',
                    'part2' => self::canonicalizeDigits($value['part2'] ?? ''),
                    'iran' => self::canonicalizeDigits($value['iran'] ?? ''),
                ];

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

    public static function canonicalizeChipInfo(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $chip = strtolower(trim($value));

        return in_array($chip, self::VALID_CHIP_SIZES, true) ? $chip : '';
    }

    public static function canonicalizePhone(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $digits = strtr(trim($value), self::DIGIT_MAP);

        return (string) preg_replace('/[^0-9]/', '', $digits);
    }

    public static function canonicalizeDigits(mixed $value): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return '';
        }

        $digits = strtr(trim((string) $value), self::DIGIT_MAP);

        return (string) preg_replace('/[^0-9]/', '', $digits);
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

    /**
     * Formats oil company serial number for display.
     */
    public static function formatOilSerialNumber(?string $serial): string
    {
        return trim((string) $serial);
    }

    /**
     * Formats an Iranian plate for structured display.
     *
     * @return array{is_structured: bool, part1?: string, letter?: string, part2?: string, iran?: string, raw: string}
     */
    public static function formatIranPlateDisplay(?string $plate, ?array $parts = null): array
    {
        if (is_array($parts) && ! empty($parts['part1']) && ! empty($parts['letter']) && ! empty($parts['part2']) && ! empty($parts['iran'])) {
            return [
                'is_structured' => true,
                'part1' => (string) $parts['part1'],
                'letter' => (string) $parts['letter'],
                'part2' => (string) $parts['part2'],
                'iran' => (string) $parts['iran'],
                'raw' => "{$parts['part1']} {$parts['letter']} {$parts['part2']} ایران {$parts['iran']}",
            ];
        }

        $plate = trim((string) $plate);

        if ($plate === '') {
            return ['is_structured' => false, 'raw' => ''];
        }

        if (preg_match('/^([0-9۰-۹]{2})\s*([\p{Arabic}A-Za-z]+)\s*([0-9۰-۹]{3})\s*(?:ایران)?\s*([0-9۰-۹]{2})$/u', $plate, $matches)) {
            return [
                'is_structured' => true,
                'part1' => $matches[1],
                'letter' => $matches[2],
                'part2' => $matches[3],
                'iran' => $matches[4],
                'raw' => $plate,
            ];
        }

        return [
            'is_structured' => false,
            'raw' => $plate,
        ];
    }

    private static function maxLength(string $key): int
    {
        return match ($key) {
            'owner_name' => 100,
            'phone_number' => 15,
            'vehicle_type' => 50,
            'vehicle_model' => 100,
            'oil_serial_number' => 50,
            'plate_number' => 50,
            'car_info' => 255,
            'system_name' => 100,
            'system_identifier' => 64,
            'chip_info' => 10,
            default => 100,
        };
    }

    /**
     * Build the Fuel fulfillment read model directly from an OrderItem
     * customization snapshot.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, array{label: string, value: string}> keyed by field
     */
    public static function fulfillmentFields(?array $snapshot): array
    {
        $snapshot = is_array($snapshot) ? $snapshot : [];

        // Distinguish historical snapshot from modern canonical snapshot
        $hasNewKeys = array_key_exists('phone_number', $snapshot) ||
            array_key_exists('vehicle_type', $snapshot) ||
            array_key_exists('vehicle_model', $snapshot) ||
            array_key_exists('oil_serial_number', $snapshot) ||
            array_key_exists('plate_parts', $snapshot);

        if (! $hasNewKeys) {
            $labels = self::HISTORICAL_FULFILLMENT_LABELS;
        } else {
            $labels = self::CANONICAL_FULFILLMENT_LABELS;

            // Preserve display of historical fields if present in snapshot
            foreach (['car_info' => 'اطلاعات خودرو (سابق)', 'system_name' => 'نام سامانه سوخت (سابق)', 'system_identifier' => 'شناسه سامانه سوخت (سابق)'] as $hKey => $hLabel) {
                if (isset($snapshot[$hKey]) && trim((string) $snapshot[$hKey]) !== '') {
                    $labels[$hKey] = $hLabel;
                }
            }
        }

        $fields = [];

        foreach ($labels as $key => $label) {
            $raw = $snapshot[$key] ?? null;
            $value = is_string($raw) ? trim($raw) : '';

            if ($key === 'vin' && $value !== '') {
                $canonical = self::canonicalizeVin($value);
                $value = $canonical !== '' ? $canonical : $value;
            } elseif ($key === 'plate_number' && $value !== '') {
                $value = self::canonicalizePlate($value);
            } elseif ($key === 'phone_number' && $value !== '') {
                $value = self::canonicalizePhone($value);
            } elseif ($key === 'chip_info' && $value !== '') {
                $chip = self::canonicalizeChipInfo($value);
                $value = $chip !== '' ? (self::CHIP_SIZE_LABELS[$chip] ?? $chip) : $value;
            } elseif ($value !== '') {
                $value = (string) preg_replace('/\s+/u', ' ', $value);
            }

            $fields[$key] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $fields;
    }
}
