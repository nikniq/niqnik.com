@csrf

<div class="stack" style="gap:1rem;">
    <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $client->name ?? '') }}" required>
    </div>

    <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" name="slug" type="text" value="{{ old('slug', $client->slug ?? '') }}" required placeholder="chopper" pattern="[a-z0-9-]+">
        <small class="field-help">Used in API paths: <code>/api/games/{slug}/events</code>.</small>
    </div>

    <div class="field">
        <label for="product_id">Linked product (optional)</label>
        <select id="product_id" name="product_id">
            <option value="">— none —</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" {{ (string) old('product_id', $client->product_id ?? '') === (string) $product->id ? 'selected' : '' }}>
                    {{ $product->name }} ({{ $product->product_code }})
                </option>
            @endforeach
        </select>
        <small class="field-help">Ties the client to a games-catalogue product so the dashboard can show its thumbnail and play link.</small>
    </div>

    <div class="field">
        <label for="homepage_url">Play URL (optional)</label>
        <input id="homepage_url" name="homepage_url" type="url" value="{{ old('homepage_url', $client->homepage_url ?? '') }}" placeholder="https://chopper.nikniq.com">
    </div>

    <div class="field">
        <label for="redirect_uris">Redirect URIs</label>
        @php
            $uris = old('redirect_uris', $client->redirect_uris ?? []);
            $uris = is_array($uris) ? implode("\n", $uris) : $uris;
        @endphp
        <textarea id="redirect_uris" name="redirect_uris" rows="3" required placeholder="https://chopper.nikniq.com/auth/callback">{{ $uris }}</textarea>
        <small class="field-help">One full URL per line. The game must send exactly one of these as <code>redirect_uri</code>.</small>
    </div>

    <div class="field">
        <label style="display:flex;align-items:center;gap:0.5rem;">
            <input type="checkbox" name="is_public" value="1" style="width:auto;" {{ old('is_public', $client->is_public ?? false) ? 'checked' : '' }}>
            Public client (browser-only game, uses PKCE instead of the secret)
        </label>
    </div>

    <div class="field">
        <label style="display:flex;align-items:center;gap:0.5rem;">
            <input type="checkbox" name="is_enabled" value="1" style="width:auto;" {{ old('is_enabled', $client->is_enabled ?? true) ? 'checked' : '' }}>
            Enabled
        </label>
    </div>

    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="2">{{ old('notes', $client->notes ?? '') }}</textarea>
    </div>

    <div>
        <button type="submit" class="button">{{ $submitLabel ?? 'Save game client' }}</button>
    </div>
</div>
