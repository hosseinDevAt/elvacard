<?php

namespace App\Services\Customization;

use App\Rules\IbanRule;

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
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (str_contains($value, '•') || str_contains($value, '*')) {
            return trim($value);
        }

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
     * Masked representation of a card number showing only last 4 digits.
     * e.g. "•••• •••• •••• 7898"
     */
    public static function maskPan(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (str_contains($value, '•') || str_contains($value, '*')) {
            return trim($value);
        }

        $value = strtr(trim((string) $value), [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        $last4 = substr($digits, -4);

        return '•••• •••• •••• '.$last4;
    }

    /**
     * Presentation-only grouped Iranian IBAN (e.g. "IR12 0120 0000 0000 0012 3456 78").
     */
    public static function presentIban(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $canonical = IbanRule::canonicalize($value);

        if ($canonical === '') {
            return '';
        }

        // Group into 4-character chunks: IR12 0120 0000 0000 0012 3456 78
        return trim(preg_replace('/(.{4})(?=.)/', '$1 ', $canonical) ?? '');
    }

    /**
     * Masked representation of an Iranian IBAN showing prefix and last 4 digits.
     * e.g. "IR•• •••• •••• •••• •••• •••• 78" or "IR12 •••• •••• •••• •••• 5678"
     */
    public static function maskIban(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (str_contains($value, '•') || str_contains($value, '*')) {
            return trim($value);
        }

        $canonical = IbanRule::canonicalize($value);

        if (strlen($canonical) !== 26) {
            return 'IR••••••••••••••••••••••••';
        }

        $prefix = substr($canonical, 0, 4); // "IR12"
        $last4 = substr($canonical, -4);

        return $prefix . ' •••• •••• •••• •••• ' . $last4;
    }

    /**
     * Fixed layout slots for the back card. Presentation-only constants; the
     * layout is decided by the design, never by the user, so the snapshot
     * never stores user-controlled positions. Normalized (0.0 - 1.0) so the
     * fixed positions scale with the card on every viewport.
     *
     * Updated per Client Change Phase 03 layout requirements:
     * - Row 1 (top): IBAN / شبا (near stripe)
     * - Row 2 (center): Card Number
     * - Row 3 (security): CVV2 on LEFT, Expiry on RIGHT
     * - Row 4 (optional back text / middle): Back Text (when placement = 'back')
     * - Row 5 (bottom): Card Holder Name
     */
    public static function fixedSlots(): array
    {
        return [
            'iban' => ['x' => 0.08, 'y' => 0.26],
            'card_number' => ['x' => 0.08, 'y' => 0.45],
            'cvv2' => ['x' => 0.08, 'y' => 0.65],
            'expiry' => ['x' => 0.70, 'y' => 0.65],
            'back_text' => ['x' => 0.08, 'y' => 0.74],
            'card_holder_name' => ['x' => 0.08, 'y' => 0.83],
            'front_custom_text' => ['x' => 0.08, 'y' => 0.84],
        ];
    }

    /**
     * Computes the WCAG 2.1 relative luminance (0.0 to 1.0) for a given HEX color code.
     */
    public static function relativeLuminance(?string $hex): float
    {
        if ($hex === null || trim($hex) === '') {
            return 0.0;
        }

        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return 0.0;
        }

        $r = hexdec(substr($hex, 0, 2)) / 255.0;
        $g = hexdec(substr($hex, 2, 2)) / 255.0;
        $b = hexdec(substr($hex, 4, 2)) / 255.0;

        $rLin = ($r <= 0.04045) ? ($r / 12.92) : pow(($r + 0.055) / 1.055, 2.4);
        $gLin = ($g <= 0.04045) ? ($g / 12.92) : pow(($g + 0.055) / 1.055, 2.4);
        $bLin = ($b <= 0.04045) ? ($b / 12.92) : pow(($b + 0.055) / 1.055, 2.4);

        return (0.2126 * $rLin) + (0.7152 * $gLin) + (0.0722 * $bLin);
    }

    /**
     * Computes the WCAG contrast ratio between two luminance values (1.0 to 21.0).
     */
    public static function contrastRatio(float $lum1, float $lum2): float
    {
        $lighter = max($lum1, $lum2);
        $darker = min($lum1, $lum2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Determines whether the background requires 'dark' or 'light' text
     * based on which candidate provides the higher WCAG contrast ratio.
     *
     * Pure white luminance = 1.0
     * Pure black luminance = 0.0
     */
    public static function textColorMode(?string $hex): string
    {
        if ($hex === null || trim($hex) === '') {
            return 'light'; // Default dark card -> light text
        }

        $bgLum = self::relativeLuminance($hex);

        $contrastWithWhite = self::contrastRatio($bgLum, 1.0);
        $contrastWithBlack = self::contrastRatio($bgLum, 0.0);

        return $contrastWithBlack >= $contrastWithWhite ? 'dark' : 'light';
    }

    /**
     * Whether the background is considered light (requiring dark text).
     */
    public static function isLightBackground(?string $hex): bool
    {
        return self::textColorMode($hex) === 'dark';
    }

    /**
     * Primary text color CSS class for card specifications on the given background.
     */
    public static function backTextColorClass(?string $hex): string
    {
        return self::isLightBackground($hex) ? 'text-slate-950' : 'text-white';
    }

    /**
     * Secondary/muted text color CSS class for card labels (e.g. CVV2 label, expiry label).
     */
    public static function backSubtextColorClass(?string $hex): string
    {
        return self::isLightBackground($hex) ? 'text-slate-800/85' : 'text-slate-200/80';
    }

    /**
     * Card holder name signature box styling based on card background contrast.
     */
    public static function signatureBoxClass(?string $hex): string
    {
        return self::isLightBackground($hex)
            ? 'bg-black/10 text-slate-950 border border-black/15 shadow-inner'
            : 'bg-white/90 text-gray-900 border border-white/20 shadow-inner';
    }
}
