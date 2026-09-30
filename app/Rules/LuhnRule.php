<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class LuhnRule implements ValidationRule
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
            $fail('شماره کارت وارد شده نامعتبر است.');

            return;
        }

        $canonical = strtr(trim($value), self::DIGIT_MAP);
        $digits = preg_replace('/[\s\-]+/', '', $canonical) ?? '';

        if (strlen($digits) !== 16 || ! ctype_digit($digits)) {
            $fail('شماره کارت باید دقیقاً ۱۶ رقمی باشد.');

            return;
        }

        if (! self::passesLuhn($digits)) {
            $fail('شماره کارت وارد شده با الگوریتم بانکی (Luhn) همخوانی ندارد.');
        }
    }

    public static function passesLuhn(string $number): bool
    {
        $digits = preg_replace('/\D/', '', $number);
        $length = strlen($digits);

        if ($length < 13 || $length > 19) {
            return false;
        }

        $sum = 0;
        $flag = false;

        for ($i = $length - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];

            if ($flag) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $flag = ! $flag;
        }

        return $sum % 10 === 0;
    }
}
