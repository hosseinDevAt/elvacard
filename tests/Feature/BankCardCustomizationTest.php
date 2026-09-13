<?php

namespace Tests\Feature;

use App\Services\BankCard\BankCardCustomization;
use Tests\TestCase;

class BankCardCustomizationTest extends TestCase
{
    public function test_canonicalize_returns_empty_for_null_and_blank(): void
    {
        $this->assertSame('', BankCardCustomization::canonicalizeCardNumber(null));
        $this->assertSame('', BankCardCustomization::canonicalizeCardNumber(''));
        $this->assertSame('', BankCardCustomization::canonicalizeCardNumber('   '));
    }

    public function test_canonicalize_removes_separators(): void
    {
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber('6274 0512 3456 7890'));
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber('6274-0512-3456-7890'));
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber(' 6274 0512-3456 7890 '));
    }

    public function test_canonicalize_normalizes_persian_and_arabic_digits(): void
    {
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber('۶۲۷۴۰۵۱۲۳۴۵۶۷۸۹۰'));
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber('٦٢٧٤٠٥١٢٣٤٥٦٧٨٩٠'));
    }

    public function test_canonicalize_keeps_ascii_digits_as_is(): void
    {
        $this->assertSame('6274051234567890', BankCardCustomization::canonicalizeCardNumber('6274051234567890'));
    }

    public function test_sanitize_drops_unknown_and_legacy_keys(): void
    {
        $result = BankCardCustomization::sanitize([
            'customization_json' => [
                'card_number' => '6274051234567890',
                'qr_code_enabled' => true,
                'positions' => ['card_number' => ['x' => 0.5, 'y' => 0.5]],
                'random_injected_field' => 'hacked',
            ],
        ]);

        $this->assertSame(['card_number' => '6274051234567890'], $result);
    }

    public function test_sanitize_keeps_valid_card_number_cvs_and_expiry(): void
    {
        $result = BankCardCustomization::sanitize([
            'customization_json' => [
                'card_number' => '۶۲۷۴-۰۵۱۲ ۳۴۵۶ ۷۸۹۰',
                'card_holder_name' => ' ALI REZA ',
                'back_text' => ' TEXT ',
                'security_cvv_enabled' => true,
                'cvv2' => '808',
                'security_expiry_enabled' => true,
                'expiry_month' => '05',
                'expiry_year' => (string) ((int) date('y') + 2),
            ],
        ]);

        $this->assertSame('6274051234567890', $result['card_number']);
        $this->assertSame('ALI REZA', $result['card_holder_name']);
        $this->assertSame('TEXT', $result['back_text']);
        $this->assertSame('808', $result['cvv2']);
        $this->assertSame('05', $result['expiry_month']);
        $this->assertSame((string) ((int) date('y') + 2), $result['expiry_year']);
    }

    public function test_sanitize_drops_invalid_card_number(): void
    {
        foreach (['1234-5678', '123456789012345', '62740000000000001', '6274ABCD5678EFGH'] as $invalid) {
            $result = BankCardCustomization::sanitize(['customization_json' => ['card_number' => $invalid]]);
            $this->assertArrayNotHasKey('card_number', $result);
        }
    }

    public function test_sanitize_accepts_three_or_four_digit_cvv(): void
    {
        foreach (['123', '8080'] as $cvv) {
            $result = BankCardCustomization::sanitize([
                'customization_json' => ['security_cvv_enabled' => true, 'cvv2' => $cvv],
            ]);
            $this->assertSame($cvv, $result['cvv2']);
        }
    }

    public function test_sanitize_drops_invalid_cvv(): void
    {
        foreach (['12', '12345', '12XY'] as $cvv) {
            $result = BankCardCustomization::sanitize([
                'customization_json' => ['security_cvv_enabled' => true, 'cvv2' => $cvv],
            ]);
            $this->assertArrayNotHasKey('cvv2', $result);
        }
    }

    public function test_sanitize_drops_cvv_when_toggle_disabled(): void
    {
        $result = BankCardCustomization::sanitize([
            'customization_json' => ['security_cvv_enabled' => false, 'cvv2' => '808'],
        ]);

        $this->assertArrayNotHasKey('cvv2', $result);
        $this->assertFalse($result['security_cvv_enabled']);
    }

    public function test_sanitize_keeps_only_valid_expiry_month_and_year(): void
    {
        $currentShort = (int) date('y');

        $valid = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '08',
                'expiry_year' => (string) ($currentShort + 1),
            ],
        ]);
        $this->assertSame('08', $valid['expiry_month']);
        $this->assertSame((string) ($currentShort + 1), $valid['expiry_year']);

        $invalid = BankCardCustomization::sanitize([
            'customization_json' => [
                'security_expiry_enabled' => true,
                'expiry_month' => '13',
                'expiry_year' => (string) ($currentShort - 5),
            ],
        ]);
        $this->assertArrayNotHasKey('expiry_month', $invalid);
        $this->assertArrayNotHasKey('expiry_year', $invalid);
    }

    public function test_sanitize_truncates_holder_and_back_text(): void
    {
        $result = BankCardCustomization::sanitize([
            'customization_json' => [
                'card_holder_name' => str_repeat('A', 150),
                'back_text' => str_repeat('B', 300),
            ],
        ]);

        $this->assertSame(100, mb_strlen($result['card_holder_name']));
        $this->assertSame(255, mb_strlen($result['back_text']));
    }

    public function test_rules_for_without_toggles_contains_no_conditional_fields(): void
    {
        $rules = BankCardCustomization::rulesFor(false, false);

        $this->assertSame(['nullable', 'string', 'digits:16'], $rules['card_number']);
        $this->assertSame(['nullable', 'string', 'max:100'], $rules['card_holder_name']);
        $this->assertSame(['nullable', 'string', 'max:255'], $rules['back_text']);
        $this->assertSame(['boolean'], $rules['security_cvv_enabled']);
        $this->assertSame(['boolean'], $rules['security_expiry_enabled']);
        $this->assertArrayNotHasKey('cvv2', $rules);
        $this->assertArrayNotHasKey('expiry_month', $rules);
        $this->assertArrayNotHasKey('expiry_year', $rules);
    }

    public function test_rules_for_adds_cvv_and_expiry_when_enabled(): void
    {
        $rules = BankCardCustomization::rulesFor(true, true);

        $this->assertSame(['nullable', 'string', 'digits_between:3,4'], $rules['cvv2']);
        $this->assertSame(['nullable', 'string', 'regex:/^(0[1-9]|1[0-2])$/'], $rules['expiry_month']);

        $currentShort = (int) date('y');
        $expectedYearRange = "between:{$currentShort},".($currentShort + 10);
        $this->assertSame(['nullable', 'string', 'integer', 'digits:2', $expectedYearRange], $rules['expiry_year']);
    }

    public function test_expiry_year_range_spans_current_to_plus_ten(): void
    {
        $currentShort = (int) date('y');

        $this->assertSame([$currentShort, $currentShort + 10], BankCardCustomization::expiryYearRange());
    }

    public function test_messages_cover_all_card_fields(): void
    {
        $messages = BankCardCustomization::messages();

        $this->assertArrayHasKey('card_number.digits', $messages);
        $this->assertArrayHasKey('cvv2.digits_between', $messages);
        $this->assertArrayHasKey('expiry_month.regex', $messages);
        $this->assertArrayHasKey('expiry_year.digits', $messages);
        $this->assertArrayHasKey('expiry_year.between', $messages);
    }
}
