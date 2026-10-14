<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EventLog;
use App\Models\User;
use App\Services\EventLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Social sign-in (Google, Meta).
 *
 * Three flows share the callback:
 *  - a guest with a matching provider id signs in;
 *  - a guest with a verified email matching an existing account links it and signs in;
 *  - a guest with no account gets one (no password until they set one);
 *  - a signed-in user links the provider to their own account.
 */
class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        return $this->driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $provider = strtolower($provider);

        try {
            $socialUser = $this->driver($provider)->user();
        } catch (Throwable $e) {
            Log::warning('oauth.callback.failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return $this->fail($provider, 'Unable to sign in with '.$this->label($provider).'. Please try again.');
        }

        $email = strtolower(trim((string) $socialUser->getEmail()));
        if ($email === '') {
            return $this->fail($provider, $this->label($provider).' did not return an email address.');
        }

        $emailVerified = $this->emailVerified($provider, $socialUser);

        // Signed-in user linking the provider to their own account.
        if (Auth::check()) {
            return $this->link($request, $provider, $socialUser);
        }

        $user = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            // Only trust a provider email to take over an existing account when
            // the provider says it has verified that address.
            if ($user && ! $emailVerified) {
                return $this->fail($provider, 'Your '.$this->label($provider).' email is not verified, so it cannot be used to sign in to the existing account for '.$email.'. Sign in with your password and link '.$this->label($provider).' from your profile.');
            }

            if ($user && $user->hasLinkedProvider() && ($user->provider_name !== $provider || $user->provider_id !== $socialUser->getId())) {
                return $this->fail($provider, 'This account is already linked to a different '.$user->providerLabel().' login.');
            }
        }

        if (! $user) {
            if (! $emailVerified) {
                return $this->fail($provider, 'Your '.$this->label($provider).' email address must be verified before you can sign up with it.');
            }

            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: $this->label($provider).' User',
                'email' => $email,
                'admin_email' => null,
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'email_verified_at' => now(),
                'password' => null,
            ]);
        } else {
            $user->forceFill([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar() ?: $user->avatar,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        EventLogger::log(EventLog::TYPE_LOGIN, $user->id, [
            'provider' => $provider,
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return redirect()->intended(route('dashboard'))->with('status', 'Signed in with '.$this->label($provider).'.');
    }

    /**
     * Remove the linked provider. Only allowed when the user can still sign in
     * with a password afterwards.
     */
    public function unlink(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasLinkedProvider()) {
            return redirect()->route('profile.show');
        }

        if (! $user->hasPassword()) {
            return redirect()->route('profile.show')->withErrors([
                'provider' => 'Set a password before unlinking '.$user->providerLabel().', or you will be locked out.',
            ]);
        }

        $label = $user->providerLabel();

        $user->forceFill([
            'provider_name' => null,
            'provider_id' => null,
        ])->save();

        return redirect()->route('profile.show')->with('status', $label.' account unlinked.');
    }

    private function link(Request $request, string $provider, SocialUser $socialUser): RedirectResponse
    {
        $user = $request->user();

        $owner = User::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($owner && $owner->id !== $user->id) {
            return redirect()->route('profile.show')->withErrors([
                'provider' => 'That '.$this->label($provider).' account is already linked to another user.',
            ]);
        }

        $user->forceFill([
            'provider_name' => $provider,
            'provider_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar() ?: $user->avatar,
        ])->save();

        return redirect()->route('profile.show')->with('status', $this->label($provider).' account linked.');
    }

    private function fail(string $provider, string $message): RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('profile.show')->withErrors(['provider' => $message]);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }

    private function emailVerified(string $provider, SocialUser $socialUser): bool
    {
        $raw = method_exists($socialUser, 'getRaw') ? (array) $socialUser->getRaw() : [];

        return match ($provider) {
            // Google's OpenID userinfo carries an explicit flag.
            'google' => filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN),
            // Meta only returns email addresses it has confirmed.
            'meta', 'facebook' => true,
            default => false,
        };
    }

    private function label(string $provider): string
    {
        return match ($provider) {
            'google' => 'Google',
            'meta', 'facebook' => 'Meta',
            default => ucfirst($provider),
        };
    }

    private function driver(string $provider)
    {
        $provider = strtolower($provider);

        if (! in_array($provider, ['google', 'meta', 'facebook'], true)) {
            abort(404);
        }

        if (! $this->providerEnabled($provider)) {
            abort(404);
        }

        $scopes = match ($provider) {
            'google' => ['openid', 'profile', 'email'],
            default => ['public_profile', 'email'],
        };

        $driverName = $provider === 'meta' ? 'facebook' : $provider;

        return Socialite::driver($driverName)->scopes($scopes);
    }

    private function providerEnabled(string $provider): bool
    {
        $configKey = 'social.providers.'.$provider.'.enabled';

        return (bool) config($configKey, false);
    }
}
