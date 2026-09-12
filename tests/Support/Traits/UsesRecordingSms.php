<?php

namespace Tests\Support\Traits;

use App\Services\SmsManager;
use Tests\Support\RecordingSmsProvider;

/**
 * Binds a recording SMS provider into the container so OTP feature tests can
 * inspect the exact codes handed to the (fake) SMS layer.
 */
trait UsesRecordingSms
{
    private function useRecordingSms(): RecordingSmsProvider
    {
        $recording = new RecordingSmsProvider();

        $this->app->instance(RecordingSmsProvider::class, $recording);
        $this->app->instance(SmsManager::class, new SmsManager($this->app, [
            'recording' => RecordingSmsProvider::class,
        ]));

        config(['sms.default' => 'recording']);

        return $recording;
    }
}