<?php

namespace App\Http\Middleware;

use App\Models\PlayerToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a game's request on behalf of a player using the bearer
 * token issued by /api/sso/token. Sets `player` (User), `gameClient` and
 * `playerToken` on the request attributes.
 */
class AuthenticatePlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();
        if (! $plain) {
            return response()->json(['message' => 'Player token required.'], 401);
        }

        $token = PlayerToken::with(['user', 'client'])
            ->where('token_hash', PlayerToken::hashFor($plain))
            ->first();

        if (! $token || ! $token->isValid() || ! $token->client || ! $token->client->is_enabled || ! $token->user) {
            return response()->json(['message' => 'Player token is invalid or expired.'], 401);
        }

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('player', $token->user);
        $request->attributes->set('gameClient', $token->client);
        $request->attributes->set('playerToken', $token);

        return $next($request);
    }
}
