@extends('layouts.app')

@section('title', 'Shop · GD Login')

@section('content')
@if(!config('shop.enabled'))
    <div class="card" style="margin:2rem auto;max-width:500px;text-align:center;">
        <h2>Shop is currently unavailable</h2>
        <p>The shop has been disabled by the administrator. Please check back later.</p>
    </div>
@else
    <header class="hero">
        <div>
            <p class="eyebrow">Shop</p>
            <h1>License plans built for growing teams</h1>
            <p class="lead">Browse the Glitchdata catalog, compare per-seat pricing, and start provisioning access in minutes.</p>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;align-items:center;">
            <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('login') }}">Sign in to purchase</a>
            <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('register') }}">Create an account</a>
        </div>
    </header>

    <section class="card" style="margin-bottom:1.5rem;">
        <h2 style="margin-top:0;">Available products</h2>
        <p style="margin-bottom:1.5rem;color:var(--muted);">All prices are in USD and renew automatically at the end of each license duration. Purchasing occurs inside the secure dashboard.</p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;">
            @forelse ($products as $product)
                <article style="border:1px solid rgba(15,23,42,0.08);border-radius:1rem;padding:1.25rem;display:flex;flex-direction:column;gap:0.75rem;background:var(--bg);">
                    <div style="display:flex;gap:0.75rem;align-items:center;">
                        @php
                            $thumb = null;
                            if (! empty($product->media) && ! empty($product->media->path)) {
                                try { $thumb = \Illuminate\Support\Facades\Storage::disk($product->media->disk)->url($product->media->path); } catch (\Exception $e) { $thumb = null; }
                            }
                            if (! $thumb) {
                                $desc = strtolower($product->description ?? '');
                                if (\Illuminate\Support\Str::contains($desc, ['space','asteroid','rocket','satellite','cosmic','galaxy'])) {
                                    $file = 'rocket.svg';
                                } elseif (\Illuminate\Support\Str::contains($desc, ['puzz','puzzle','match','brain'])) {
                                    $file = 'puzzle.svg';
                                } elseif (\Illuminate\Support\Str::contains($desc, ['race','racer','racing','car','drive'])) {
                                    $file = 'racer.svg';
                                } else {
                                    $file = 'joystick.svg';
                                }
                                $m = \App\Models\Media::where('filename', $file)->latest()->first();
                                if ($m) { $thumb = \Illuminate\Support\Facades\Storage::disk($m->disk)->url($m->path); }
                                else { $thumb = asset('assets/games/' . $file); }
                            }
                        @endphp
                        <div style="width:64px;height:64px;flex:0 0 64px;border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid rgba(15,23,42,0.04);">
                            <img src="{{ $thumb }}" alt="{{ $product->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                        </div>
                        <div>
                            <p class="eyebrow" style="margin-bottom:0.25rem;color:var(--muted);">{{ $product->category ?? 'Software' }}</p>
                            <h3 style="margin:0;"><a href="{{ route('shop.products.show', $product) }}" style="color:inherit;text-decoration:none;">{{ $product->name }}</a></h3>
                            <p style="margin:0;color:var(--muted);font-family:monospace;">{{ $product->product_code }}</p>
                        </div>
                    </div>
                    <p style="margin:0;">{{ $product->description ? \Illuminate\Support\Str::limit($product->description, 140) : 'No marketing copy provided yet.' }}</p>
                    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;">
                        <span style="font-size:2rem;font-weight:700;">${{ number_format($product->price, 2) }}<span style="font-size:1rem;font-weight:500;color:var(--muted);">/seat</span></span>
                        <span style="color:var(--muted);">{{ $product->duration_months }}-month term</span>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:auto;">
                        <a class="link" style="font-weight:600;" href="{{ route('shop.products.show', $product) }}">View details →</a>
                        <a class="link" href="{{ route('register') }}">Need an account?</a>
                    </div>
                </article>
            @empty
                <p style="color:var(--muted);">No products are available yet. Please check back soon.</p>
            @endforelse
        </div>
    </section>
@endif
@endsection
