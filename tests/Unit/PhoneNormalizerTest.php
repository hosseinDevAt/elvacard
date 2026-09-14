<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneNormalizerTest extends TestCase
{
    // ───────── normalize_phone ─────────

    #[DataProvider('canonicalPhoneProvider')]
    public function test_normalize_phone_returns_canonical_unchanged(string $input, string $expected): void
    {
        $this->assertSame($expected, normalize_phone($input));
    }

    /** @return array<string, array{string, string}> */
    public static function canonicalPhoneProvider(): array
    {
        return [
            'plain canonical' => ['09123456789', '09123456789'],
            'canonical with spaces' => ['091 234 56789', '09123456789'],
            'canonical with dash' => ['0912-345-6789', '09123456789'],
            'canonical with parens' => ['(091)23456789', '09123456789'],
        ];
    }

    #[DataProvider('prefixPhoneProvider')]
    public function test_normalize_phone_converts_international_prefixes(string $input, string $expected): void
    {
        $this->assertSame($expected, normalize_phone($input));
    }

    /** @return array<string, array{string, string}> */
    public static function prefixPhoneProvider(): array
    {
        return [
            '+98 prefix' => ['+989123456789', '09123456789'],
            '+98 with spaces' => ['+98 912 345 6789', '09123456789'],
            '+98 with dashes' => ['+98-912-345-6789', '09123456789'],
            '0098 prefix' => ['00989123456789', '09123456789'],
            '0098 with spaces' => ['0098 912 345 6789', '09123456789'],
            'persian digits with +98' => ['+۹۸۹۱۲۳۴۵۶۷۸۹', '09123456789'],
            'arabic digits with 0098' => ['٠٠٩٨٩١٢٣٤٥٦٧٨٩', '09123456789'],
        ];
    }

    #[DataProvider('persianArabicDigitProvider')]
    public function test_normalize_phone_converts_non_latin_digits(string $input, string $expected): void
    {
        $this->assertSame($expected, normalize_phone($input));
    }

    /** @return array<string, array{string, string}> */
    public static function persianArabicDigitProvider(): array
    {
        return [
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹', '09123456789'],
            'arabic digits' => ['٠٩١٢٣٤٥٦٧٨٩', '09123456789'],
            'mixed' => ['۰۹۱۲-۳۴۵-۶۷۸۹', '09123456789'],
        ];
    }

    // ───────── is_valid_iranian_mobile ─────────

    #[DataProvider('validMobileProvider')]
    public function test_is_valid_iranian_mobile_accepts_valid_input(?string $input): void
    {
        $this->assertTrue(is_valid_iranian_mobile($input));
    }

    /** @return array<string, array{?string}> */
    public static function validMobileProvider(): array
    {
        return [
            'canonical 09...' => ['09123456789'],
            '+98 prefix' => ['+989123456789'],
            '0098 prefix' => ['00989123456789'],
            'with spaces' => ['091 234 56789'],
            'with dashes' => ['+98-912-345-6789'],
            'persian digits' => ['۰۹۱۲۳۴۵۶۷۸۹'],
            'persian digits with dash' => ['۰۹۱۲-۳۴۵-۶۷۸۹'],
            'arabic digits' => ['٠٩١٢٣٤٥٦٧٨٩'],
        ];
    }

    #[DataProvider('invalidMobileProvider')]
    public function test_is_valid_iranian_mobile_rejects_invalid_input(?string $input): void
    {
        $this->assertFalse(is_valid_iranian_mobile($input));
    }

    /** @return array<string, array{?string}> */
    public static function invalidMobileProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
            'whitespace only' => ['   '],
            'too short' => ['09123'],
            'too long' => ['091234567890'],
            'does not start with 09' => ['08123456789'],
            'starts with 98 no + 00' => ['98123456789'],
            '+98 too short' => ['+98912345'],
            '+98 only prefix' => ['+98'],
            '0098 too short' => ['00989123456'],
            'letters' => ['abcdefghijk'],
            'mixed valid invalid' => ['09123abc789'],
        ];
    }
}
