@extends('layouts.app')

@section('title', 'Admin · New Server')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Add server</h1>
        <p class="lead">Track a new server and its status.</p>
    </div>
    <a class="link" href="{{ route('admin.servers.index') }}">Back to servers</a>
</header>

@if ($errors->any())
    <div class="banner error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <form method="POST" action="{{ route('admin.servers.store') }}">
        @include('admin.servers._form', ['server' => new \App\Models\Server(), 'submitLabel' => 'Create server'])
    </form>
</div>
@endsection
