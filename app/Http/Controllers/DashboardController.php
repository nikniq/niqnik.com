<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Models\Product;
use App\Models\Favourite;
use App\Models\PlayerGameStat;
use App\Services\GameStatsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private GameStatsService $gameStatsService)
    {
    }

    public function index(): View
    {
        $licenseEnabled = (bool) config('license.enabled');
        $purchaseEnabled = $licenseEnabled && (bool) config('license.purchase_enabled');

        $favorites = collect();
        if ($user = Auth::user()) {
            $favorites = Favourite::with('favoritable.media')
                ->where('user_id', $user->id)
                ->where('favoritable_type', Product::class)
                ->get()
                ->map(fn($f) => $f->favoritable)
                ->filter();
        }

        $gameStats = collect();
        $ranks = [];
        if (config('sso.enabled') && Auth::check()) {
            $gameStats = PlayerGameStat::with('client.product')
                ->where('user_id', Auth::id())
                ->whereHas('client', fn ($q) => $q->where('is_enabled', true))
                ->orderByDesc('last_played_at')
                ->get();
            foreach ($gameStats as $row) {
                $ranks[$row->game_client_id] = $this->gameStatsService->rankFor($row->client, Auth::user());
            }
        }

        return view('dashboard', [
            'gameStats' => $gameStats,
            'ranks' => $ranks,
            'user' => Auth::user(),
            'licenses' => $licenseEnabled
                ? License::with(['product', 'domains'])->where('user_id', Auth::id())->orderBy('expires_at')->get()
                : collect(),
            'products' => $purchaseEnabled ? Product::orderBy('name')->get() : collect(),
            'paypalClientId' => config('paypal.client_id'),
            'paypalCurrency' => config('paypal.currency', 'USD'),
            'stripePublicKey' => config('stripe.public_key'),
            'stripeCurrency' => config('stripe.currency', 'USD'),
            'paypalEnabled' => $purchaseEnabled && (bool) (config('payment.providers.paypal.enabled') && config('paypal.client_id')),
            'stripeEnabled' => $purchaseEnabled && (bool) (config('payment.providers.stripe.enabled') && config('stripe.public_key') && config('stripe.secret')),
            'favorites' => $favorites,
        ]);
    }
}
