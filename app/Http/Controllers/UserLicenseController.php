<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseLicenseRequest;
use App\Models\License;
use App\Models\Product;
use App\Models\EventLog;
use App\Services\EventLogger;
use App\Services\PayPalClient;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class UserLicenseController extends Controller
{
    public function __construct(private PayPalClient $paypal)
    {
    }

    public function show(License $license): View
    {
        abort_unless($license->user_id === Auth::id(), 404);

        return view('licenses.show', [
            'license' => $license->load(['product', 'user']),
        ]);
    }

    public function store(PurchaseLicenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $orderId = $this->sanitizeOrderId($data['paypal_order_id'] ?? '');
        if ($orderId === null) {
            $this->logPurchase($request->user()->id, $data['product_id'] ?? null, 'paypal', 'failed', 'Invalid PayPal order reference');
            return back()
                ->withErrors(['payment' => 'Invalid PayPal order reference. Please restart checkout.'])
                ->withInput($request->except('paypal_order_id'));
        }

        $product = Product::findOrFail($data['product_id']);
        $seats = (int) $data['seats_total'];
        $cacheKey = $this->orderCacheKey($orderId);
        $cachedOrder = Cache::get($cacheKey);

        if (! $cachedOrder || ($cachedOrder['user_id'] ?? null) !== $request->user()->id) {
            $this->logPurchase($request->user()->id, $product->id, 'paypal', 'failed', 'PayPal session expired');
            return back()
                ->withErrors(['payment' => 'PayPal session expired. Please start checkout again.'])
                ->withInput($request->except('paypal_order_id'));
        }

        if ((int) $cachedOrder['product_id'] !== $product->id || (int) $cachedOrder['seats_total'] !== $seats) {
            $this->logPurchase($request->user()->id, $product->id, 'paypal', 'failed', 'Purchase details changed');
            return back()
                ->withErrors(['payment' => 'Purchase details changed. Please recreate the PayPal order.'])
                ->withInput($request->except('paypal_order_id'));
        }

        $total = (float) $cachedOrder['total'];

        try {
            $capture = $this->paypal->captureOrder($orderId);
        } catch (Exception $e) {
            $this->logPurchase($request->user()->id, $product->id, 'paypal', 'failed', $e->getMessage());
            return back()
                ->withErrors(['payment' => $e->getMessage()])
                ->withInput($request->except('paypal_order_id'));
        }

        $captureSummary = $this->captureSummary($capture);

        if ($captureSummary['status'] !== 'COMPLETED') {
            $this->logPurchase($request->user()->id, $product->id, 'paypal', 'failed', 'PayPal did not complete the transaction.');
            return back()
                ->withErrors(['payment' => 'PayPal did not complete the transaction.'])
                ->withInput($request->except('paypal_order_id'));
        }

        if (abs($captureSummary['amount'] - $total) > 0.01) {
            $this->logPurchase($request->user()->id, $product->id, 'paypal', 'failed', 'Captured amount mismatch');
            return back()
                ->withErrors(['payment' => 'Captured amount does not match the expected total.'])
                ->withInput($request->except('paypal_order_id'));
        }

        $duration = max(1, (int) ($product->duration_months ?? 12));
        $domainInput = $cachedOrder['domain'] ?? ($data['domain'] ?? null);

        try {
            $license = License::create([
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'seats_total' => $seats,
                'expires_at' => now()->addMonths($duration),
            ]);

            if ($domainInput) {
                $normalized = strtolower(trim($domainInput));
                if ($normalized !== '') {
                    $license->domains()->create(['domain' => $normalized]);
                }
            }
        } finally {
            Cache::forget($cacheKey);
        }

        $transactionId = $captureSummary['transaction_id'] ?? 'N/A';

        $this->logPurchase($request->user()->id, $product->id, 'paypal', 'succeeded', 'License purchased', [
            'transaction_id' => $transactionId,
            'amount' => $total,
            'currency' => $captureSummary['currency'] ?? null,
        ]);

        return redirect()
            ->route('licenses.show', $license)
            ->with('status', 'License purchased successfully. PayPal capture '.$transactionId.' · Total $'.number_format($total, 2));
    }

    private function orderCacheKey(string $orderId): string
    {
        return config('paypal.order_cache_prefix', 'paypal:order:').$orderId;
    }

    private function captureSummary(array $capture): array
    {
        $captureNode = data_get($capture, 'purchase_units.0.payments.captures.0', []);

        return [
            'status' => $captureNode['status'] ?? null,
            'amount' => isset($captureNode['amount']['value']) ? (float) $captureNode['amount']['value'] : 0.0,
            'currency' => $captureNode['amount']['currency_code'] ?? null,
            'transaction_id' => $captureNode['id'] ?? null,
        ];
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

    private function sanitizeOrderId(string $orderId): ?string
    {
        $trimmed = trim(preg_replace('/\s+/', '', $orderId));
        if ($trimmed === '') {
            return null;
        }

        // PayPal order ids are typically uppercase alphanumerics (and may include hyphen).
        if (! preg_match('/^[A-Z0-9-]+$/', $trimmed)) {
            return null;
        }

        return $trimmed;
    }
}
