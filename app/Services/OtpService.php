<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Exceptions\SmsSendingFailedException;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

final class OtpService
{
    public function __construct(private readonly SmsManager $sms) {}

    /**
     * Issue a fresh OTP for the given phone and purpose.
     *
     * Delivery happens before persistence: if the current provider cannot send
     * (e.g. disabled driver in production), SmsSendingFailedException bubbles up
     * and no unusable code is stored.
     *
     * @throws SmsSendingFailedException
     * @throws \App\Exceptions\UnknownSmsProviderException
     */
    public function issue(string $phone, OtpPurpose $purpose): void
    {
        OtpCode::query()
            ->active($phone, $purpose)
            ->delete();

        $code = $this->generateCode();

        $this->sms->provider()->sendOtp($phone, $code);

        OtpCode::query()->create([
            'phone' => $phone,
            'purpose' => $purpose->value,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addSeconds((int) config('sms.otp.expires_in', 120)),
            'attempts' => 0,
        ]);
    }

    /**
     * Verify a submitted code against the latest active code for the phone and
     * purpose. A successful match consumes the code immediately.
     */
    public function verify(string $phone, OtpPurpose $purpose, string $code): bool
    {
        $otp = OtpCode::query()
            ->active($phone, $purpose)
            ->latest('id')
            ->first();

        if (! $otp) {
            return false;
        }

        $maxAttempts = (int) config('sms.otp.max_attempts', 5);

        if ($otp->attempts >= $maxAttempts || $otp->isExpired()) {
            $otp->update(['consumed_at' => now()]);

            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }

    /**
     * Whether a phone still has a non-expired active code for a purpose.
     */
    public function hasActiveCode(string $phone, OtpPurpose $purpose): bool
    {
        return OtpCode::query()
            ->active($phone, $purpose)
            ->where('expires_at', '>', now())
            ->exists();
    }

    private function generateCode(): string
    {
        $length = max(4, (int) config('sms.otp.length', 6));

        $min = 10 ** ($length - 1);
        $max = 10 ** $length - 1;

        return (string) random_int($min, $max);
    }
}