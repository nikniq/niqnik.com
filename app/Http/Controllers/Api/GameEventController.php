<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameClient;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameEventController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    /**
     * POST /api/games/{game}/events — report one event or a batch.
     *
     * Single: { event, score?, level?, duration_seconds?, won?, metadata?, occurred_at? }
     * Batch:  { events: [ {...}, {...} ] }
     */
    public function store(Request $request, GameClient $game): JsonResponse
    {
        /** @var GameClient $tokenClient */
        $tokenClient = $request->attributes->get('gameClient');
        if ($tokenClient->id !== $game->id) {
            return response()->json(['message' => 'This token was issued for a different game.'], 403);
        }

        $rules = [
            'event' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_\-.]+$/i'],
            'score' => ['nullable', 'integer', 'min:-2147483648', 'max:9223372036854775807'],
            'level' => ['nullable', 'string', 'max:64'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:604800'],
            'won' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array', 'max:50'],
            'occurred_at' => ['nullable', 'date'],
        ];

        if ($request->has('events')) {
            $max = (int) config('sso.max_batch', 50);
            $request->validate(['events' => ['required', 'array', 'min:1', 'max:'.$max]]);
            $validated = $request->validate(collect($rules)->mapWithKeys(fn ($r, $k) => ['events.*.'.$k => $r])->all());
            $items = $validated['events'];
        } else {
            $items = [$request->validate($rules)];
        }

        /** @var User $player */
        $player = $request->attributes->get('player');

        $ids = [];
        foreach ($items as $item) {
            $ids[] = $this->stats->record($game, $player, $item)->id;
        }

        return response()->json([
            'recorded' => count($ids),
            'event_ids' => $ids,
            'stats' => $this->stats->statsFor($game, $player)?->toApi(),
            'rank' => $this->stats->rankFor($game, $player),
        ], 201);
    }
}
