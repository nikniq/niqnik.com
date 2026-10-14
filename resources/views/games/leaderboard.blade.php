@extends('layouts.app')

@section('title', $game->name.' leaderboard · Nikniq')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Leaderboard</p>
        <h1>{{ $game->name }}</h1>
        <p class="lead">Top scores reported by the game. Sign in to the game with your Nikniq account to appear here.</p>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
        @if ($game->playUrl())
            <a class="link" href="{{ $game->playUrl() }}" target="_blank" rel="noopener">Play {{ $game->name }}</a>
        @endif
        <a class="link" href="{{ Route::has('games.index') ? route('games.index') : route('home') }}">All games</a>
    </div>
</header>

@auth
<div class="card">
    <h2 style="margin-top:0;">Your standing</h2>
    @if ($mine)
        <dl class="details">
            <div><dt>Rank</dt><dd>{{ $myRank ? '#'.$myRank : 'Unranked' }}</dd></div>
            <div><dt>Best score</dt><dd>{{ $mine->best_score !== null ? number_format($mine->best_score) : '—' }}</dd></div>
            <div><dt>Plays</dt><dd>{{ number_format($mine->plays) }}</dd></div>
            <div><dt>Wins</dt><dd>{{ number_format($mine->wins) }}</dd></div>
            <div><dt>Last played</dt><dd>{{ $mine->last_played_at?->diffForHumans() ?? '—' }}</dd></div>
        </dl>
    @else
        <p class="hint">You have not played {{ $game->name }} with this account yet.</p>
    @endif
</div>
@endauth

<div class="card">
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:separate;border-spacing:0 0.5rem;">
            <thead>
                <tr style="text-align:left;color:var(--muted);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.1em;">
                    <th style="padding:0 0.75rem;">#</th>
                    <th style="padding:0 0.75rem;">Player</th>
                    <th style="padding:0 0.75rem;text-align:right;">Best score</th>
                    <th style="padding:0 0.75rem;text-align:right;">Plays</th>
                    <th style="padding:0 0.75rem;text-align:right;">Wins</th>
                    <th style="padding:0 0.75rem;">Last played</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr style="background:var(--bg);{{ auth()->id() === $row['player']['id'] ? 'outline:2px solid var(--primary);' : '' }}">
                        <td style="padding:0.9rem 0.75rem;font-weight:700;">{{ $row['rank'] }}</td>
                        <td style="padding:0.9rem 0.75rem;display:flex;align-items:center;gap:0.6rem;">
                            @if ($row['player']['avatar'])
                                <img src="{{ $row['player']['avatar'] }}" alt="" width="28" height="28" referrerpolicy="no-referrer" style="border-radius:50%;object-fit:cover;">
                            @endif
                            <span style="font-weight:600;">{{ $row['player']['name'] }}</span>
                        </td>
                        <td style="padding:0.9rem 0.75rem;text-align:right;">{{ number_format($row['best_score']) }}</td>
                        <td style="padding:0.9rem 0.75rem;text-align:right;">{{ number_format($row['plays']) }}</td>
                        <td style="padding:0.9rem 0.75rem;text-align:right;">{{ number_format($row['wins']) }}</td>
                        <td style="padding:0.9rem 0.75rem;">{{ $row['last_played_at'] ? \Illuminate\Support\Carbon::parse($row['last_played_at'])->diffForHumans() : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:1rem 0.75rem;text-align:center;color:var(--muted);">No scores yet. Be the first.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
