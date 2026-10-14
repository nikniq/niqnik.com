<?php

namespace App\Http\Controllers;

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
        return view('profile.index', [
            'user' => $request->user(),
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
