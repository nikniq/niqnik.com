<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['social.providers.google.enabled' => true]);
    }

    private function fakeGoogleUser(array $overrides = []): SocialiteUser
    {
        $attrs = array_merge([
            'id' => 'google-123',
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/a/photo',
            'verified' => true,
        ], $overrides);

        $user = new SocialiteUser;
        $user->map([
            'id' => $attrs['id'],
            'nickname' => null,
            'name' => $attrs['name'],
            'email' => $attrs['email'],
            'avatar' => $attrs['avatar'],
        ]);
        $user->setRaw([
            'sub' => $attrs['id'],
            'email' => $attrs['email'],
            'email_verified' => $attrs['verified'],
            'verified_email' => $attrs['verified'],
            'picture' => $attrs['avatar'],
        ]);

        return $user;
    }

    private function mockGoogle(SocialiteUser $user): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirect_sends_user_to_google(): void
    {
        config([
            'services.google.client_id' => 'id',
            'services.google.client_secret' => 'secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('oauth.redirect', 'google'));

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
    }

    public function test_disabled_provider_returns_404(): void
    {
        config(['social.providers.google.enabled' => false]);

        $this->get(route('oauth.redirect', 'google'))->assertNotFound();
    }

    public function test_new_google_user_is_created_without_password_and_signed_in(): void
    {
        $this->mockGoogle($this->fakeGoogleUser());

        $response = $this->get(route('oauth.callback', 'google'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertSame('google', $user->provider_name);
        $this->assertSame('google-123', $user->provider_id);
        $this->assertSame('https://lh3.googleusercontent.com/a/photo', $user->avatar);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_existing_user_with_verified_email_is_linked_and_signed_in(): void
    {
        $existing = User::factory()->create(['email' => 'ada@example.com']);
        $this->mockGoogle($this->fakeGoogleUser());

        $this->get(route('oauth.callback', 'google'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($existing);
        $existing->refresh();
        $this->assertSame('google', $existing->provider_name);
        $this->assertSame('google-123', $existing->provider_id);
        $this->assertSame(1, User::count());
    }

    public function test_unverified_google_email_cannot_take_over_existing_account(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $this->mockGoogle($this->fakeGoogleUser(['verified' => false]));

        $response = $this->get(route('oauth.callback', 'google'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unverified_google_email_cannot_register(): void
    {
        $this->mockGoogle($this->fakeGoogleUser(['verified' => false]));

        $this->get(route('oauth.callback', 'google'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_returning_google_user_signs_in_by_provider_id_even_if_email_changed(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);
        $this->mockGoogle($this->fakeGoogleUser(['email' => 'new@example.com']));

        $this->get(route('oauth.callback', 'google'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_signed_in_user_can_link_google_from_profile(): void
    {
        $user = User::factory()->create();
        $this->mockGoogle($this->fakeGoogleUser(['email' => 'other@example.com']));

        $response = $this->actingAs($user)->get(route('oauth.callback', 'google'));

        $response->assertRedirect(route('profile.show'));
        $user->refresh();
        $this->assertSame('google', $user->provider_name);
        $this->assertSame('google-123', $user->provider_id);
    }

    public function test_cannot_link_google_account_owned_by_another_user(): void
    {
        User::factory()->create(['provider_name' => 'google', 'provider_id' => 'google-123']);
        $user = User::factory()->create();
        $this->mockGoogle($this->fakeGoogleUser());

        $response = $this->actingAs($user)->get(route('oauth.callback', 'google'));

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHasErrors('provider');
        $this->assertNull($user->refresh()->provider_name);
    }

    public function test_unlink_requires_a_password(): void
    {
        $user = User::factory()->create([
            'provider_name' => 'google',
            'provider_id' => 'google-123',
            'password' => null,
        ]);

        $response = $this->actingAs($user)->post(route('oauth.unlink'));

        $response->assertSessionHasErrors('provider');
        $this->assertSame('google', $user->refresh()->provider_name);
    }

    public function test_unlink_clears_provider_when_password_exists(): void
    {
        $user = User::factory()->create([
            'provider_name' => 'google',
            'provider_id' => 'google-123',
        ]);

        $this->actingAs($user)->post(route('oauth.unlink'))->assertRedirect(route('profile.show'));

        $user->refresh();
        $this->assertNull($user->provider_name);
        $this->assertNull($user->provider_id);
    }

    public function test_oauth_only_user_can_set_password_without_current_password(): void
    {
        $user = User::factory()->create([
            'provider_name' => 'google',
            'provider_id' => 'google-123',
            'password' => null,
        ]);

        $response = $this->actingAs($user)->post(route('profile.password.update'), [
            'password' => 'new-secret-password-1',
            'password_confirmation' => 'new-secret-password-1',
        ]);

        $response->assertRedirect(route('profile.show'));
        $response->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-secret-password-1', $user->refresh()->password));
    }

    public function test_user_with_password_must_supply_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('profile.password.update'), [
            'password' => 'new-secret-password-1',
            'password_confirmation' => 'new-secret-password-1',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_login_fails_for_oauth_only_account(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
            'provider_name' => 'google',
            'provider_id' => 'google-123',
            'password' => null,
        ]);

        $response = $this->post('/login', ['email' => 'ada@example.com', 'password' => 'anything']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
