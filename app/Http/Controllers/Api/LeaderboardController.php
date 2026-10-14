<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameClient;
use App\Services\GameStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    /**
     * GET /api/games/{game}/leaderboard?limit=25
     */
    public function __invoke(Request $request, GameClient $game): JsonResponse
    {
        abort_unless($game->is_enabled, 404);

        $limit = (int) $request->query('limit', config('sso.leaderboard_limit', 25));

        return response()->json([
            'game' => ['slug' => $game->slug, 'name' => $game->name],
            'leaderboard' => $this->stats->leaderboard($game, $limit),
        ]);
    }
}
