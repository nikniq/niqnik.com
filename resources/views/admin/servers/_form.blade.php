@csrf

<div class="stack" style="gap:1rem;">
    <div class="field">
        <label for="name">Name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $server->name ?? '') }}" required>
    </div>

    <div class="field">
        <label for="hostname">Hostname</label>
        <input id="hostname" name="hostname" type="text" value="{{ old('hostname', $server->hostname ?? '') }}" required>
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" {{ old('status', $server->status ?? '') === $status ? 'selected' : '' }}>
                    {{ ucfirst($status) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="field">
        <label for="environment">Environment</label>
        <input id="environment" name="environment" type="text" value="{{ old('environment', $server->environment ?? '') }}" placeholder="production, staging, etc." maxlength="64">
    </div>

    <div class="field">
        <label for="last_seen_at">Last seen at</label>
        <input id="last_seen_at" name="last_seen_at" type="datetime-local" value="{{ old('last_seen_at', optional($server->last_seen_at ?? null)->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="field">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3" placeholder="Operational notes or runbooks...">{{ old('notes', $server->notes ?? '') }}</textarea>
    </div>

    <div>
        <button type="submit" class="button">{{ $submitLabel ?? 'Save server' }}</button>
    </div>
</div>
