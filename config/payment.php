<?php

return [
    'default' => env('PAYMENT_PROVIDER', 'paypal'),
    'providers' => [
        'paypal' => [
            'enabled' => env('PAYPAL_ENABLED', true),
        ],
        'stripe' => [
            'enabled' => env('STRIPE_ENABLED', false),
        ],
    ],
];
