@extends('layouts.app')

@section('title', 'Admin · Edit Product')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Edit product</h1>
        <p class="lead">Adjust metadata for {{ $product->name }}.</p>
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
    <form method="POST" action="{{ route('admin.products.update', $product) }}">
        @method('PUT')
        @include('admin.products._form', ['product' => $product, 'submitLabel' => 'Save changes'])
    </form>
</div>
@endsection
