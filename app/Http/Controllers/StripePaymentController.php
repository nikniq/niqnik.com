<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Product;
use App\Models\EventLog;
use App\Services\StripeClient;
use App\Services\EventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StripePaymentController extends Controller
{
    public function __construct(private StripeClient $stripe)
    {
    }

    public function intent(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'domain' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($request->integer('product_id'));
        $amountCents = (int) round(((float) $product->price) * 100);
        if ($amountCents <= 0) {
            return response()->json(['message' => 'Unable to create a Stripe payment for zero total.'], 422);
        }

        $currency = config('stripe.currency', 'USD');
        $metadata = [
            'user_id' => (string) $request->user()->id,
            'product_id' => (string) $product->id,
            'domain' => (string) ($request->input('domain') ?? ''),
        ];

        $intent = $this->stripe->createPaymentIntent($amountCents, $currency, $metadata);

        return response()->json([
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'amount' => $amountCents,
            'currency' => $currency,
        ]);
    }

    public function complete(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'domain' => ['nullable', 'string', 'max:255'],
            'payment_intent_id' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $amountCents = (int) round(((float) $product->price) * 100);
        try {
            $intent = $this->stripe->retrievePaymentIntent($data['payment_intent_id']);
        } catch (\Throwable $e) {
            $this->logPurchase(Auth::id(), $product->id, 'stripe', 'failed', $e->getMessage());
            return back()
                ->withErrors(['payment' => $e->getMessage()])
                ->withInput($request->except('payment_intent_id'));
        }

        $expectedCurrency = strtolower(config('stripe.currency', 'USD'));
        $status = $intent->status ?? null;
        $intentAmount = (int) ($intent->amount ?? 0);
        $intentCurrency = strtolower((string) ($intent->currency ?? ''));
        $metadataUser = $intent->metadata->user_id ?? null;
        $metadataProduct = $intent->metadata->product_id ?? null;

        if ($status !== 'succeeded') {
            $this->logPurchase(Auth::id(), $product->id, 'stripe', 'failed', 'Stripe payment not completed.');
            return back()
                ->withErrors(['payment' => 'Stripe payment not completed.'])
                ->withInput($request->except('payment_intent_id'));
        }

        if ($intentAmount !== $amountCents || $intentCurrency !== $expectedCurrency) {
            $this->logPurchase(Auth::id(), $product->id, 'stripe', 'failed', 'Stripe payment amount mismatch.');
            return back()
                ->withErrors(['payment' => 'Stripe payment amount mismatch.'])
                ->withInput($request->except('payment_intent_id'));
        }

        if ((string) Auth::id() !== (string) $metadataUser || (string) $product->id !== (string) $metadataProduct) {
            $this->logPurchase(Auth::id(), $product->id, 'stripe', 'failed', 'Stripe payment does not match this order.');
            return back()
                ->withErrors(['payment' => 'Stripe payment does not match this order.'])
                ->withInput($request->except('payment_intent_id'));
        }

        $duration = max(1, (int) ($product->duration_months ?? 12));
        $domainInput = $data['domain'] ?? ($intent->metadata->domain ?? null);

        $license = License::create([
            'product_id' => $product->id,
            'user_id' => Auth::id(),
            'seats_total' => 1,
            'expires_at' => now()->addMonths($duration),
        ]);

        if ($domainInput) {
            $normalized = strtolower(trim($domainInput));
            if ($normalized !== '') {
                $license->domains()->create(['domain' => $normalized]);
            }
        }

        $this->logPurchase(Auth::id(), $product->id, 'stripe', 'succeeded', 'License purchased', [
            'transaction_id' => $intent->id,
            'amount' => $amountCents / 100,
            'currency' => $intentCurrency,
        ]);

        return redirect()
            ->route('licenses.show', $license)
            ->with('status', 'License purchased successfully. Stripe payment '.$intent->id.' · Total $'.number_format($amountCents / 100, 2));
    }

    private function logPurchase(?int $userId, ?int $productId, string $provider, string $status, string $message, array $extra = []): void
    {
        EventLogger::log(EventLog::TYPE_PURCHASE, $userId, array_merge([
            'provider' => $provider,
            'status' => $status,
            'message' => $message,
            'product_id' => $productId,
        ], $extra));
    }
}
