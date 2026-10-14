@extends('layouts.app')

@section('title', 'Admin · '.$client->name)

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>{{ $client->name }}</h1>
        <p class="lead">{{ $client->players_count }} players · {{ number_format($client->events_count) }} events · {{ $client->tokens_count }} tokens issued</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        <a class="link" href="{{ route('games.leaderboard', $client) }}">Leaderboard</a>
        <a class="link" href="{{ route('admin.game-clients.index') }}">Back to game clients</a>
    </div>
</header>

@if (session('status'))
    <div class="banner success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="banner error"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <h2 style="margin-top:0;">Credentials</h2>
    <dl class="details">
        <div>
            <dt>Client ID</dt>
            <dd><code style="user-select:all;">{{ $client->client_id }}</code></dd>
        </div>
        <div>
            <dt>Client secret</dt>
            <dd>
                @if (session('new_secret'))
                    <code style="user-select:all;word-break:break-all;">{{ session('new_secret') }}</code>
                    <small class="field-help">Shown once. Store it in the game's server config now.</small>
                @else
                    <span class="hint">Hidden. Rotate to get a new one.</span>
                @endif
            </dd>
        </div>
        <div>
            <dt>Authorize URL</dt>
            <dd><code style="user-select:all;word-break:break-all;">{{ route('sso.authorize') }}</code></dd>
        </div>
        <div>
            <dt>Token URL</dt>
            <dd><code style="user-select:all;word-break:break-all;">{{ url('/api/sso/token') }}</code></dd>
        </div>
        <div>
            <dt>Events URL</dt>
            <dd><code style="user-select:all;word-break:break-all;">{{ url('/api/games/'.$client->slug.'/events') }}</code></dd>
        </div>
    </dl>
    <form method="POST" action="{{ route('admin.game-clients.rotate-secret', $client) }}" onsubmit="return confirm('Rotate the secret? The game stops working until it is updated.');" style="margin-top:1rem;">
        @csrf
        <button type="submit" class="secondary">Rotate secret</button>
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;">Settings</h2>
    <form method="POST" action="{{ route('admin.game-clients.update', $client) }}">
        @method('PUT')
        @include('admin.game-clients._form', ['submitLabel' => 'Save changes'])
    </form>
</div>

<div class="card">
    <h2 style="margin-top:0;">Danger zone</h2>
    <p class="hint">Deleting the client also deletes its tokens, events, and player stats.</p>
    <form method="POST" action="{{ route('admin.game-clients.destroy', $client) }}" onsubmit="return confirm('Delete {{ $client->name }} and all of its stats?');">
        @csrf
        @method('DELETE')
        <button type="submit" style="background:var(--error);">Delete game client</button>
    </form>
</div>
@endsection
