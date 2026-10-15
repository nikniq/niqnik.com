<?php

namespace App\Http\Controllers;

use App\Models\EventLog;
use App\Models\GameClient;
use App\Models\PlayerToken;
use App\Services\EventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Lets a player see which games hold a live token for their account and
 * revoke them, one game at a time or all at once.
 */
class ConnectedGamesController extends Controller
{
    public function revoke(Request $request, GameClient $game): RedirectResponse
    {
        $count = $this->activeTokens($request)->where('game_client_id', $game->id)->update(['revoked_at' => now()]);

        EventLogger::log(EventLog::TYPE_USER_UPDATE, $request->user()->id, [
            'action' => 'game_access_revoked',
            'game' => $game->slug,
            'tokens' => $count,
        ]);

        return redirect()->route('profile.show')
            ->with('status', $count ? 'Signed out of '.$game->name.'.' : $game->name.' had no active sessions.');
    }

    public function revokeAll(Request $request): RedirectResponse
    {
        $count = $this->activeTokens($request)->update(['revoked_at' => now()]);

        EventLogger::log(EventLog::TYPE_USER_UPDATE, $request->user()->id, [
            'action' => 'game_access_revoked_all',
            'tokens' => $count,
        ]);

        return redirect()->route('profile.show')
            ->with('status', $count ? 'Signed out of every game.' : 'No games were signed in.');
    }

    private function activeTokens(Request $request)
    {
        return PlayerToken::where('user_id', $request->user()->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }
}
