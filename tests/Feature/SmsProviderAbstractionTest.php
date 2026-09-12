<?php

namespace Tests\Feature;

use App\Contracts\Sms\SmsProvider;
use App\Enums\OtpPurpose;
use App\Exceptions\SmsSendingFailedException;
use App\Exceptions\UnknownSmsProviderException;
use App\Models\OtpCode;
use App\Services\OtpService;
use App\Services\SmsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\RecordingSmsProvider;

class SmsProviderAbstractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_provider_is_log(): void
    {
        $this->assertSame('log', config('sms.default'));
        $this->assertTrue(app(SmsManager::class)->has('log'));
    }

    public function test_provider_allowlist_exposes_only_configured_drivers(): void
    {
        $manager = app(SmsManager::class);

        $this->assertSame(['log', 'disabled'], $manager->names());
        $this->assertFalse($manager->has('recording'));
    }

    public function test_unknown_provider_throws_controlled_exception(): void
    {
        $this->expectException(UnknownSmsProviderException::class);

        app(SmsManager::class)->resolve('not-a-real-provider');
    }

    public function test_disabled_provider_always_fails_in_a_controlled_way(): void
    {
        $this->expectException(SmsSendingFailedException::class);

        app(SmsManager::class)->resolve('disabled')->sendOtp('09123456789', '123456');
    }

    public function test_otp_service_never_persists_a_code_when_sending_fails(): void
    {
        config(['sms.default' => 'disabled']);

        try {
            app(OtpService::class)->issue('09123456789', OtpPurpose::REGISTRATION);
            $this->fail('Expected SmsSendingFailedException.');
        } catch (SmsSendingFailedException) {
            // Expected: no code should be stored for a delivery that failed.
        }

        $this->assertDatabaseCount('otp_codes', 0);
    }

    public function test_recording_provider_implements_the_contract(): void
    {
        $recording = new RecordingSmsProvider();

        $this->assertInstanceOf(SmsProvider::class, $recording);

        $recording->sendOtp('09123456789', '424242');

        $this->assertSame('424242', $recording->lastCode());
        $this->assertSame('09123456789', $recording->lastPhone());
    }

    public function test_resolved_provider_name_must_match_allowlist_key(): void
    {
        $this->app->instance(RecordingSmsProvider::class, new RecordingSmsProvider('mismatched'));
        $this->app->instance(SmsManager::class, new SmsManager($this->app, [
            'recording' => RecordingSmsProvider::class,
        ]));

        $this->expectException(UnknownSmsProviderException::class);

        app(SmsManager::class)->resolve('recording');
    }

    public function test_consumed_and_expired_otps_are_not_reusable_via_service(): void
    {
        $recording = new RecordingSmsProvider();
        $this->app->instance(RecordingSmsProvider::class, $recording);
        $this->app->instance(SmsManager::class, new SmsManager($this->app, [
            'recording' => RecordingSmsProvider::class,
        ]));
        config(['sms.default' => 'recording']);

        $service = app(OtpService::class);
        $service->issue('09123456789', OtpPurpose::REGISTRATION);

        $code = $recording->lastCode();

        $this->assertTrue($service->verify('09123456789', OtpPurpose::REGISTRATION, (string) $code));
        $this->assertFalse($service->verify('09123456789', OtpPurpose::REGISTRATION, (string) $code), 'A consumed code must never verify again.');

        $service->issue('09123456789', OtpPurpose::REGISTRATION);
        $stale = OtpCode::query()->latest('id')->first();
        $this->assertNotNull($stale);
        $stale->expires_at = now()->subMinute();
        $stale->save();

        $this->assertFalse($service->verify('09123456789', OtpPurpose::REGISTRATION, (string) $recording->lastCode()));
    }
}