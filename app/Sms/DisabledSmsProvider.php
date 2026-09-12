<?php

namespace App\Sms;

use App\Contracts\Sms\SmsProvider;
use App\Exceptions\SmsSendingFailedException;

/**
 * Placeholder driver for environments without a configured SMS provider.
 *
 * Always fails with a controlled, user-friendly error so the system never
 * silently fakes an SMS.
 */
final class DisabledSmsProvider implements SmsProvider
{
    public function __construct(
        private readonly string $providerName = 'disabled',
    ) {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function sendOtp(string $phone, string $code): void
    {
        throw new SmsSendingFailedException('Sending SMS messages is not configured yet. Please try again later.');
    }
}