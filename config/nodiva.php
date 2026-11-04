<?php

return [
    'default_currency' => env('NODIVA_DEFAULT_CURRENCY', 'GHS'),
    'default_country' => env('NODIVA_DEFAULT_COUNTRY', 'GH'),
    'download_link_ttl' => env('NODIVA_DOWNLOAD_LINK_TTL', 5),
    'require_mfa_for_platform' => env('NODIVA_REQUIRE_MFA_FOR_PLATFORM', false),
    'result_separation_of_duties' => env('NODIVA_RESULT_SEPARATION_OF_DUTIES', true),
    'min_aggregate_cell' => env('NODIVA_MIN_AGGREGATE_CELL', 5),
    'payment_provider' => env('NODIVA_PAYMENT_PROVIDER', 'manual'),
    'seed_demo' => env('NODIVA_SEED_DEMO', true),
];
