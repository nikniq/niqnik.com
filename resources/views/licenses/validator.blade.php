@extends('layouts.app')

@section('title', 'Validate License · '.$license->identifier)

@section('content')
@php($shareUrl = $license->public_validator_uri)
<header class="hero">
    <div>
        <p class="eyebrow">External validator</p>
        <h1 style="margin-bottom:0.5rem;">License {{ $license->identifier }}</h1>
        <p class="lead" style="max-width:40rem;">Share this page with vendors or auditors so they can call the same validation API you use in production without signing into the dashboard.</p>
    </div>
    <div style="min-width:16rem;max-width:22rem;width:100%;display:flex;flex-direction:column;gap:0.5rem;">
        <label style="font-size:0.85rem;color:var(--muted);">Shareable link</label>
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <input id="share-link" type="text" value="{{ $shareUrl }}" readonly style="flex:1;border-radius:0.75rem;padding:0.5rem 0.75rem;font-family:monospace;font-size:0.85rem;">
            <button id="copy-link" type="button" style="white-space:nowrap;">Copy</button>
        </div>
        @if(config('apilab.enabled'))
        <a class="link" href="{{ url('/api-lab') }}" style="font-weight:600;">Try another license →</a>
        @endif
    </div>
</header>

<section class="card">
    <h2 style="margin-top:0;">License snapshot</h2>
    <dl class="details">
        <div>
            <dt>Product</dt>
            <dd>{{ $license->product->name ?? '—' }}</dd>
        </div>
        <div>
            <dt>Product code</dt>
            <dd style="font-family:monospace;">{{ $license->product->product_code ?? '—' }}</dd>
        </div>
        <div>
            <dt>Seats</dt>
            <dd>{{ $license->seats_total }}</dd>
        </div>
        <div>
            <dt>Expires</dt>
            <dd>{{ $license->expires_at ? $license->expires_at->format('M j, Y') : 'No expiry' }}</dd>
        </div>
    </dl>
</section>

<section class="card" id="validator-card" data-license-code="{{ $license->identifier }}">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div>
            <p class="eyebrow" style="margin-bottom:0.35rem;">Live result</p>
            <h2 style="margin:0;">API validation</h2>
        </div>
        <button id="revalidate" type="button">Revalidate</button>
    </div>
    <div id="status-wrapper" style="margin-top:1rem;display:flex;gap:0.5rem;align-items:center;">
        <span>Status:</span>
        <span id="status-pill" style="border-radius:999px;padding:0.35rem 0.85rem;font-weight:600;background:rgba(15,23,42,0.08);color:var(--muted);">Pending</span>
    </div>
    <pre id="result-json" style="margin-top:1rem;background:var(--bg);padding:1rem;border-radius:0.75rem;overflow:auto;min-height:8rem;">Waiting for API response…</pre>
    <div id="error-card" style="display:none;margin-top:1rem;padding:0.75rem 1rem;border-radius:0.75rem;background:rgba(220,38,38,0.12);color:var(--error);font-weight:600;"></div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const card = document.getElementById('validator-card');
    if (!card) {
        return;
    }

    const licenseCode = card.dataset.licenseCode;
    const apiUrl = '{{ url('/api/licenses/validate') }}';
    const statusPill = document.getElementById('status-pill');
    const resultJson = document.getElementById('result-json');
    const errorCard = document.getElementById('error-card');
    const revalidateButton = document.getElementById('revalidate');
    const copyButton = document.getElementById('copy-link');
    const shareInput = document.getElementById('share-link');

    const setStatus = (label, theme) => {
        statusPill.textContent = label;
        if (theme === 'success') {
            statusPill.style.background = 'rgba(22,163,74,0.15)';
            statusPill.style.color = 'var(--success)';
        } else if (theme === 'error') {
            statusPill.style.background = 'rgba(220,38,38,0.15)';
            statusPill.style.color = 'var(--error)';
        } else {
            statusPill.style.background = 'rgba(15,23,42,0.08)';
            statusPill.style.color = 'var(--muted)';
        }
    };

    const validate = async () => {
        setStatus('Validating…', 'pending');
        errorCard.style.display = 'none';
        resultJson.textContent = 'Contacting API…';

        try {
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ license_code: licenseCode }),
            });

            const json = await response.json();
            resultJson.textContent = JSON.stringify(json, null, 2);

            if (!response.ok) {
                const reason = json?.reason || json?.message || 'API rejected this license.';
                throw new Error(reason);
            }

            if (json.valid) {
                setStatus('VALID', 'success');
            } else {
                setStatus('INVALID', 'error');
                errorCard.textContent = json.reason ? 'License invalid: ' + json.reason : 'License invalid.';
                errorCard.style.display = 'block';
            }
        } catch (error) {
            setStatus('ERROR', 'error');
            resultJson.textContent = '';
            errorCard.textContent = error.message || 'Unable to reach the API.';
            errorCard.style.display = 'block';
        }
    };

    if (revalidateButton) {
        revalidateButton.addEventListener('click', validate);
    }

    if (copyButton && shareInput) {
        copyButton.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(shareInput.value);
                copyButton.textContent = 'Copied!';
                setTimeout(() => {
                    copyButton.textContent = 'Copy';
                }, 1500);
            } catch (error) {
                copyButton.textContent = 'Press ⌘+C';
            }
        });
    }

    validate();
})();
</script>
@endpush
