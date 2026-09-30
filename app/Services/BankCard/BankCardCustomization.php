<?php

namespace App\Services\BankCard;

use App\Rules\LuhnRule;
use App\Services\Customization\CardPresenter;
use App\Support\Dates\DateService;
use Illuminate\Support\Facades\Crypt;

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

    public static function validateLuhn(string $number): bool
    {
        return LuhnRule::passesLuhn($number);
    }

    public static function rulesFor(bool $cvvEnabled, bool $expiryEnabled): array
    {
        $rules = [
            'card_number' => ['nullable', 'string', 'digits:16', new LuhnRule],
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
            $rules['expiry_year'] = ['nullable', 'string', 'integer', 'digits:4', $yearRange];
        }

        return $rules;
    }

    public static function messages(): array
    {
        return [
            'card_number.digits' => 'شماره کارت باید دقیقاً ۱۶ رقمی باشد.',
            'cvv2.digits_between' => 'CVV2 باید ۳ تا ۴ رقم باشد.',
            'expiry_month.regex' => 'ماه انقضا باید بین ۰۱ تا ۱۲ باشد.',
            'expiry_year.digits' => 'سال انقضا باید چهار رقم باشد.',
            'expiry_year.between' => 'سال انقضا باید در بازه معتبر باشد.',
        ];
    }

    public static function expiryYearRange(): array
    {
        return app(DateService::class)->jalaliYearRange();
    }

    public static function sanitize(array $payload): array
    {
        $rawCustomization = is_array($payload['customization_json'] ?? null)
            ? $payload['customization_json']
            : [];

        $sanitizedCustomization = [];

        // Encrypt and protect PAN at rest using Laravel Crypt + last4 mask
        if (! empty($rawCustomization['card_number']) && is_string($rawCustomization['card_number'])) {
            $cardNumber = self::canonicalizeCardNumber($rawCustomization['card_number']);
            if (preg_match('/^[0-9]{16}$/', $cardNumber) === 1 && LuhnRule::passesLuhn($cardNumber)) {
                $sanitizedCustomization['pan_encrypted'] = Crypt::encryptString($cardNumber);
                $sanitizedCustomization['pan_last4'] = substr($cardNumber, -4);
                $sanitizedCustomization['card_number_masked'] = CardPresenter::maskPan($cardNumber);
                $sanitizedCustomization['pan_hash'] = hash_hmac('sha256', $cardNumber, (string) config('app.key'));
            }
        } elseif (! empty($rawCustomization['pan_encrypted']) && is_string($rawCustomization['pan_encrypted'])) {
            $sanitizedCustomization['pan_encrypted'] = $rawCustomization['pan_encrypted'];
            if (! empty($rawCustomization['pan_last4'])) {
                $sanitizedCustomization['pan_last4'] = (string) $rawCustomization['pan_last4'];
            }
            if (! empty($rawCustomization['card_number_masked'])) {
                $sanitizedCustomization['card_number_masked'] = (string) $rawCustomization['card_number_masked'];
            }
            if (! empty($rawCustomization['pan_hash'])) {
                $sanitizedCustomization['pan_hash'] = (string) $rawCustomization['pan_hash'];
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

        // CVV2 is strictly used for live validation and discarded immediately - never persisted
        if (isset($rawCustomization['security_cvv_enabled'])) {
            $sanitizedCustomization['security_cvv_enabled'] = (bool) $rawCustomization['security_cvv_enabled'];
        }

        if (isset($rawCustomization['security_expiry_enabled'])) {
            $sanitizedCustomization['security_expiry_enabled'] = (bool) $rawCustomization['security_expiry_enabled'];
            if ($sanitizedCustomization['security_expiry_enabled']) {
                $month = null;
                if (! empty($rawCustomization['expiry_month']) && is_string($rawCustomization['expiry_month'])) {
                    $month = mb_substr(trim($rawCustomization['expiry_month']), 0, 2);
                    if (preg_match('/^(0[1-9]|1[0-2])$/', $month) === 1) {
                        $sanitizedCustomization['expiry_month'] = $month;
                    }
                }

                if (! empty($rawCustomization['expiry_year']) && is_string($rawCustomization['expiry_year'])) {
                    $dates = app(DateService::class);
                    $year = $dates->ascii(trim($rawCustomization['expiry_year']));

                    if (preg_match('/^\d{4}$/', $year) === 1) {
                        $converted = $dates->jalaliExpiryToGregorian($sanitizedCustomization['expiry_month'] ?? null, $year);
                        if ($converted !== null) {
                            $sanitizedCustomization['expiry_month'] = $converted['expiry_month'];
                            $sanitizedCustomization['expiry_year'] = $converted['expiry_year'];
                        }
                    } elseif (preg_match('/^\d{2}$/', $year) === 1) {
                        $currentShort = (int) date('y');
                        if ((int) $year >= $currentShort && (int) $year <= $currentShort + 10) {
                            $sanitizedCustomization['expiry_year'] = $year;
                        }
                    }
                }
            }
        }

        return $sanitizedCustomization;
    }
}
