<?php

namespace App\Http\Controllers\Sso;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\GameClient;
use App\Models\SsoCode;
use App\Services\EventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Step 1 of game sign-in. The game sends the player here; the portal makes
 * sure they are logged in, then redirects back with a one-time code.
 *
 *   GET /sso/authorize?client_id=...&redirect_uri=...&state=...
 *       [&code_challenge=...&code_challenge_method=S256]
 */
class AuthorizeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|View
    {
        $client = GameClient::where('client_id', (string) $request->query('client_id'))->first();
        $redirectUri = (string) $request->query('redirect_uri', '');

        // Never redirect to an unregistered URI: show the error on the portal instead.
        if (! $client || ! $client->is_enabled) {
            return $this->errorPage('Unknown or disabled game client.');
        }
        if ($redirectUri === '' || ! $client->redirectAllowed($redirectUri)) {
            return $this->errorPage('The redirect URI is not registered for '.$client->name.'.');
        }

        $challenge = $request->query('code_challenge');
        $method = strtoupper((string) $request->query('code_challenge_method', 'S256'));

        if ($challenge !== null && ($method !== 'S256' || ! preg_match('/^[A-Za-z0-9\-_]{43,128}$/', (string) $challenge))) {
            return $this->redirectError($redirectUri, $request->query('state'), 'invalid_request', 'code_challenge must be S256 and base64url encoded.');
        }
        if ($client->is_public && $challenge === null) {
            return $this->redirectError($redirectUri, $request->query('state'), 'invalid_request', 'Public clients must use PKCE (code_challenge).');
        }

        $plain = Str::random(64);

        SsoCode::create([
            'game_client_id' => $client->id,
            'user_id' => $request->user()->id,
            'code_hash' => SsoCode::hashFor($plain),
            'redirect_uri' => $redirectUri,
            'code_challenge' => $challenge,
            'expires_at' => now()->addMinutes((int) config('sso.code_ttl_minutes', 5)),
        ]);

        EventLogger::log(EventLog::TYPE_GAME_LOGIN, $request->user()->id, [
            'game' => $client->slug,
            'ip' => $request->ip(),
        ]);

        $query = ['code' => $plain];
        if ($request->filled('state')) {
            $query['state'] = (string) $request->query('state');
        }

        return redirect()->away($this->appendQuery($redirectUri, $query));
    }

    private function redirectError(string $uri, ?string $state, string $error, string $description): RedirectResponse
    {
        $query = ['error' => $error, 'error_description' => $description];
        if ($state !== null && $state !== '') {
            $query['state'] = $state;
        }

        return redirect()->away($this->appendQuery($uri, $query));
    }

    private function appendQuery(string $uri, array $query): string
    {
        $separator = str_contains($uri, '?') ? '&' : '?';

        return $uri.$separator.http_build_query($query);
    }

    private function errorPage(string $message): View
    {
        return view('sso.error', ['message' => $message]);
    }
}
