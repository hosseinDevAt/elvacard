<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::CONFIRMED => 'تایید شده',
            self::PROCESSING => 'در حال پردازش',
            self::COMPLETED => 'تکمیل شده',
            self::CANCELLED => 'لغو شده',
        };
    }
}
