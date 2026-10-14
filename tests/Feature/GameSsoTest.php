<?php

namespace Tests\Feature;

use App\Models\GameClient;
use App\Models\GameEvent;
use App\Models\PlayerGameStat;
use App\Models\PlayerToken;
use App\Models\SsoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class GameSsoTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'super-secret-value';

    private function client(array $attrs = []): GameClient
    {
        return GameClient::factory()->create(array_merge([
            'slug' => 'chopper',
            'name' => 'Chopper',
            'client_secret' => Hash::make(self::SECRET),
            'redirect_uris' => ['https://chopper.nikniq.test/auth/callback'],
        ], $attrs));
    }

    private function authorizeAndGetCode(User $user, GameClient $client, array $extra = []): array
    {
        $response = $this->actingAs($user)->get(route('sso.authorize', array_merge([
            'client_id' => $client->client_id,
            'redirect_uri' => $client->redirect_uris[0],
            'state' => 'xyz',
        ], $extra)));

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith($client->redirect_uris[0].'?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    private function exchange(GameClient $client, string $code, array $extra = [])
    {
        return $this->postJson('/api/sso/token', array_merge([
            'grant_type' => 'authorization_code',
            'client_id' => $client->client_id,
            'client_secret' => self::SECRET,
            'code' => $code,
            'redirect_uri' => $client->redirect_uris[0],
        ], $extra));
    }

    public function test_guest_is_sent_to_login_and_back_to_authorize(): void
    {
        $client = $this->client();

        $response = $this->get(route('sso.authorize', ['client_id' => $client->client_id, 'redirect_uri' => $client->redirect_uris[0]]));

        $response->assertRedirect(route('login'));
    }

    public function test_full_code_exchange_flow_issues_player_token(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $client = $this->client();

        $query = $this->authorizeAndGetCode($user, $client);
        $this->assertSame('xyz', $query['state']);
        $this->assertNotEmpty($query['code']);

        $response = $this->exchange($client, $query['code']);

        $response->assertCreated()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('player.id', $user->id)
            ->assertJsonPath('player.name', 'Ada')
            ->assertJsonPath('stats', null);

        $token = $response->json('access_token');
        $this->assertDatabaseHas('player_tokens', ['token_hash' => PlayerToken::hashFor($token), 'user_id' => $user->id]);

        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('player.id', $user->id)
            ->assertJsonPath('game.slug', 'chopper');
    }

    public function test_code_is_single_use(): void
    {
        $user = User::factory()->create();
        $client = $this->client();
        $code = $this->authorizeAndGetCode($user, $client)['code'];

        $this->exchange($client, $code)->assertCreated();
        $this->exchange($client, $code)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = User::factory()->create();
        $client = $this->client();
        $code = $this->authorizeAndGetCode($user, $client)['code'];
        SsoCode::query()->update(['expires_at' => now()->subMinute()]);

        $this->exchange($client, $code)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_wrong_secret_is_rejected(): void
    {
        $user = User::factory()->create();
        $client = $this->client();
        $code = $this->authorizeAndGetCode($user, $client)['code'];

        $this->exchange($client, $code, ['client_secret' => 'nope'])->assertStatus(401)->assertJsonPath('error', 'invalid_client');
    }

    public function test_redirect_uri_must_match_authorize_request(): void
    {
        $user = User::factory()->create();
        $client = $this->client(['redirect_uris' => ['https://chopper.nikniq.test/auth/callback', 'https://chopper.nikniq.test/other']]);
        $code = $this->authorizeAndGetCode($user, $client)['code'];

        $this->exchange($client, $code, ['redirect_uri' => 'https://chopper.nikniq.test/other'])
            ->assertStatus(400)->assertJsonPath('error', 'invalid_grant');
    }

    public function test_unregistered_redirect_uri_shows_error_without_redirecting(): void
    {
        $user = User::factory()->create();
        $client = $this->client();

        $response = $this->actingAs($user)->get(route('sso.authorize', [
            'client_id' => $client->client_id,
            'redirect_uri' => 'https://evil.example/steal',
        ]));

        $response->assertOk()->assertSee('not registered');
        $this->assertSame(0, SsoCode::count());
    }

    public function test_disabled_client_cannot_authorize(): void
    {
        $user = User::factory()->create();
        $client = $this->client(['is_enabled' => false]);

        $this->actingAs($user)->get(route('sso.authorize', ['client_id' => $client->client_id, 'redirect_uri' => $client->redirect_uris[0]]))
            ->assertOk()->assertSee('disabled');
    }

    public function test_public_client_requires_pkce_and_verifies_it(): void
    {
        $user = User::factory()->create();
        $client = $this->client(['is_public' => true]);

        // Without a challenge the authorize step errors back to the game.
        $query = $this->authorizeAndGetCode($user, $client);
        $this->assertSame('invalid_request', $query['error']);

        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $query = $this->authorizeAndGetCode($user, $client, ['code_challenge' => $challenge, 'code_challenge_method' => 'S256']);

        // Wrong verifier, no secret.
        $this->exchange($client, $query['code'], ['client_secret' => null, 'code_verifier' => Str::random(64)])
            ->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        // Correct verifier works; a new code is needed since the first was consumed.
        $query = $this->authorizeAndGetCode($user, $client, ['code_challenge' => $challenge]);
        $this->exchange($client, $query['code'], ['client_secret' => null, 'code_verifier' => $verifier])->assertCreated();
    }

    public function test_events_update_stats_and_leaderboard(): void
    {
        $user = User::factory()->create(['name' => 'Ada']);
        $rival = User::factory()->create(['name' => 'Grace']);
        $client = $this->client();

        $token = $this->exchange($client, $this->authorizeAndGetCode($user, $client)['code'])->json('access_token');
        $rivalToken = $this->exchange($client, $this->authorizeAndGetCode($rival, $client)['code'])->json('access_token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->postJson('/api/games/chopper/events', ['event' => 'start'], $headers)->assertCreated();
        $this->postJson('/api/games/chopper/events', ['event' => 'game_over', 'score' => 120, 'won' => false, 'duration_seconds' => 45, 'level' => 'cave-2'], $headers)
            ->assertCreated()
            ->assertJsonPath('stats.plays', 1)
            ->assertJsonPath('stats.best_score', 120)
            ->assertJsonPath('rank', 1);

        $this->postJson('/api/games/chopper/events', ['events' => [
            ['event' => 'game_over', 'score' => 300, 'won' => true, 'duration_seconds' => 90],
            ['event' => 'game_over', 'score' => 50, 'duration_seconds' => 10],
        ]], $headers)
            ->assertCreated()
            ->assertJsonPath('recorded', 2)
            ->assertJsonPath('stats.plays', 3)
            ->assertJsonPath('stats.wins', 1)
            ->assertJsonPath('stats.best_score', 300)
            ->assertJsonPath('stats.total_score', 470)
            ->assertJsonPath('stats.total_duration_seconds', 145)
            ->assertJsonPath('stats.last_level', 'cave-2');

        $this->postJson('/api/games/chopper/events', ['event' => 'game_over', 'score' => 999], ['Authorization' => 'Bearer '.$rivalToken])->assertCreated();

        $this->assertSame(5, GameEvent::count());
        $this->assertSame(2, PlayerGameStat::count());

        $this->getJson('/api/games/chopper/leaderboard')
            ->assertOk()
            ->assertJsonPath('leaderboard.0.player.name', 'Grace')
            ->assertJsonPath('leaderboard.0.best_score', 999)
            ->assertJsonPath('leaderboard.1.player.name', 'Ada')
            ->assertJsonPath('leaderboard.1.rank', 2);

        $this->getJson('/api/sso/me', $headers)->assertOk()->assertJsonPath('rank', 2);

        $this->actingAs($user)->get(route('games.leaderboard', $client))->assertOk()->assertSee('Grace')->assertSee('#2');
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Chopper')->assertSee('300');
    }

    public function test_token_for_one_game_cannot_report_to_another(): void
    {
        $user = User::factory()->create();
        $chopper = $this->client();
        $blockworld = GameClient::factory()->create(['slug' => 'blockworld']);

        $token = $this->exchange($chopper, $this->authorizeAndGetCode($user, $chopper)['code'])->json('access_token');

        $this->postJson('/api/games/blockworld/events', ['event' => 'game_over', 'score' => 1], ['Authorization' => 'Bearer '.$token])
            ->assertForbidden();
    }

    public function test_invalid_event_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $client = $this->client();
        $token = $this->exchange($client, $this->authorizeAndGetCode($user, $client)['code'])->json('access_token');

        $this->postJson('/api/games/chopper/events', ['event' => 'bad event!', 'score' => 'high'], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422);
        $this->postJson('/api/games/chopper/events', ['event' => 'x'])->assertUnauthorized();
        $this->postJson('/api/games/chopper/events', ['event' => 'x'], ['Authorization' => 'Bearer nope'])->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $client = $this->client();
        $token = $this->exchange($client, $this->authorizeAndGetCode($user, $client)['code'])->json('access_token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->postJson('/api/sso/logout', [], $headers)->assertOk();
        $this->getJson('/api/sso/me', $headers)->assertUnauthorized();
    }

    public function test_admin_can_register_edit_and_rotate_game_client(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.game-clients.store'), [
            'name' => 'Blockworld',
            'slug' => 'Blockworld',
            'homepage_url' => 'https://blockworld.nikniq.com',
            'redirect_uris' => "https://blockworld.nikniq.com/auth/callback\nhttps://blockworld.nikniq.com/auth/callback2",
            'is_public' => '1',
            'is_enabled' => '1',
        ]);

        $client = GameClient::where('slug', 'blockworld')->firstOrFail();
        $response->assertRedirect(route('admin.game-clients.edit', $client))->assertSessionHas('new_secret');
        $this->assertTrue($client->is_public);
        $this->assertCount(2, $client->redirect_uris);
        $this->assertTrue($client->verifySecret(session('new_secret')));

        $this->actingAs($admin)->get(route('admin.game-clients.index'))->assertOk()->assertSee('Blockworld');
        $this->actingAs($admin)->get(route('admin.game-clients.edit', $client))->assertOk()->assertSee($client->client_id);

        $old = $client->client_secret;
        $this->actingAs($admin)->post(route('admin.game-clients.rotate-secret', $client))->assertRedirect();
        $this->assertNotSame($old, $client->refresh()->client_secret);

        $this->actingAs($admin)->put(route('admin.game-clients.update', $client), [
            'name' => 'Blockworld 2',
            'slug' => 'blockworld',
            'redirect_uris' => 'https://blockworld.nikniq.com/cb',
            'is_enabled' => '0',
        ])->assertRedirect(route('admin.game-clients.index'));
        $this->assertSame('Blockworld 2', $client->refresh()->name);
        $this->assertFalse($client->is_enabled);
    }

    public function test_non_admin_cannot_manage_game_clients(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.game-clients.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.game-clients.store'), ['name' => 'x'])->assertForbidden();
    }
}
