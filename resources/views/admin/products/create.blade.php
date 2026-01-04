@extends('layouts.app')

@section('title', 'Admin · New Product')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Add product</h1>
        <p class="lead">Register a new application so licenses can attach to it.</p>
    </div>
    <a class="link" href="{{ route('admin.products.index') }}">Back to shop</a>
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
    <form method="POST" action="{{ route('admin.products.store') }}">
        @include('admin.products._form', ['product' => new \App\Models\Product(), 'submitLabel' => 'Create product'])
    </form>
</div>
@endsection
