<?php

namespace App\Http\Controllers;

use App\Models\PlayerToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $connectedGames = collect();
        if (config('sso.enabled')) {
            // One row per game: newest token, last activity, live session count.
            $connectedGames = PlayerToken::with('client')
                ->where('user_id', $request->user()->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->get()
                ->groupBy('game_client_id')
                ->map(fn ($tokens) => (object) [
                    'client' => $tokens->first()->client,
                    'sessions' => $tokens->count(),
                    'connected_at' => $tokens->min('created_at'),
                    'last_used_at' => $tokens->max('last_used_at'),
                    'expires_at' => $tokens->max('expires_at'),
                ])
                ->filter(fn ($row) => $row->client !== null)
                ->sortByDesc(fn ($row) => $row->last_used_at ?? $row->connected_at)
                ->values();
        }

        return view('profile.index', [
            'user' => $request->user(),
            'connectedGames' => $connectedGames,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Accounts created through Google or Meta have no password yet, so
        // there is no "current password" to confirm the first time.
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
        if ($user->hasPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $request->validate($rules);

        $hadPassword = $user->hasPassword();

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();

        Auth::logoutOtherDevices($request->input('password'));

        return redirect()->route('profile.show')
            ->with('status', $hadPassword ? 'Password updated.' : 'Password set. You can now sign in with your email and password too.');
    }
}
