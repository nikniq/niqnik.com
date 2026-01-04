<?php

namespace App\Services;

use App\Models\Product;

class ShopService
{
    public function getProducts()
    {
        return Product::orderBy('name')->get();
    }

    public function getProductDetails(Product $product): array
    {
        return [
            'product' => $product,
            'paypalClientId' => config('paypal.client_id'),
            'paypalCurrency' => config('paypal.currency', 'USD'),
            'stripePublicKey' => config('stripe.public_key'),
            'stripeCurrency' => config('stripe.currency', 'USD'),
            'paypalEnabled' => (bool) (config('payment.providers.paypal.enabled') && config('paypal.client_id')),
            'stripeEnabled' => (bool) (config('payment.providers.stripe.enabled') && config('stripe.public_key') && config('stripe.secret')),
        ];
    }
}
