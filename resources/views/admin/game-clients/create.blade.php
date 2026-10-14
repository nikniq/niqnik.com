@extends('layouts.app')

@section('title', 'Admin · New game client')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Register a game</h1>
        <p class="lead">Creates a client ID and secret the game uses to sign players in and report stats.</p>
    </div>
    <a class="link" href="{{ route('admin.game-clients.index') }}">Back to game clients</a>
</header>

@if ($errors->any())
    <div class="banner error"><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.game-clients.store') }}">
        @include('admin.game-clients._form', ['submitLabel' => 'Create game client'])
    </form>
</div>
@endsection
