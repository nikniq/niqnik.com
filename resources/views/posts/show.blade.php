@extends('layouts.app')

@section('title', $post->post_title)

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Post</p>
        <h1>{{ $post->post_title }}</h1>
        <p class="lead">{{ optional($post->post_date)->format('M j, Y') }}</p>
    </div>
</header>

<section class="card">
    <article>
        {!! $post->post_content !!}
    </article>
</section>
@endsection
