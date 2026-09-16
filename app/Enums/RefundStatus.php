<?php

namespace App\Enums;

enum RefundStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::COMPLETED => 'تکمیل شده',
            self::FAILED => 'ناموفق',
            self::CANCELLED => 'لغو شده',
        };
    }
}
