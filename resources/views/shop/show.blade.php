@extends('layouts.app')

@section('title', 'Shop · ' . $product->name)

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
        <div style="display:flex;gap:1rem;align-items:center;">
            @php
                $hero = null;
                if (! empty($product->media) && ! empty($product->media->path)) {
                    try { $hero = \Illuminate\Support\Facades\Storage::disk($product->media->disk)->url($product->media->path); } catch (\Exception $e) { $hero = null; }
                }
                if (! $hero) {
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
                    if ($m) { $hero = \Illuminate\Support\Facades\Storage::disk($m->disk)->url($m->path); }
                    else { $hero = asset('assets/games/' . $file); }
                }
            @endphp
            <div style="width:96px;height:96px;border-radius:12px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#fff;border:1px solid rgba(15,23,42,0.04);">
                <img src="{{ $hero }}" alt="{{ $product->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
            </div>
            <div>
                <h1>{{ $product->name }}</h1>
                <p class="lead">{{ $product->description ?: 'No marketing copy available yet.' }}</p>
            </div>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;align-items:center;">
        @if(config('shop.enabled'))
            <a class="link" href="{{ url('/shop') }}" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;">← Back to shop</a>
        @endif
        <a class="link" href="{{ route('login') }}" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;">Purchase in dashboard</a>
    </div>
</header>

<section class="card" style="display:grid;gap:1.25rem;">
    <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
        <div style="display:flex;align-items:flex-end;gap:0.5rem;">
            <span style="font-size:3rem;font-weight:700;">${{ number_format($product->price, 2) }}</span>
            <span style="font-size:1rem;font-weight:600;color:var(--muted);">/seat</span>
        </div>
        <span style="font-weight:600;color:var(--muted);">{{ $product->duration_months }}-month term</span>
        <span style="font-family:monospace;color:var(--muted);">Code: {{ $product->product_code }}</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
        <div style="background:var(--bg);padding:0.9rem 1rem;border-radius:0.85rem;">
            <p style="margin:0;font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);">Vendor</p>
            <p style="margin:0.2rem 0 0;font-weight:600;">{{ $product->vendor ?? '—' }}</p>
        </div>
        <div style="background:var(--bg);padding:0.9rem 1rem;border-radius:0.85rem;">
            <p style="margin:0;font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);">Category</p>
            <p style="margin:0.2rem 0 0;font-weight:600;">{{ $product->category ?? '—' }}</p>
        </div>
    </div>
    <div style="background:var(--bg);padding:1rem 1.1rem;border-radius:0.85rem;">
        <p style="margin:0;font-size:0.8rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--muted);">Product description</p>
        <p style="margin:0.35rem 0 0;line-height:1.6;">{!! $product->description ? nl2br(e($product->description)) : 'No marketing copy available yet.' !!}</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.5rem;">
        <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ route('register') }}">Need an account? Sign up</a>
        @if(config('apilab.enabled'))
        <a class="link" style="display:block;text-align:center;padding:0.65rem 0.9rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.9rem;background:#fff;box-shadow:0 6px 18px rgba(15,23,42,0.08);font-weight:600;" href="{{ url('/api-lab') }}">Validate via API Lab</a>
        @endif
    </div>
</section>

@auth
    @if ($paypalEnabled)
    <section class="card" style="margin-top:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <p class="eyebrow" style="margin-bottom:0.35rem;">Purchase</p>
                <h2 style="margin:0;">Buy seats for {{ $product->name }}</h2>
            </div>
            <span style="font-size:0.9rem;color:var(--muted);">Charged in dashboard currency (USD)</span>
        </div>

        <form method="POST" action="{{ route('licenses.store') }}" style="display:grid;gap:1rem;margin-top:1.25rem;" id="shop-purchase-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="seats_total" id="shop-seats-input" value="1">
            <label>
                <span>Primary domain (optional)</span>
                <input type="text" name="domain" placeholder="acme.com" value="{{ old('domain') }}">
            </label>
            <div style="padding:0.75rem 1rem;background:var(--bg);border-radius:0.75rem;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>
                    Estimated total
                    <small style="display:block;font-weight:400;color:var(--muted);">Renews every {{ $product->duration_months }} months</small>
                </span>
                <span id="shop-purchase-total">$0.00</span>
            </div>
            <input type="hidden" name="paypal_order_id" id="shop-paypal-order">
            <p style="margin:0;color:var(--muted);font-size:0.95rem;">Checkout is powered by PayPal. Each purchase provides one license seat—approve the popup to finish.</p>
            <div style="padding:0.5rem;border:1px solid rgba(15,23,42,0.08);border-radius:0.85rem;background:#fff;box-shadow:0 8px 20px rgba(15,23,42,0.06);">
                <div id="paypal-buttons-shop"></div>
                <p style="margin:0.35rem 0 0;color:var(--muted);font-size:0.9rem;">Pay with PayPal</p>
            </div>
            <p id="paypal-errors-shop" style="display:none;color:var(--error);font-weight:600;"></p>
            @error('payment')
                <p style="color:var(--error);font-weight:600;">{{ $message }}</p>
            @enderror
            @if (! $paypalClientId)
                <p style="color:var(--error);font-weight:600;">Set PAYPAL_CLIENT_ID and PAYPAL_SECRET in your environment file to enable checkout.</p>
            @endif
        </form>
    </section>
    @endif

    @if ($stripeEnabled)
    <section class="card" style="margin-top:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
            <div>
                <p class="eyebrow" style="margin-bottom:0.35rem;">Card checkout</p>
                <h2 style="margin:0;">Pay with Stripe</h2>
            </div>
            <span style="font-size:0.9rem;color:var(--muted);">Secured by Stripe Elements</span>
        </div>

        <form method="POST" action="{{ route('stripe.complete') }}" style="display:grid;gap:1rem;margin-top:1.25rem;" id="shop-stripe-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="payment_intent_id" id="shop-stripe-payment-intent">
            <label>
                <span>Primary domain (optional)</span>
                <input type="text" name="domain" id="shop-stripe-domain" placeholder="acme.com" value="{{ old('domain') }}">
            </label>
            <div style="padding:0.75rem 1rem;background:var(--bg);border-radius:0.75rem;font-weight:600;display:flex;justify-content:space-between;align-items:center;">
                <span>
                    Estimated total
                    <small style="display:block;font-weight:400;color:var(--muted);">Renews every {{ $product->duration_months }} months</small>
                </span>
                <span id="shop-stripe-total">${{ number_format($product->price, 2) }}</span>
            </div>
            <div id="shop-stripe-card" style="padding:0.75rem 1rem;border:1px solid rgba(15,23,42,0.12);border-radius:0.75rem;background:#fff;"></div>
            <button type="submit" id="shop-stripe-submit" style="padding:0.75rem 1rem;border:none;border-radius:0.75rem;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;">Pay with card</button>
            <p id="shop-stripe-errors" style="display:none;color:var(--error);font-weight:600;"></p>
            @if (! $stripePublicKey)
                <p style="color:var(--error);font-weight:600;">Set STRIPE_PUBLIC_KEY and STRIPE_SECRET to enable Stripe checkout.</p>
            @endif
        </form>
    </section>
    @endif
@endauth
@endif
@endsection

@push('scripts')
@if(config('shop.enabled'))
    @if ($paypalEnabled && $paypalClientId)
        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency={{ $paypalCurrency ?? 'USD' }}" data-sdk-integration-source="button-factory"></script>
        <script>
        (function () {
            const form = document.getElementById('shop-purchase-form');
            const total = document.getElementById('shop-purchase-total');
            const seatsInput = document.getElementById('shop-seats-input');
            const domainInput = form ? form.querySelector('input[name="domain"]') : null;
            const paypalOrderInput = document.getElementById('shop-paypal-order');
            const paypalErrors = document.getElementById('paypal-errors-shop');
            const price = parseFloat('{{ number_format($product->price, 2, '.', '') }}');

            if (!form || !window.paypal) {
                return;
            }

            const showError = (message) => {
                if (paypalErrors) {
                    paypalErrors.textContent = message;
                    paypalErrors.style.display = 'block';
                }
            };

            const clearError = () => {
                if (paypalErrors) {
                    paypalErrors.textContent = '';
                    paypalErrors.style.display = 'none';
                }
            };

            const updateTotal = () => {
                const seats = seatsInput ? parseInt(seatsInput.value || '1', 10) : 1;
                const computed = Number.isFinite(price) ? price * (Number.isFinite(seats) && seats > 0 ? seats : 1) : 0;
                if (total) {
                    total.textContent = `$${computed.toFixed(2)}`;
                }
            };

            if (seatsInput) {
                seatsInput.addEventListener('change', updateTotal);
                seatsInput.addEventListener('input', updateTotal);
            }

            updateTotal();

            const paypalContainer = document.getElementById('paypal-buttons-shop');
            if (!paypalContainer) {
                return;
            }

            window.paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                },
                createOrder: async () => {
                    clearError();

                    const payload = {
                        product_id: form.querySelector('input[name="product_id"]').value,
                        seats_total: seatsInput ? parseInt(seatsInput.value || '1', 10) : 1,
                        domain: domainInput ? domainInput.value : null,
                    };

                    const response = await fetch('{{ route('paypal.orders.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to create a PayPal order.');
                    }

                    if (paypalOrderInput) {
                        paypalOrderInput.value = data.order_id;
                    }

                    return data.order_id;
                },
                onApprove: () => {
                    clearError();
                    form.submit();
                },
                onError: (err) => {
                    showError(err && err.message ? err.message : 'PayPal error.');
                },
            }).render(paypalContainer);
        })();
        </script>
    @endif

    @if ($stripeEnabled && $stripePublicKey)
        <script src="https://js.stripe.com/v3/"></script>
        <script>
        (function () {
            const stripe = Stripe('{{ $stripePublicKey }}');
            const elements = stripe.elements();
            const card = elements.create('card');

            const form = document.getElementById('shop-stripe-form');
            const domainInput = document.getElementById('shop-stripe-domain');
            const intentInput = document.getElementById('shop-stripe-payment-intent');
            const errorEl = document.getElementById('shop-stripe-errors');
            const submitBtn = document.getElementById('shop-stripe-submit');

            if (!form) {
                return;
            }

            card.mount('#shop-stripe-card');

            const showError = (message) => {
                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.style.display = 'block';
                }
            };

            const clearError = () => {
                if (errorEl) {
                    errorEl.textContent = '';
                    errorEl.style.display = 'none';
                }
            };

            const setLoading = (loading) => {
                if (!submitBtn) return;
                submitBtn.disabled = loading;
                submitBtn.textContent = loading ? 'Processing…' : 'Pay with card';
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                clearError();
                setLoading(true);

                try {
                    const payload = {
                        product_id: form.querySelector('input[name="product_id"]').value,
                        domain: domainInput ? domainInput.value : null,
                    };

                    const response = await fetch('{{ route('stripe.intents.create') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to create a Stripe payment.');
                    }

                    const result = await stripe.confirmCardPayment(data.client_secret, {
                        payment_method: {
                            card,
                        },
                    });

                    if (result.error) {
                        throw new Error(result.error.message || 'Card payment failed.');
                    }

                    if (result.paymentIntent && result.paymentIntent.status === 'succeeded') {
                        if (intentInput) {
                            intentInput.value = data.payment_intent_id;
                        }
                        form.submit();
                        return;
                    }

                    throw new Error('Stripe did not complete the payment.');
                } catch (err) {
                    showError(err && err.message ? err.message : 'Card payment failed.');
                } finally {
                    setLoading(false);
                }
            });
        })();
        </script>
    @endif
@endif
@endpush
