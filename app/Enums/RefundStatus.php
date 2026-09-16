<?php

namespace App\Enums;

enum RefundStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REVIEW = 'review';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REVIEW => 'Under Review',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::COMPLETED => 'تکمیل شده',
            self::FAILED => 'ناموفق',
            self::REVIEW => 'در حال بررسی',
            self::CANCELLED => 'لغو شده',
        };
    }
}
