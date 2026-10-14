@extends('layouts.app')

@section('title', 'Game sign-in · Nikniq')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Game sign-in</p>
        <h1>We couldn't hand you over to the game</h1>
        <p class="lead">{{ $message }}</p>
    </div>
</header>
<div class="card">
    <p>This usually means the game is misconfigured. You are still signed in to Nikniq.</p>
    <p><a class="link" href="{{ route('dashboard') }}">Back to your dashboard</a></p>
</div>
@endsection
