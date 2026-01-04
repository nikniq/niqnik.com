@extends('layouts.app')

@section('title', 'Email Test · GD Login')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Diagnostics</p>
        <h1>Send a test email</h1>
        <p class="lead">Validate your mail driver by sending a quick message to any inbox.</p>
    </div>
</header>

<section class="card">
    <h2 style="margin-top:0;">Compose test message</h2>

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

    <form method="POST" action="{{ route('email.test.send') }}" style="display:grid;gap:1rem;margin-top:1.25rem;">
        @csrf
        <label>
            <span>Recipient</span>
            <input type="email" name="to" value="{{ old('to', $defaultRecipient) }}" required>
        </label>
        <label>
            <span>Subject</span>
            <input type="text" name="subject" value="{{ old('subject', 'Test email from GD Login') }}" required>
        </label>
        <label>
            <span>Message</span>
            <textarea name="message" rows="6" required>{{ old('message', 'If you received this email, your GD Login mail configuration works!') }}</textarea>
        </label>
        <button type="submit">Send test email</button>
    </form>
</section>
@endsection
