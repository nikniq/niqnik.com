<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventLogRequest;
use App\Models\ExternalLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class EventLogReceiverController extends Controller
{
    public function __invoke(StoreEventLogRequest $request): JsonResponse
    {
        $token = config('services.log_receiver.token');

        if ($token && $token !== $request->header('X-Log-Token')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $externalLog = ExternalLog::create([
            'type' => $request->validated('type'),
            'user_id' => $request->validated('user_id'),
            'source' => $request->validated('source'),
            'occurred_at' => $this->parseOccurredAt($request->validated('occurred_at')),
            'ip' => $request->ip(),
            'context' => $request->context(),
        ]);

        return response()->json([
            'id' => $externalLog->id,
            'type' => $externalLog->type,
            'user_id' => $externalLog->user_id,
            'source' => $externalLog->source,
            'occurred_at' => optional($externalLog->occurred_at)->toISOString(),
            'context' => $externalLog->context,
            'ip' => $externalLog->ip,
            'created_at' => optional($externalLog->created_at)->toISOString(),
        ], 201);
    }

    private function parseOccurredAt(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
