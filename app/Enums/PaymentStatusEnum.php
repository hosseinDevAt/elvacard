<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case UNPAID = 'unpaid';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => 'Unpaid',
            self::PAID => 'Paid',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::UNPAID => 'پرداخت نشده',
            self::PAID => 'پرداخت شده',
            self::FAILED => 'ناموفق',
            self::REFUNDED => 'بازگشت داده شده',
        };
    }
}
