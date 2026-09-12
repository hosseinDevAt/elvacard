<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MANUAL_TRANSFER = 'manual_transfer';
    case GATEWAY = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL_TRANSFER => 'Manual Bank Transfer',
            self::GATEWAY => 'Online Gateway',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::MANUAL_TRANSFER => 'کارت به کارت',
            self::GATEWAY => 'درگاه آنلاین',
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