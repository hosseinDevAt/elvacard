<?php

namespace App\Services\Customization;

class CardPresenter
{
    /**
     * Presentation-only grouped card number (e.g. "6274 0512 3456 7890").
     * Never persisted: the snapshot always keeps the canonical 16 ASCII digits.
     *
     * Handles Persian/Arabic digit glyphs on display but does NOT mutate the
     * database value — canonical storage is always plain 16 ASCII digits.
     */
    public static function presentCardNumber(?string $value): string
    {
        $value = strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D/', '', $value) ?? '';

        return trim(preg_replace('/(.{4})(?=.)/', '$1 ', $digits) ?? '');
    }

    /**
     * Fixed layout slots for the back card. Presentation-only constants; the
     * layout is decided by the design, never by the user, so the snapshot
     * never stores user-controlled positions. Normalized (0.0 - 1.0) so the
     * fixed positions scale with the card on every viewport.
     */
    public static function fixedSlots(): array
    {
        return [
            'card_number' => ['x' => 0.08, 'y' => 0.42],
            'card_holder_name' => ['x' => 0.08, 'y' => 0.78],
            'back_text' => ['x' => 0.08, 'y' => 0.62],
            'cvv2' => ['x' => 0.72, 'y' => 0.78],
            'expiry' => ['x' => 0.48, 'y' => 0.78],
        ];
    }
}
