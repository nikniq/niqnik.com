<?php

namespace App\Http\Controllers;

use App\Models\GameClient;
use App\Services\GameStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameStatsController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    public function leaderboard(Request $request, GameClient $game): View
    {
        abort_unless($game->is_enabled, 404);

        $user = $request->user();

        return view('games.leaderboard', [
            'game' => $game->load('product.media'),
            'rows' => $this->stats->leaderboard($game, (int) config('sso.leaderboard_limit', 25)),
            'mine' => $user ? $this->stats->statsFor($game, $user) : null,
            'myRank' => $user ? $this->stats->rankFor($game, $user) : null,
        ]);
    }
}
