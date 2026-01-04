@extends('layouts.app')

@section('title', 'Games · GD Login')

@section('content')
    <header class="hero">
        <div>
            <p class="eyebrow">Games</p>
            <h1>Featured games</h1>
            <p class="lead">Browse curated games and play experiences.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;align-items:center;">
            <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('login') }}">Sign in to play</a>
            <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('register') }}">Create an account</a>
        </div>
    </header>

    <section class="card" style="margin-bottom:1.5rem;">
        <h2 style="margin-top:0;">Available games</h2>
        <p style="margin-bottom:1.5rem;color:var(--muted);">Curated list — links go to the source when available.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;">
            @forelse ($products as $product)
                <article style="border:1px solid rgba(15,23,42,0.08);border-radius:1rem;padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem;background:var(--bg);">
                    <div>
                        <p class="eyebrow" style="margin-bottom:0.25rem;color:var(--muted);">{{ $product->category ?? 'Game' }}</p>
                        <h3 style="margin:0;">@if(!empty($product->url))<a href="{{ $product->url }}" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;">@endif{{ $product->name }}@if(!empty($product->url))</a>@endif</h3>
                        <p style="margin:0;color:var(--muted);font-family:monospace;">{{ $product->product_code ?? '' }}</p>
                    </div>
                    <p style="margin:0;">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 140) : 'No description provided yet.' }}</p>
                    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;">
                        @if($product->price && $product->price > 0)
                            <span style="font-size:2rem;font-weight:700;">${{ number_format($product->price, 2) }}<span style="font-size:1rem;font-weight:500;color:var(--muted);">/item</span></span>
                        @endif
                        @if(!empty($product->duration_months))
                            <span style="color:var(--muted);">{{ $product->duration_months }}-month term</span>
                        @endif
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:auto;">
                        @if(!empty($product->url))
                            <a class="link" style="font-weight:600;" href="{{ $product->url }}" target="_blank" rel="noopener">Play / View →</a>
                        @else
                            <a class="link" href="{{ route('register') }}">Need an account?</a>
                        @endif
                    </div>
                </article>
            @empty
                <p style="color:var(--muted);">No games are available yet. Please check back soon.</p>
            @endforelse
        </div>
    </section>
@endsection
