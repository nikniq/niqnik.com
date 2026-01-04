@extends('layouts.app')

@section('title', 'Security check · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Two-factor authentication</p>
        <h1>Enter the 6-digit security code</h1>
        <p class="lead">We sent a verification code to the email associated with your account.</p>
    </div>
</header>

<div class="grid">
    <section class="card">
        <h2>Verify code</h2>
        @if (session('status'))
            <div class="banner success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="banner error">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('login.two-factor.verify') }}" style="display:grid;gap:1rem;">
            @csrf
            <label>
                <span>Security code</span>
                <input type="text" name="code" inputmode="numeric" minlength="6" maxlength="6" pattern="[0-9]*" placeholder="123456" required autofocus>
            </label>
            <button type="submit">Verify and continue</button>
        </form>
        <form method="POST" action="{{ route('login.two-factor.resend') }}" style="margin-top:1rem;display:flex;gap:0.5rem;align-items:center;">
            @csrf
            <span style="color:var(--muted);">Didn't get the email?</span>
            <button type="submit" style="background:none;border:none;color:var(--primary);font-weight:600;padding:0;">Resend code</button>
        </form>
        <p class="hint" style="margin-top:1rem;">Entered the wrong email? <a class="link" href="{{ route('login') }}">Start over</a>.</p>
    </section>
</div>
@endsection
