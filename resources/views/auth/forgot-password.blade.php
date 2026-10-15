@extends('layouts.app')

@section('title', 'Reset password · Nikniq')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Account recovery</p>
        <h1>Forgot your password?</h1>
        <p class="lead">Enter your email and we will send you a link to choose a new one.</p>
    </div>
</header>

<div class="grid">
    <section class="card">
        <h2>Send reset link</h2>
        @if (session('status'))
            <div class="banner success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="banner error" role="alert">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@example.com">
            </label>
            <button type="submit">Email me a reset link</button>
            <p class="hint">Remembered it? <a class="link" href="{{ route('login') }}">Back to sign in</a>.</p>
            @if (config('social.providers.google.enabled'))
                <p class="hint">Signed up with Google? Just <a class="link" href="{{ route('oauth.redirect', ['provider' => 'google']) }}">continue with Google</a> instead.</p>
            @endif
        </form>
    </section>
</div>
@endsection
