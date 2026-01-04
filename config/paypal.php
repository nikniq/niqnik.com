<?php

return [
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET'),
    'environment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
    'currency' => env('PAYPAL_CURRENCY', 'USD'),
    'intent' => env('PAYPAL_INTENT', 'CAPTURE'),
    'order_cache_prefix' => 'paypal:order:',
    'base_urls' => [
        'sandbox' => 'https://api-m.sandbox.paypal.com',
        'live' => 'https://api-m.paypal.com',
    ],
];
