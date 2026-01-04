@csrf
<div class="stack" style="display:grid;gap:1rem;">
    <label>
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required>
    </label>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
    </label>
    <label>
        <span>Admin contact email (optional)</span>
        <input type="email" name="admin_email" value="{{ old('admin_email', $user->admin_email ?? '') }}" placeholder="ops@example.com">
    </label>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
        <label>
            <span>Password {{ isset($user) && $user->exists ? '(leave blank to keep current)' : '' }}</span>
            <input type="password" name="password" {{ isset($user) && $user->exists ? '' : 'required' }}>
        </label>
        <label>
            <span>Confirm password</span>
            <input type="password" name="password_confirmation" {{ isset($user) && $user->exists ? '' : 'required' }}>
        </label>
    </div>
    <label style="display:flex;align-items:center;gap:0.5rem;">
        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}>
        <span>Grant admin access</span>
    </label>
</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <button type="submit">{{ $submitLabel }}</button>
    <a class="link" href="{{ route('admin.users.index') }}" style="display:inline-flex;align-items:center;justify-content:center;padding:0.65rem 1rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);">Cancel</a>
</div>
