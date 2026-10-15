@extends('layouts.app')

@section('title', 'Choose a new password · Nikniq')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Account recovery</p>
        <h1>Choose a new password</h1>
        <p class="lead">This link works once and expires after an hour.</p>
    </div>
</header>

<div class="grid">
    <section class="card">
        <h2>New password</h2>
        @if ($errors->any())
            <div class="banner error" role="alert">
                <ul style="margin:0;padding-left:1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <p class="hint" style="margin-top:0.5rem;">Link expired or already used? <a class="link" href="{{ route('password.request') }}">Request a new one</a>.</p>
            </div>
        @endif
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">
            </label>
            <label>
                <span>New password</span>
                <input type="password" name="password" required autofocus autocomplete="new-password" minlength="8">
                <small class="field-help">At least 8 characters.</small>
            </label>
            <label>
                <span>Confirm new password</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password" minlength="8">
            </label>
            <button type="submit">Reset password</button>
        </form>
    </section>
</div>
@endsection
