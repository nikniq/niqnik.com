@extends('layouts.app')

@section('title', 'Admin · Edit Server')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Edit server</h1>
        <p class="lead">Update server metadata and status.</p>
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
    <form method="POST" action="{{ route('admin.servers.update', $server) }}">
        @csrf
        @method('PUT')
        @include('admin.servers._form', ['submitLabel' => 'Save changes'])
    </form>
</div>
@endsection
