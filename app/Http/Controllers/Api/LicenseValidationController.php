<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateLicenseRequest;
use App\Models\License;
use Illuminate\Http\JsonResponse;

class LicenseValidationController extends Controller
{
    public function __invoke(ValidateLicenseRequest $request): JsonResponse
    {
        $license = License::with('product')
            ->where('identifier', $request->input('license_code'))
            ->first();

        if (! $license) {
            return response()->json([
                'valid' => false,
                'reason' => 'License not found.',
            ], 404);
        }

        $seatsRequested = $request->seatsRequested();
        $fitsSeatRequest = $license->seats_total >= $seatsRequested;
        $isExpired = $license->expires_at && $license->expires_at->isPast();

        $valid = $fitsSeatRequest && ! $isExpired;

        return response()->json([
            'valid' => $valid,
            'reason' => $valid ? null : $this->deriveReason($fitsSeatRequest, $isExpired),
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
        ]);
    }

    private function deriveReason(bool $fitsSeatRequest, bool $isExpired): string
    {
        if ($isExpired) {
            return 'License expired.';
        }

        if (! $fitsSeatRequest) {
            return 'Insufficient seats.';
        }

        return 'License invalid.';
    }
}
