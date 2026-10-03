<?php

namespace Tests\Unit;

use Tests\TestCase;

class NumeralHelperTest extends TestCase
{
    public function test_fa_digits_converts_ascii_digits(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', fa_digits('0123456789'));
        $this->assertSame('۱۴۰۵/۰۶/۲۵', fa_digits('1405/06/25'));
        $this->assertSame('تعداد: ۵ عدد', fa_digits('تعداد: 5 عدد'));
    }

    public function test_fa_digits_is_idempotent(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', fa_digits('۰۱۲۳۴۵۶۷۸۹'));
        $this->assertSame('۱۴۰۵/۰۶/۲۵', fa_digits('۱۴۰۵/۰۶/۲۵'));
    }

    public function test_fa_digits_handles_empty_and_null(): void
    {
        $this->assertSame('', fa_digits(null));
        $this->assertSame('', fa_digits(''));
    }

    public function test_fa_number_formats_integers_and_floats(): void
    {
        $this->assertSame('۵', fa_number(5));
        $this->assertSame('۰', fa_number(0));
        $this->assertSame('۱۲۵۰۰۰۰', fa_number(1250000));
        $this->assertSame('۱,۲۵۰,۰۰۰', fa_number(1250000, true));
        $this->assertSame('۱,۲۵۰,۰۰۰', fa_number('1,250,000'));
    }

    public function test_fa_number_is_idempotent(): void
    {
        $this->assertSame('۱,۲۵۰,۰۰۰', fa_number('۱,۲۵۰,۰۰۰'));
        $this->assertSame('۴۲', fa_number('۴۲'));
    }

    public function test_format_price_formats_currency_in_tomans(): void
    {
        $this->assertSame('۱,۲۵۰,۰۰۰', format_price(1250000));
        $this->assertSame('۱,۲۵۰,۰۰۰ تومان', format_price(1250000, true));
        $this->assertSame('۰ تومان', format_price(0, true));
        $this->assertSame('۰ تومان', format_price(null, true));
    }
}
