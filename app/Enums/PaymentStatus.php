<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PENDING_REVIEW = 'pending_review';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PENDING_REVIEW => 'Pending Review',
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function faLabel(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار',
            self::PENDING_REVIEW => 'در انتظار بررسی',
            self::SUCCESS => 'پرداخت موفق',
            self::FAILED => 'رد شده',
            self::CANCELLED => 'لغو شده',
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