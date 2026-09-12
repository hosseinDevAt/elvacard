<?php

namespace App\Enums;

enum ProductTypeEnum: string
{
    case BANK = 'bank';
    case FUEL = 'fuel';
    case STANDARD = 'standard';

    public function label(): string
    {
        return match ($this) {
            self::BANK => 'Bank Card',
            self::FUEL => 'Fuel Card',
            self::STANDARD => 'Standard Product',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::BANK => 'کارت بانکی',
            self::FUEL => 'کارت سوخت',
            self::STANDARD => 'محصول استاندارد',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }
}
