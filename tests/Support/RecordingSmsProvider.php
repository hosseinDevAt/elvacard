<?php

namespace Tests\Support;

use App\Contracts\Sms\SmsProvider;

/**
 * Test-only fake that records every OTP it "sends" so tests can inspect the
 * delivered code. It is never registered in production configuration, the
 * resolver, or any user-facing UI (see SmsProviderAbstractionTest).
 */
final class RecordingSmsProvider implements SmsProvider
{
    public function __construct(private readonly string $providerName = 'recording') {}

    /** @var array<int, array{phone: string, code: string}> */
    public array $sent = [];

    public function name(): string
    {
        return $this->providerName;
    }

    public function sendOtp(string $phone, string $code): void
    {
        $this->sent[] = ['phone' => $phone, 'code' => $code];
    }

    public function lastCode(): ?string
    {
        return $this->sent[array_key_last($this->sent)]['code'] ?? null;
    }

    public function lastPhone(): ?string
    {
        return $this->sent[array_key_last($this->sent)]['phone'] ?? null;
    }
}