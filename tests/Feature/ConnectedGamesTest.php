<?php

namespace Tests\Feature;

use App\Models\GameClient;
use App\Models\PlayerToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectedGamesTest extends TestCase
{
    use RefreshDatabase;

    private function issueToken(User $user, GameClient $client, string $plain, array $attrs = []): PlayerToken
    {
        return PlayerToken::create(array_merge([
            'game_client_id' => $client->id,
            'user_id' => $user->id,
            'token_hash' => PlayerToken::hashFor($plain),
            'expires_at' => now()->addDays(30),
        ], $attrs));
    }

    public function test_profile_lists_only_live_connected_games(): void
    {
        $user = User::factory()->create();
        $chopper = GameClient::factory()->create(['name' => 'Chopper', 'slug' => 'chopper']);
        $blockworld = GameClient::factory()->create(['name' => 'Blockworld', 'slug' => 'blockworld']);
        $old = GameClient::factory()->create(['name' => 'Retired Game', 'slug' => 'retired']);

        $this->issueToken($user, $chopper, 'a');
        $this->issueToken($user, $chopper, 'b');
        $this->issueToken($user, $blockworld, 'c', ['revoked_at' => now()]);
        $this->issueToken($user, $old, 'd', ['expires_at' => now()->subDay()]);

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('Connected games')
            ->assertSee('Chopper')
            ->assertDontSee('Blockworld')
            ->assertDontSee('Retired Game')
            ->assertSee('Sign out of all games');
    }

    public function test_player_can_sign_out_of_one_game(): void
    {
        $user = User::factory()->create();
        $chopper = GameClient::factory()->create(['slug' => 'chopper']);
        $blockworld = GameClient::factory()->create(['slug' => 'blockworld']);
        $this->issueToken($user, $chopper, 'chop');
        $this->issueToken($user, $blockworld, 'block');

        $this->actingAs($user)->post(route('profile.games.revoke', $chopper))->assertRedirect(route('profile.show'));

        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer chop'])->assertUnauthorized();
        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer block'])->assertOk();
    }

    public function test_player_can_sign_out_of_all_games(): void
    {
        $user = User::factory()->create();
        $chopper = GameClient::factory()->create(['slug' => 'chopper']);
        $blockworld = GameClient::factory()->create(['slug' => 'blockworld']);
        $this->issueToken($user, $chopper, 'chop');
        $this->issueToken($user, $blockworld, 'block');

        $this->actingAs($user)->post(route('profile.games.revoke-all'))->assertRedirect(route('profile.show'));

        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer chop'])->assertUnauthorized();
        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer block'])->assertUnauthorized();
    }

    public function test_player_cannot_revoke_someone_elses_tokens(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $chopper = GameClient::factory()->create(['slug' => 'chopper']);
        $this->issueToken($other, $chopper, 'theirs');

        $this->actingAs($me)->post(route('profile.games.revoke', $chopper));
        $this->actingAs($me)->post(route('profile.games.revoke-all'));

        $this->getJson('/api/sso/me', ['Authorization' => 'Bearer theirs'])->assertOk();
    }

    public function test_guest_cannot_revoke(): void
    {
        $chopper = GameClient::factory()->create(['slug' => 'chopper']);

        $this->post(route('profile.games.revoke', $chopper))->assertRedirect(route('login'));
    }
}
