<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePayPalOrderRequest;
use App\Models\Product;
use App\Services\PayPalClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalOrderController extends Controller
{
    public function __construct(private PayPalClient $paypal)
    {
    }

    public function store(CreatePayPalOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $product = Product::findOrFail($data['product_id']);
        $seats = (int) $data['seats_total'];

        $total = round((float) $product->price * $seats, 2);
        if ($total <= 0) {
            return response()->json([
                'message' => 'Unable to create an order for zero total.',
            ], 422);
        }

        $domain = $this->normalizeDomain($data['domain'] ?? null);

        try {
            // Keep description ASCII-only and short to satisfy PayPal schema rules.
            $asciiName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $product->name) ?: $product->name;
            $cleanName = preg_replace('/[^A-Za-z0-9 ._-]/', '', $asciiName) ?: 'Product';
            $description = sprintf('%s - %d seat(s)', Str::limit($cleanName, 80, ''), $seats);

            $order = $this->paypal->createOrder(
                $total,
                config('paypal.currency', 'USD'),
                $description
            );
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        Cache::put(
            $this->cacheKey($order['id']),
            [
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'seats_total' => $seats,
                'domain' => $domain,
                'total' => $total,
            ],
            now()->addMinutes(30)
        );

        return response()->json([
            'order_id' => $order['id'],
            'status' => $order['status'] ?? null,
        ]);
    }

    private function cacheKey(string $orderId): string
    {
        return config('paypal.order_cache_prefix', 'paypal:order:').$orderId;
    }

    private function normalizeDomain(?string $domain): ?string
    {
        if ($domain === null) {
            return null;
        }

        $normalized = strtolower(trim($domain));

        return $normalized === '' ? null : $normalized;
    }
}
