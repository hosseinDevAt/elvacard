<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IbanRule implements ValidationRule
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $fail('شماره شبا وارد شده نامعتبر است.');

            return;
        }

        $canonical = self::canonicalize($value);

        if ($canonical === '') {
            return;
        }

        if (strlen($canonical) !== 26 || ! str_starts_with($canonical, 'IR') || ! ctype_digit(substr($canonical, 2))) {
            $fail('شماره شبا باید با IR آغاز شده و دارای ۲۴ رقم باشد.');

            return;
        }

        if (! self::passesMod97($canonical)) {
            $fail('شماره شبا وارد شده با الگوریتم کنترلی بانکی (Mod-97) همخوانی ندارد.');
        }
    }

    /**
     * Canonicalizes an Iranian IBAN input:
     * - Converts Persian and Arabic digits to ASCII digits
     * - Strips whitespace, hyphens, and slashes
     * - Converts to uppercase
     * - If exactly 24 digits are supplied without prefix, prepends 'IR'
     */
    public static function canonicalize(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = strtr(trim($value), self::DIGIT_MAP);
        $value = preg_replace('/[\s\-_]+/', '', $value) ?? '';
        $value = strtoupper($value);

        if (preg_match('/^[0-9]{24}$/', $value)) {
            $value = 'IR' . $value;
        }

        return $value;
    }

    /**
     * Validates an Iranian IBAN using the ISO 7064 Mod 97-10 algorithm.
     */
    public static function passesMod97(string $iban): bool
    {
        $iban = strtoupper(trim($iban));

        if (! preg_match('/^IR[0-9]{24}$/', $iban)) {
            return false;
        }

        // Rearrange IBAN: move first 4 characters (IR + 2 check digits) to end.
        // Convert 'IR' to digits: 'I' = 18, 'R' = 27 -> "1827"
        $bban = substr($iban, 4);
        $checkDigits = substr($iban, 2, 2);
        $rearranged = $bban . '1827' . $checkDigits;

        // Compute modulo 97 in safe numeric chunks
        $remainder = 0;
        $chunks = str_split($rearranged, 7);

        foreach ($chunks as $chunk) {
            $remainder = (int) (($remainder . $chunk) % 97);
        }

        return $remainder === 1;
    }

    /**
     * Computes the 2-digit checksum for a 22-digit Iranian BBAN to form a valid IBAN.
     * Useful for constructing valid test cases and verifying calculations.
     */
    public static function calculateCheckDigits(string $bban22): string
    {
        $bban = preg_replace('/\D/', '', $bban22);
        $bban = str_pad(substr($bban, 0, 22), 22, '0', STR_PAD_LEFT);

        $rearranged = $bban . '182700';
        $remainder = 0;

        foreach (str_split($rearranged, 7) as $chunk) {
            $remainder = (int) (($remainder . $chunk) % 97);
        }

        $check = 98 - $remainder;

        return sprintf('%02d', $check);
    }
}
