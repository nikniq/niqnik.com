<?php

return [
    'secret' => env('STRIPE_SECRET'),
    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'currency' => env('STRIPE_CURRENCY', 'USD'),
];
