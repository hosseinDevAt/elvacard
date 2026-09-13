<?php

namespace App\Services\FuelCard;

class FuelCardCustomization
{
    // No Fuel-specific customer fields are defined yet, so the allow-list is
    // empty by design: every submitted key (including any Bank-card field and
    // product/color/design metadata) is stripped. Definitive Fuel fields will
    // extend this list together with their canonicalization in sanitize().
    private const ALLOWED_KEYS = [];

    public static function rulesFor(): array
    {
        return [];
    }

    public static function messages(): array
    {
        return [];
    }

    public static function sanitize(array $payload): array
    {
        $rawCustomization = is_array($payload['customization_json'] ?? null)
            ? $payload['customization_json']
            : [];

        $sanitized = [];

        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $rawCustomization)) {
                $sanitized[$key] = $rawCustomization[$key];
            }
        }

        return $sanitized;
    }
}
