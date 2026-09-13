<?php

namespace App\Enums;

enum CustomizationWorkflowEnum: string
{
    case BANK_CARD = 'bank_card';
    case FUEL_CARD = 'fuel_card';

    public function label(): string
    {
        return match ($this) {
            self::BANK_CARD => 'Bank Card',
            self::FUEL_CARD => 'Fuel Card',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::BANK_CARD => 'کارت بانکی',
            self::FUEL_CARD => 'کارت سوخت',
        };
    }
}
