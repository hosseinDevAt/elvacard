<?php

namespace Tests\Feature;

use App\Services\FuelCard\FuelCardCustomization;
use Tests\TestCase;

class FuelCardCustomizationTest extends TestCase
{
    public function test_sanitize_accepts_only_the_definitive_fuel_keys_with_valid_chip_size(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'owner_name' => 'علی رضایی',
                'car_info' => 'پژو ۲۰۶',
                'vin' => 'IRABCDEFGH1234567',
                'system_name' => 'سامانه هوشمند سوخت',
                'system_identifier' => 'REG-12345',
                'plate_number' => '۱۲ م ۳۴۵ ایران',
                'chip_info' => 'small',
            ],
        ]);

        $this->assertSame([
            'owner_name' => 'علی رضایی',
            'car_info' => 'پژو ۲۰۶',
            'vin' => 'IRABCDEFGH1234567',
            'system_name' => 'سامانه هوشمند سوخت',
            'system_identifier' => 'REG-12345',
            'plate_number' => '12 م 345 ایران',
            'chip_info' => 'small',
        ], $result);
    }

    public function test_chip_info_only_accepts_small_or_large(): void
    {
        $smallResult = FuelCardCustomization::sanitize([
            'customization_json' => ['chip_info' => ' SMALL '],
        ]);
        $this->assertSame(['chip_info' => 'small'], $smallResult);

        $largeResult = FuelCardCustomization::sanitize([
            'customization_json' => ['chip_info' => 'LARGE'],
        ]);
        $this->assertSame(['chip_info' => 'large'], $largeResult);

        $invalidResult = FuelCardCustomization::sanitize([
            'customization_json' => ['chip_info' => 'arbitrary text'],
        ]);
        $this->assertArrayNotHasKey('chip_info', $invalidResult);
    }

    public function test_vin_is_canonicalized_to_ascii_uppercase_without_whitespace(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => ['vin' => ' i r a b c d e f g h 1 2 3 4 5 6 7 '],
        ]);

        $this->assertSame(['vin' => 'IRABCDEFGH1234567'], $result);
    }

    public function test_persian_digit_vin_is_canonicalized(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => ['vin' => 'IRABCDEFGH۱۲۳۴۵۶۷'],
        ]);

        $this->assertSame(['vin' => 'IRABCDEFGH1234567'], $result);
    }

    public function test_invalid_vin_is_dropped(): void
    {
        $this->assertSame([], FuelCardCustomization::sanitize([
            'customization_json' => ['vin' => 'TOO-SHORT'],
        ]));

        $this->assertSame([], FuelCardCustomization::sanitize([
            'customization_json' => ['vin' => 'IRABCDEFGH12345678'],
        ]));
    }

    public function test_plate_number_collapses_whitespace_and_maps_digits(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => ['plate_number' => '  ۱۲   م   ۳۴۵   ایران  '],
        ]);

        $this->assertSame(['plate_number' => '12 م 345 ایران'], $result);
    }

    public function test_overlong_text_fields_are_truncated_to_their_limits(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'owner_name' => str_repeat('ا', 150),
                'system_identifier' => str_repeat('9', 100),
            ],
        ]);

        $this->assertSame(100, mb_strlen($result['owner_name']));
        $this->assertSame(64, mb_strlen($result['system_identifier']));
    }

    public function test_bank_and_unknown_keys_are_stripped_while_fuel_keys_survive(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'owner_name' => 'علی رضایی',
                'card_number' => '6274051234567890',
                'cvv2' => '808',
                'injected_field' => 'hacked',
                'product_id' => 5,
            ],
        ]);

        $this->assertSame(['owner_name' => 'علی رضایی'], $result);
    }

    public function test_blank_and_non_string_values_are_dropped(): void
    {
        $result = FuelCardCustomization::sanitize([
            'customization_json' => [
                'owner_name' => '   ',
                'car_info' => ['nested' => 'array'],
                'vin' => null,
                'plate_number' => '',
            ],
        ]);

        $this->assertSame([], $result);
    }
}
