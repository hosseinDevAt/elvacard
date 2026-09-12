<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Online Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Server-side allowlist mapping canonical provider names to gateway
    | implementations bound by the App\Contracts\Payments\PaymentGateway
    | contract. Nothing is registered in phase F5; real gateways (e.g.
    | Zarinpal) are added in a later phase.
    |
    | Gateways are selected exclusively through this list — never through user
    | input. No test doubles may be registered here.
    |
    */

    'gateways' => [
        // 'zarinpal' => App\Gateways\ZarinpalGateway::class,
    ],
];
