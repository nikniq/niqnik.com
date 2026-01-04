@csrf
<div class="stack" style="display:grid;gap:1rem;">
    <label>
        <span>Name</span>
        <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required>
    </label>
    <label>
        <span>Product code</span>
        <input type="text" name="product_code" value="{{ old('product_code', $product->product_code ?? '') }}" required>
    </label>
    <div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
        <label>
            <span>Vendor</span>
            <input type="text" name="vendor" value="{{ old('vendor', $product->vendor ?? '') }}">
        </label>
        <label>
            <span>Product URL (admin only)</span>
            <input type="url" name="url" value="{{ old('url', $product->url ?? '') }}" placeholder="https://example.com/product-page">
        </label>
        <label>
            <span>Category</span>
            <input type="text" name="category" value="{{ old('category', $product->category ?? '') }}">
        </label>
        <label>
            <span>Price (USD per seat)</span>
            <input type="number" name="price" min="0" step="0.01" value="{{ old('price', $product->price ?? '0.00') }}" required>
        </label>
        <label>
            <span>License duration (months)</span>
            <input type="number" name="duration_months" min="1" max="60" value="{{ old('duration_months', $product->duration_months ?? 12) }}" required>
        </label>
    </div>
    <label>
        <span>Description</span>
        <textarea name="description" rows="4" style="width:100%;border:1px solid rgba(15,23,42,0.15);border-radius:0.9rem;padding:0.85rem 1rem;font-size:1rem;">{{ old('description', $product->description ?? '') }}</textarea>
    </label>

    <label>
        <span>Product image</span>
        <div style="display:flex;gap:1rem;align-items:center;">
            <select name="media_id" id="media-select" style="min-width:260px;">
                <option value="">-- choose image (optional) --</option>
                @foreach(($media ?? []) as $m)
                    <option value="{{ $m->id }}" data-url="{{ Storage::disk($m->disk)->url($m->path) }}" {{ (int)old('media_id', $product->media_id ?? 0) === $m->id ? 'selected' : '' }}>{{ $m->filename }}</option>
                @endforeach
            </select>
            <div style="width:84px;height:84px;border:1px solid rgba(15,23,42,0.06);display:flex;align-items:center;justify-content:center;background:var(--bg);">
                <img id="media-preview" src="{{ optional($product->media)->path ? Storage::disk(optional($product->media)->disk)->url(optional($product->media)->path) : '' }}" alt="preview" style="max-width:100%;max-height:100%;object-fit:contain;" />
            </div>
            <a class="link" href="{{ route('admin.media.create') }}">Upload new</a>
        </div>
    </label>
</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
    <button type="submit">{{ $submitLabel }}</button>
    <a class="link" href="{{ route('admin.products.index') }}" style="display:inline-flex;align-items:center;justify-content:center;padding:0.65rem 1rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('media-select');
    var preview = document.getElementById('media-preview');
    if (!select) return;
    select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        var url = opt ? opt.dataset.url : '';
        if (url) preview.src = url; else preview.src = '';
    });
});
</script>
@endpush
