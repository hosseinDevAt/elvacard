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
    | Absolute (inclusive) bounds of Jalali expiration years offered and
    | validated for bank card customization in the custom-design flow.
    | The selector must start at 1400 and must not be artificially capped
    | at 1415 (the previous relative "current year + 10" ceiling).
    |
    */
    'card_expiry_min_year' => 1400,
    'card_expiry_max_year' => 1430,
];
