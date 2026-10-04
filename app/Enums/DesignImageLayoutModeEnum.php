<?php

namespace App\Enums;

enum DesignImageLayoutModeEnum: string
{
    case CENTERED = 'centered';
    case FULL_CARD = 'full_card';

    public function label(): string
    {
        return match ($this) {
            self::CENTERED => 'Centered',
            self::FULL_CARD => 'Full Card',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::CENTERED => 'طرح معمولی (مرکزی)',
            self::FULL_CARD => 'طرح تمام‌کارت (پوشش کامل)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
