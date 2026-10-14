<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameClient;
use App\Models\PlayerToken;
use App\Models\SsoCode;
use App\Models\User;
use App\Services\GameStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SsoTokenController extends Controller
{
    public function __construct(private GameStatsService $stats) {}

    /**
     * Step 2 of game sign-in: exchange the one-time code for a player token.
     *
     *   POST /api/sso/token
     *   { grant_type: "authorization_code", client_id, code, redirect_uri,
     *     client_secret (confidential clients) | code_verifier (PKCE) }
     */
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'grant_type' => ['required', 'in:authorization_code'],
            'client_id' => ['required', 'string', 'max:64'],
            'client_secret' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:128'],
            'redirect_uri' => ['required', 'string', 'max:2048'],
            'code_verifier' => ['nullable', 'string', 'min:43', 'max:128'],
        ]);

        $client = GameClient::where('client_id', $data['client_id'])->first();
        if (! $client || ! $client->is_enabled) {
            return $this->oauthError('invalid_client', 'Unknown or disabled client.', 401);
        }

        if (! $client->is_public && ! $client->verifySecret($data['client_secret'] ?? null)) {
            return $this->oauthError('invalid_client', 'Client authentication failed.', 401);
        }

        $code = SsoCode::with('user')
            ->where('code_hash', SsoCode::hashFor($data['code']))
            ->where('game_client_id', $client->id)
            ->first();

        if (! $code || ! $code->isUsable()) {
            return $this->oauthError('invalid_grant', 'Authorization code is invalid, expired, or already used.');
        }
        if (! hash_equals($code->redirect_uri, $data['redirect_uri'])) {
            return $this->oauthError('invalid_grant', 'redirect_uri does not match the authorization request.');
        }
        if ($client->is_public && $code->code_challenge === null) {
            return $this->oauthError('invalid_grant', 'PKCE is required for this client.');
        }
        if (! $code->verifierMatches($data['code_verifier'] ?? null)) {
            return $this->oauthError('invalid_grant', 'code_verifier does not match code_challenge.');
        }

        // Single use, even if issuing the token below fails.
        $code->forceFill(['used_at' => now()])->save();

        $plain = Str::random(80);
        $token = PlayerToken::create([
            'game_client_id' => $client->id,
            'user_id' => $code->user_id,
            'token_hash' => PlayerToken::hashFor($plain),
            'expires_at' => now()->addDays((int) config('sso.token_ttl_days', 30)),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plain,
            'expires_at' => $token->expires_at->toIso8601String(),
            'player' => $this->playerPayload($code->user),
            'stats' => $this->stats->statsFor($client, $code->user)?->toApi(),
        ], 201);
    }

    /**
     * GET /api/sso/me — who is this token for, and how are they doing in this game.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $player */
        $player = $request->attributes->get('player');
        /** @var GameClient $client */
        $client = $request->attributes->get('gameClient');

        return response()->json([
            'player' => $this->playerPayload($player),
            'game' => ['slug' => $client->slug, 'name' => $client->name],
            'stats' => $this->stats->statsFor($client, $player)?->toApi(),
            'rank' => $this->stats->rankFor($client, $player),
        ]);
    }

    /**
     * POST /api/sso/logout — revoke the presented token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var PlayerToken $token */
        $token = $request->attributes->get('playerToken');
        $token->forceFill(['revoked_at' => now()])->save();

        return response()->json(['revoked' => true]);
    }

    private function playerPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }

    private function oauthError(string $error, string $description, int $status = 400): JsonResponse
    {
        return response()->json(['error' => $error, 'error_description' => $description], $status);
    }
}
