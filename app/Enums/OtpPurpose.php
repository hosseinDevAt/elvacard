<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case REGISTRATION = 'registration';
    case PASSWORD_RESET = 'password_reset';

    public function faLabel(): string
    {
        return match ($this) {
            self::REGISTRATION => 'ثبت‌نام',
            self::PASSWORD_RESET => 'بازیابی رمز عبور',
        };
    }
}