<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\PaymentIntent;
use Stripe\StripeClient as BaseStripeClient;

class StripeClient
{
    private ?BaseStripeClient $client = null;

    public function __construct(private ?string $secret = null, ?BaseStripeClient $client = null)
    {
        $this->client = $client;
    }

    public static function make(): self
    {
        return new self((string) config('stripe.secret') ?: null);
    }

    /**
     * Build the SDK client on first use, so controllers that depend on this
     * service can be constructed (e.g. by route:list) without a secret.
     */
    private function client(): BaseStripeClient
    {
        if ($this->client === null) {
            if (! $this->secret) {
                throw new RuntimeException('STRIPE_SECRET is not configured.');
            }
            $this->client = new BaseStripeClient($this->secret);
        }

        return $this->client;
    }

    public function createPaymentIntent(int $amountCents, string $currency, array $metadata = []): PaymentIntent
    {
        try {
            return $this->client()->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'metadata' => $metadata,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe createPaymentIntent failed', [
                'amount_cents' => $amountCents,
                'currency' => $currency,
                'metadata' => $metadata,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to create a Stripe payment: '.$e->getMessage());
        }
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        try {
            return $this->client()->paymentIntents->retrieve($id);
        } catch (\Throwable $e) {
            Log::warning('Stripe retrievePaymentIntent failed', [
                'id' => $id,
                'message' => $e->getMessage(),
            ]);
            throw new RuntimeException('Unable to verify Stripe payment: '.$e->getMessage());
        }
    }
}
