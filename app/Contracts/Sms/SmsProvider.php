<?php

namespace App\Contracts\Sms;

use App\Exceptions\SmsSendingFailedException;

interface SmsProvider
{
    /**
     * Canonical provider name (must match the allowlist key).
     */
    public function name(): string;

    /**
     * Deliver an OTP code to a phone number.
     *
     * Implementations must throw SmsSendingFailedException when delivery is
     * impossible — they must never silently pretend the message was sent.
     *
     * @throws SmsSendingFailedException
     */
    public function sendOtp(string $phone, string $code): void;
}
