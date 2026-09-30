<?php

namespace App\Services\FuelCard;

class FuelCardCustomization
{
    public const VALID_CHIP_SIZES = ['small', 'large'];

    /**
     * Human labels of the Fuel fulfillment read model, in the order an
     * operator needs them to produce and dispatch a physical card.
     */
    public const FULFILLMENT_LABELS = [
        'owner_name' => 'نام مالک کارت',
        'car_info' => 'اطلاعات خودرو',
        'vin' => 'شماره شاسی (VIN)',
        'system_name' => 'نام سامانه سوخت',
        'system_identifier' => 'شناسه سامانه سوخت',
        'plate_number' => 'شماره پلاک',
        'chip_info' => 'سایز چیپ',
    ];

    /**
     * Chip size is an order customization choice, not a printed specification,
     * so it is presented separately from anything that gets engraved on the
     * card body.
     */
    public const CHIP_SIZE_LABELS = [
        'small' => 'کوچک',
        'large' => 'بزرگ',
    ];

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
            'chip_info' => ['required', 'string', 'in:small,large'],
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

    public static function canonicalizeChipInfo(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $chip = strtolower(trim($value));

        return in_array($chip, self::VALID_CHIP_SIZES, true) ? $chip : '';
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
            'chip_info' => 10,
            default => 100,
        };
    }

    /**
     * Build the Fuel fulfillment read model directly from an OrderItem
     * customization snapshot.
     *
     * The snapshot is the only source: the customer's live design workspace is
     * never consulted, so a paid order always renders the data that was
     * actually purchased.
     *
     * This is deliberately a *read* projection and not a second call to
     * sanitize(). It filters to the allow-list (so a hand-edited row cannot
     * leak a Bank or foreign key) and canonicalizes VIN/plate/chip, but it
     * never truncates. Re-validating on read would silently shorten an
     * over-length historical value, and would drop a VIN that does not match
     * today's 17-character rule — both are exactly the silent data loss a
     * fulfillment screen must not cause. A value that no longer matches the
     * current rules is still what the customer bought, so it is still shown.
     *
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, array{label: string, value: string}> keyed by field
     */
    public static function fulfillmentFields(?array $snapshot): array
    {
        $snapshot = is_array($snapshot) ? $snapshot : [];

        $fields = [];

        foreach (self::FULFILLMENT_LABELS as $key => $label) {
            $raw = $snapshot[$key] ?? null;
            $value = is_string($raw) ? trim($raw) : '';

            if ($key === 'vin' && $value !== '') {
                $canonical = self::canonicalizeVin($value);

                // Fall back to the stored text when it is not canonicalizable
                // (e.g. a historical value that predates the current rule) so
                // the operator still sees what was actually purchased.
                $value = $canonical !== '' ? $canonical : $value;
            } elseif ($key === 'plate_number' && $value !== '') {
                $value = self::canonicalizePlate($value);
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
