<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | Canonical name of the SMS provider used to deliver OTP messages.
    |
    | No real SMS provider is registered yet. The only drivers present are:
    |
    |   - log:      Writes the OTP to the dedicated "sms" log channel. Intended
    |               for local development and automated tests only. It
    |               hard-refuses to run in a production environment.
    |   - disabled: Placeholder that always fails with a controlled,
    |               user-friendly error so the system never silently fakes an
    |               SMS in production.
    |
    | A real provider (e.g. Kavenegar) is added in a later phase through this
    | same allowlist — never through user input.
    |
    */

    'default' => env('SMS_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    | Single source of truth for OTP behaviour so values are never hardcoded
    | across the application.
    |
    */

    'otp' => [
        // Numeric code length.
        'length' => (int) env('SMS_OTP_LENGTH', 6),

        // Seconds a code stays valid after issuance.
        'expires_in' => (int) env('SMS_OTP_EXPIRES_IN', 120),

        // Failed verification attempts allowed before the code is invalidated.
        'max_attempts' => (int) env('SMS_OTP_MAX_ATTEMPTS', 5),

        // Seconds a user must wait before requesting / resending an OTP.
        'resend_cooldown' => (int) env('SMS_OTP_RESEND_COOLDOWN', 60),

        // Number of verification attempts allowed per minute per phone.
        'verify_rate_limit' => (int) env('SMS_OTP_VERIFY_RATE_LIMIT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Allowlist
    |--------------------------------------------------------------------------
    |
    | Server-side allowlist mapping canonical provider names to implementations
    | bound by the App\Contracts\Sms\SmsProvider contract. Providers are
    | selected exclusively through this list — never through user input.
    | Test doubles must never be registered here.
    |
    */

    'providers' => [
        'log' => App\Sms\LogSmsProvider::class,
        'disabled' => App\Sms\DisabledSmsProvider::class,
    ],
];