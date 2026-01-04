@extends('layouts.app')

@section('title', 'Posts')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Posts</p>
        <h1>Blog posts</h1>
        <p class="lead">Showing recent posts pulled from the connected WordPress database.</p>
    </div>
</header>

<section class="card">
    @forelse($posts as $post)
        <article style="margin-bottom:1.25rem;">
            <h2 style="margin:0 0 0.25rem 0;"><a href="{{ url('/posts/' . $post->post_name) }}">{{ $post->post_title }}</a></h2>
            <div style="color:var(--muted);font-size:0.95rem;margin-bottom:0.5rem;">{{ optional($post->post_date)->format('M j, Y') }}</div>
            <p style="margin:0 0 0.5rem 0;">{{ $post->excerpt }}</p>
            <a class="link" href="{{ url('/posts/' . $post->post_name) }}">Read more →</a>
        </article>
    @empty
        <p>No posts found.</p>
    @endforelse

    <div style="margin-top:1rem;">
        {{ $posts->links() }}
    </div>
</section>
@endsection
