<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Business / display timezone
    |--------------------------------------------------------------------------
    |
    | Timestamps are stored canonically in UTC (config('app.timezone')).
    | This value describes the timezone used for business-day boundaries
    | and for presenting Jalali dates to users across the customer and
    | admin surfaces. All calendar logic must go through the central
    | DateService.
    |
    */
    'business_timezone' => env('APP_BUSINESS_TIMEZONE', 'Asia/Tehran'),

    /*
    |--------------------------------------------------------------------------
    | Jalali calendar parameters
    |--------------------------------------------------------------------------
    |
    | Range of Jalali expiration years offered/validated for bank card
    | customization (relative to the current Tehran-local Jalali year).
    |
    */
    'card_expiry_max_years' => 10,
];
