<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PRODUCTION = 'production';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::PRODUCTION => 'Production',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار پرداخت',
            self::CONFIRMED => 'تایید شده',
            self::PRODUCTION, self::PROCESSING => 'در حال تولید',
            self::COMPLETED => 'تکمیل شده',
            self::CANCELLED => 'لغو شده',
        };
    }
}
