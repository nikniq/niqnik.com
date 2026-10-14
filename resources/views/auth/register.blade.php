@extends('layouts.app')

@section('title', 'Create account · Nikniq')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">New here?</p>
        <h1>Create your Nikniq account</h1>
        <p class="lead">Fill out the details below to unlock your dashboard instantly.</p>
    </div>
</header>

<div class="grid">
    <section class="card">
        <h2>Create account</h2>
        @if ($errors->any())
            <div class="banner error" role="alert">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label>
                <span>Name</span>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your name">
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="you@example.com">
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required autocomplete="new-password" minlength="8">
                <small class="field-help">At least 8 characters.</small>
            </label>
            <label>
                <span>Confirm password</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
            </label>
            <label>
                <span>Admin contact email <em style="font-style:normal;color:var(--muted);">(optional)</em></span>
                <input type="email" name="admin_email" value="{{ old('admin_email') }}" autocomplete="off" placeholder="ops@example.com">
                <small class="field-help">Where license and billing notices go, if different from your sign-in email.</small>
            </label>
            <label>
                <span>Quick check: what is {{ $captchaQuestion ?? session('captcha.challenge') ?? '1 + 1' }}?</span>
                <input type="number" name="captcha" inputmode="numeric" min="0" required autocomplete="off" placeholder="Answer">
            </label>
            <button type="submit">Create account</button>

            @if (config('social.providers.google.enabled') || config('social.providers.meta.enabled'))
            <div class="sso-divider stack" style="gap:0.6rem;">
                <p class="eyebrow" style="margin:0 0 0.35rem;">Or continue with</p>
                @if (config('social.providers.google.enabled'))
                <a class="sso-button" href="{{ route('oauth.redirect', ['provider' => 'google']) }}" style="padding:0.85rem 1.2rem;border-radius:999px;text-decoration:none;">Google</a>
                @endif
                @if (config('social.providers.meta.enabled'))
                <a class="sso-button" href="{{ route('oauth.redirect', ['provider' => 'meta']) }}" style="padding:0.85rem 1.2rem;border-radius:999px;text-decoration:none;">Meta</a>
                @endif
            </div>
            @endif

            <p class="hint">Already have access? <a class="link" href="{{ route('login') }}">Sign in</a>.</p>
        </form>
    </section>
</div>
@endsection
