@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Account</p>
        <h1>Your profile</h1>
        <p class="lead">Update your credentials and keep your account secure.</p>
    </div>
    <a class="link" href="{{ route('dashboard') }}">Back to dashboard</a>
</header>

@if (session('status'))
    <div class="banner success">{{ session('status') }}</div>
@endif

@if ($errors->has('provider'))
    <div class="banner error">{{ $errors->first('provider') }}</div>
@endif

<div class="card">
    <h2 style="margin-top:0;">Account info</h2>
    <div style="display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap;">
        @if ($user->avatar)
            <img src="{{ $user->avatar }}" alt="" width="72" height="72" referrerpolicy="no-referrer" style="border-radius:50%;object-fit:cover;flex:0 0 auto;">
        @endif
        <dl class="details" style="flex:1 1 240px;">
            <div>
                <dt>Name</dt>
                <dd>{{ $user->name }}</dd>
            </div>
            <div>
                <dt>Email</dt>
                <dd>{{ $user->email }}</dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd>{{ $user->is_admin ? 'Admin' : 'User' }}</dd>
            </div>
            <div>
                <dt>Sign-in methods</dt>
                <dd>
                    {{ $user->hasPassword() ? 'Password' : '' }}{{ $user->hasPassword() && $user->hasLinkedProvider() ? ' · ' : '' }}{{ $user->providerLabel() }}
                </dd>
            </div>
        </dl>
    </div>
</div>

@php
    $providers = collect(config('social.providers', []))->filter(fn ($p) => ! empty($p['enabled']));
@endphp
@if ($providers->isNotEmpty())
<div class="card">
    <h2 style="margin-top:0;">Connected accounts</h2>
    @if ($user->hasLinkedProvider())
        <p>Signed in with <strong>{{ $user->providerLabel() }}</strong>.</p>
        @if ($user->hasPassword())
            <form method="POST" action="{{ route('oauth.unlink') }}" onsubmit="return confirm('Unlink {{ $user->providerLabel() }}? You will sign in with your password only.');">
                @csrf
                <button type="submit" class="secondary">Unlink {{ $user->providerLabel() }}</button>
            </form>
        @else
            <p class="hint">Set a password below before unlinking, so you can still sign in.</p>
        @endif
    @else
        <p class="hint">Link a provider to sign in with one click.</p>
        <div class="stack">
            @foreach ($providers as $key => $provider)
                <a href="{{ route('oauth.redirect', ['provider' => $key]) }}" style="display:inline-flex;align-items:center;gap:0.5rem;background:#fff;color:#0f172a;border:1px solid rgba(15,23,42,0.15);box-shadow:0 8px 18px rgba(15,23,42,0.08);padding:0.6rem 1rem;border-radius:0.6rem;text-decoration:none;font-weight:700;">
                    Link {{ $key === 'google' ? 'Google' : ucfirst($key) }}
                </a>
            @endforeach
        </div>
    @endif
</div>
@endif

<div class="card">
    <h2 style="margin-top:0;">{{ $user->hasPassword() ? 'Change password' : 'Set a password' }}</h2>
    @unless ($user->hasPassword())
        <p class="hint">Your account was created with {{ $user->providerLabel() ?? 'a social login' }}. Add a password to sign in with your email as well.</p>
    @endunless
    <form method="POST" action="{{ route('profile.password.update') }}" class="stack">
        @csrf
        @if ($user->hasPassword())
        <label>
            <span>Current password</span>
            <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        @endif
        <label>
            <span>New password</span>
            <input type="password" name="password" required autocomplete="new-password">
        </label>
        <label>
            <span>Confirm new password</span>
            <input type="password" name="password_confirmation" required autocomplete="new-password">
        </label>
        @if ($errors->has('current_password') || $errors->has('password'))
            <div class="banner error">
                <ul>
                    @foreach (array_merge($errors->get('current_password'), $errors->get('password')) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <button type="submit">{{ $user->hasPassword() ? 'Update password' : 'Set password' }}</button>
    </form>
</div>
@endsection
