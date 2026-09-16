<?php

namespace App\Enums;

enum RefundLookupStatus: string
{
    case SUCCESS = 'success';
    case CONFIRMED_FAILURE = 'confirmed_failure';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::SUCCESS => 'Success',
            self::CONFIRMED_FAILURE => 'Confirmed Failure',
            self::UNKNOWN => 'Unknown',
        };
    }
}
