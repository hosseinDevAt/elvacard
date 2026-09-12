<?php

namespace App\Sms;

use App\Contracts\Sms\SmsProvider;
use App\Exceptions\SmsSendingFailedException;
use Illuminate\Support\Facades\Log;

/**
 * Development / testing driver.
 *
 * Writes the OTP to the dedicated "sms" log channel. It hard-refuses to send
 * a fake OTP when the application runs in production, so a misconfigured
 * production environment can never silently fake an SMS.
 */
final class LogSmsProvider implements SmsProvider
{
    public function __construct(
        private readonly string $providerName = 'log',
    ) {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function sendOtp(string $phone, string $code): void
    {
        if (app()->isProduction()) {
            throw new SmsSendingFailedException('SMS sending is not configured yet.');
        }

        Log::channel('sms')->info('OTP sent', [
            'phone' => $phone,
            'code' => $code,
            'env' => app()->environment(),
        ]);
    }
}