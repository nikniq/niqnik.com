@extends('layouts.app')

@section('title', 'Admin · Media')

@section('content')
<header class="hero">
    <div>
        <p class="eyebrow">Admin</p>
        <h1>Media library</h1>
        <p class="lead">Uploaded images and media records.</p>
    </div>
    <div class="admin-nav">
        <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
        @if(config('license.enabled') && config('license.admin_enabled'))
            <a class="{{ request()->routeIs('admin.licenses.*') ? 'active' : '' }}" href="{{ route('admin.licenses.index') }}">Licenses</a>
        @endif
        <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">Products</a>
        <a class="{{ request()->routeIs('admin.media.*') ? 'active' : '' }}" href="{{ route('admin.media.index') }}">Media</a>
        <a class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">Users</a>
        <a class="{{ request()->routeIs('admin.event-logs.index') ? 'active' : '' }}" href="{{ route('admin.event-logs.index') }}">Logs</a>
        @if (config('admin.servers_enabled'))
            <a class="{{ request()->routeIs('admin.servers.*') ? 'active' : '' }}" href="{{ route('admin.servers.index') }}">Servers</a>
        @endif
        @if(config('license.enabled') && config('license.public_validation'))
            <a class="{{ request()->routeIs('admin.tools.license-validation') ? 'active' : '' }}" href="{{ route('admin.tools.license-validation') }}">License Validation</a>
        @endif
        <a href="{{ route('admin.media.create') }}" class="{{ request()->routeIs('admin.media.create') ? 'active' : '' }}">+ Upload</a>
    </div>
</header>

@if (session('success'))
    <div class="banner success">{{ session('success') }}</div>
@endif

<div class="card">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;">
        @forelse ($media as $m)
            <div style="width:160px;border:1px solid rgba(15,23,42,0.06);padding:0.5rem;border-radius:0.6rem;text-align:center;background:var(--bg);">
                <div style="height:100px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                    <img src="{{ Storage::disk($m->disk)->url($m->path) }}" alt="{{ $m->filename }}" style="max-width:100%;max-height:100%;object-fit:contain;" />
                </div>
                <div style="margin-top:0.5rem;font-size:0.85rem;color:var(--muted);">{{ $m->filename }}</div>
                <div style="margin-top:0.5rem;display:flex;justify-content:center;gap:0.5rem;">
                    <form method="POST" action="{{ route('admin.media.destroy', $m) }}" onsubmit="return confirm('Delete this media?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Delete" aria-label="Delete {{ $m->filename }}" style="background:var(--error);color:#fff;padding:0.45rem 0.8rem;border-radius:0.6rem;border:none;cursor:pointer;font-weight:600;">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div style="padding:2rem;color:var(--muted);">No media uploaded yet.</div>
        @endforelse
    </div>

    <div style="margin-top:1rem;">{{ $media->links() }}</div>
</div>
@endsection
