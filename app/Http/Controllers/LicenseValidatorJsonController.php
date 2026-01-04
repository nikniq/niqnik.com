<?php

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseValidatorJsonController extends Controller
{
    public function __invoke(Request $request, string $key): JsonResponse
    {
        $seatsRequested = max(1, (int) $request->query('seats_requested', 1));
        $domain = $request->query('domain');

        $license = License::with(['product', 'domains'])->where('identifier', $key)->first();

        if (! $license) {
            Log::info('license.validate.not_found', [
                'key' => $key,
                'domain' => $domain,
                'seats_requested' => $seatsRequested,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'valid' => false,
                'reason' => 'License not found.',
            ], 404);
        }

        $errors = [];

        if ($license->expires_at && $license->expires_at->isPast()) {
            $errors[] = 'License expired.';
        }

        if ($license->seats_total !== null && $seatsRequested > $license->seats_total) {
            $errors[] = 'Insufficient seats.';
        }

        if ($domain) {
            $domains = $license->domains->pluck('domain')->filter()->map('strtolower');
            if ($domains->isNotEmpty() && ! $domains->contains(strtolower($domain))) {
                $errors[] = 'Domain not allowed for this license.';
            }
        }

        $valid = empty($errors);

        Log::info('license.validate.result', [
            'key' => $key,
            'valid' => $valid,
            'errors' => $errors,
            'domain' => $domain,
            'seats_requested' => $seatsRequested,
            'license_id' => $license->id,
            'product_id' => $license->product?->id,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'valid' => $valid,
            'reason' => $valid ? null : $errors[0],
            'errors' => $errors,
            'seats_requested' => $seatsRequested,
            'expires_at' => optional($license->expires_at)->toDateString(),
            'license' => [
                'id' => $license->id,
                'seats_total' => $license->seats_total,
                'inspect_uri' => $license->inspect_uri,
                'public_validator_uri' => $license->public_validator_uri,
            ],
            'product' => [
                'id' => $license->product->id,
                'name' => $license->product->name,
                'product_code' => $license->product->product_code,
                'vendor' => $license->product->vendor,
                'category' => $license->product->category,
            ],
            'constraints' => [
                'domain_required' => $license->domains->isNotEmpty(),
            ],
        ]);
    }
}
