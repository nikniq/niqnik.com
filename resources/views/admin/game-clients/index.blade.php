@extends('layouts.app')

@section('title', 'Admin · Game clients')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Game clients</h1>
        <p class="lead">Games that sign players in through Nikniq and report their stats here.</p>
    </div>
    <div class="admin-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.products.index') }}">Products</a>
        <a href="{{ route('admin.users.index') }}">Users</a>
        <a href="{{ route('admin.event-logs.index') }}">Event Logs</a>
        <a class="active" href="{{ route('admin.game-clients.index') }}">Game clients</a>
        <a href="{{ route('admin.game-clients.create') }}">+ Register game</a>
    </div>
</header>

@if (session('status'))
    <div class="banner success">{{ session('status') }}</div>
@endif

<div class="card">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.5rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;">
                    <th style="padding:0 0.75rem;">Game</th>
                    <th style="padding:0 0.75rem;">Client ID</th>
                    <th style="padding:0 0.75rem;">Type</th>
                    <th style="padding:0 0.75rem;text-align:right;">Players</th>
                    <th style="padding:0 0.75rem;text-align:right;">Events</th>
                    <th style="padding:0 0.75rem;">Status</th>
                    <th style="padding:0 0.75rem;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clients as $client)
                    <tr style="background:var(--bg);">
                        <td style="padding:0.9rem 0.75rem;">
                            <div style="font-weight:600;">{{ $client->name }}</div>
                            <div style="color:var(--muted);font-size:0.85rem;">{{ $client->slug }}@if($client->product) · {{ $client->product->product_code }}@endif</div>
                        </td>
                        <td style="padding:0.9rem 0.75rem;"><code style="font-size:0.8rem;">{{ $client->client_id }}</code></td>
                        <td style="padding:0.9rem 0.75rem;">{{ $client->is_public ? 'Public (PKCE)' : 'Confidential' }}</td>
                        <td style="padding:0.9rem 0.75rem;text-align:right;">{{ number_format($client->players_count) }}</td>
                        <td style="padding:0.9rem 0.75rem;text-align:right;">{{ number_format($client->events_count) }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $client->is_enabled ? 'Enabled' : 'Disabled' }}</td>
                        <td style="padding:0.9rem 0.75rem;display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <a class="link" href="{{ route('admin.game-clients.edit', $client) }}">Edit</a>
                            <a class="link" href="{{ route('games.leaderboard', $client) }}">Leaderboard</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">No games registered yet. <a class="link" href="{{ route('admin.game-clients.create') }}">Register the first one</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:1rem;">{{ $clients->links() }}</div>
</div>
@endsection
