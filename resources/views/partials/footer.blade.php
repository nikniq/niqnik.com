<footer class="card" style="text-align:center;padding:1rem 1.5rem;margin-top:1.5rem;border-radius:0.8rem;">
    <div style="max-width:900px;margin:0 auto;color:var(--muted);">
        <p style="margin:0.1rem 0;">&copy; {{ date('Y') }} {{ config('site.name') }}</p>
        <p style="margin:0.25rem 0;font-size:0.95rem;">
            @if(config('posts.enabled'))
                <a class="link" href="{{ url('/posts') }}">Blog</a>
            @endif
            @if(config('shop.enabled'))
                @if(config('posts.enabled')) &middot; @endif
                <a class="link" href="{{ url('/shop') }}">Shop</a>
            @endif
            @if(Route::has('login'))
                @if(config('posts.enabled') || config('shop.enabled')) &middot; @endif
                <a class="link" href="{{ route('login') }}">Admin</a>
            @endif
        </p>
    </div>
</footer>
