<?php

namespace App\Services\BankCard;

class BankCardCustomization
{
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function canonicalizeCardNumber(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = strtr(trim($value), self::DIGIT_MAP);

        return preg_replace('/[\s\-]+/', '', $value) ?? '';
    }

    public static function rulesFor(bool $cvvEnabled, bool $expiryEnabled): array
    {
        $rules = [
            'card_number' => ['nullable', 'string', 'digits:16'],
            'card_holder_name' => ['nullable', 'string', 'max:100'],
            'back_text' => ['nullable', 'string', 'max:255'],
            'security_cvv_enabled' => ['boolean'],
            'security_expiry_enabled' => ['boolean'],
        ];

        if ($cvvEnabled) {
            $rules['cvv2'] = ['nullable', 'string', 'digits_between:3,4'];
        }

        if ($expiryEnabled) {
            [$from, $to] = self::expiryYearRange();
            $yearRange = 'between:'.$from.','.$to;
            $rules['expiry_month'] = ['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])$/'];
            $rules['expiry_year'] = ['nullable', 'string', 'integer', 'digits:2', $yearRange];
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'card_number.digits' => 'شماره کارت باید دقیقاً ۱۶ رقمی باشد.',
            'cvv2.digits_between' => 'CVV2 باید ۳ تا ۴ رقم باشد.',
            'expiry_month.regex' => 'ماه انقضا باید بین ۰۱ تا ۱۲ باشد.',
            'expiry_year.digits' => 'سال انقضا باید دو رقم باشد.',
            'expiry_year.between' => 'سال انقضا باید در بازه معتبر باشد.',
        ];
    }

    public static function expiryYearRange(): array
    {
        $currentYearShort = (int) date('y');

        return [$currentYearShort, $currentYearShort + 10];
    }

    public static function sanitize(array $payload): array
    {
        $rawCustomization = is_array($payload['customization_json'] ?? null)
            ? $payload['customization_json']
            : [];

        $sanitizedCustomization = [];

        if (! empty($rawCustomization['card_number']) && is_string($rawCustomization['card_number'])) {
            $cardNumber = self::canonicalizeCardNumber($rawCustomization['card_number']);
            if (preg_match('/^[0-9]{16}$/', $cardNumber) === 1) {
                $sanitizedCustomization['card_number'] = $cardNumber;
            }
        }

        if (! empty($rawCustomization['card_holder_name']) && is_string($rawCustomization['card_holder_name'])) {
            $name = mb_substr(trim($rawCustomization['card_holder_name']), 0, 100);
            if ($name !== '') {
                $sanitizedCustomization['card_holder_name'] = $name;
            }
        }

        if (! empty($rawCustomization['back_text']) && is_string($rawCustomization['back_text'])) {
            $text = mb_substr(trim($rawCustomization['back_text']), 0, 255);
            if ($text !== '') {
                $sanitizedCustomization['back_text'] = $text;
            }
        }

        if (isset($rawCustomization['security_cvv_enabled'])) {
            $sanitizedCustomization['security_cvv_enabled'] = (bool) $rawCustomization['security_cvv_enabled'];
            if ($sanitizedCustomization['security_cvv_enabled'] && ! empty($rawCustomization['cvv2']) && is_string($rawCustomization['cvv2'])) {
                $cvv = self::canonicalizeCardNumber($rawCustomization['cvv2']);
                if (preg_match('/^[0-9]{3,4}$/', $cvv) === 1) {
                    $sanitizedCustomization['cvv2'] = $cvv;
                }
            }
        }

        if (isset($rawCustomization['security_expiry_enabled'])) {
            $sanitizedCustomization['security_expiry_enabled'] = (bool) $rawCustomization['security_expiry_enabled'];
            if ($sanitizedCustomization['security_expiry_enabled']) {
                if (! empty($rawCustomization['expiry_month']) && is_string($rawCustomization['expiry_month'])) {
                    $month = substr(trim($rawCustomization['expiry_month']), 0, 2);
                    if (preg_match('/^(0[1-9]|1[0-2])$/', $month) === 1) {
                        $sanitizedCustomization['expiry_month'] = $month;
                    }
                }
                if (! empty($rawCustomization['expiry_year']) && is_string($rawCustomization['expiry_year'])) {
                    $year = substr(trim($rawCustomization['expiry_year']), 0, 2);
                    $currentShort = (int) date('y');
                    if (preg_match('/^[0-9]{2}$/', $year) === 1 && (int) $year >= $currentShort && (int) $year <= $currentShort + 10) {
                        $sanitizedCustomization['expiry_year'] = $year;
                    }
                }
            }
        }

        return $sanitizedCustomization;
    }
}
