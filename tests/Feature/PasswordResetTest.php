<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders_and_login_links_to_it(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Email me a reset link');
        $this->get('/login')->assertOk()->assertSee('Forgot your password?');
    }

    public function test_reset_link_is_emailed_to_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/forgot-password', ['email' => 'ada@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_unknown_email_gets_the_same_response_and_no_mail(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com']);
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token.'?email=ada@example.com')->assertOk()->assertSee('ada@example.com');

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ]);

        $response->assertRedirect(route('login'))->assertSessionHas('status');
        $this->assertTrue(Hash::check('brand-new-password-1', $user->refresh()->password));

        // Token is single use.
        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'another-password-22',
            'password_confirmation' => 'another-password-22',
        ])->assertSessionHasErrors('email');
    }

    public function test_invalid_token_is_rejected(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->post('/reset-password', [
            'token' => 'bogus',
            'email' => 'ada@example.com',
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ])->assertSessionHasErrors('email');
    }

    public function test_oauth_only_user_can_recover_by_setting_a_password(): void
    {
        $user = User::factory()->create(['email' => 'ada@example.com', 'password' => null, 'provider_name' => 'google', 'provider_id' => 'g1']);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'ada@example.com',
            'password' => 'brand-new-password-1',
            'password_confirmation' => 'brand-new-password-1',
        ])->assertRedirect(route('login'));

        $this->assertTrue($user->refresh()->hasPassword());
        // Credentials are accepted: the response is the two-factor step or the dashboard, never a validation error.
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'brand-new-password-1'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }
}
