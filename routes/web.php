<?php

use App\Http\Controllers\LicenseValidatorJsonController;
use App\Http\Controllers\Admin\LicenseController as AdminLicenseController;
use App\Http\Controllers\Admin\LicenseValidationTestController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\EventLogController;
use App\Http\Controllers\Admin\ExternalLogController;
use App\Http\Controllers\Admin\ServerController as AdminServerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTestController;
use App\Http\Controllers\PayPalOrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicLicenseValidatorController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\UserLicenseController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\WhoisController;
use App\Http\Controllers\WpPostController;
use App\Http\Controllers\CertController;
use App\Http\Controllers\SubdomainController;
use App\Http\Controllers\Sso\AuthorizeController as SsoAuthorizeController;
use App\Http\Controllers\GameStatsController;
use App\Http\Controllers\Admin\GameClientController as AdminGameClientController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $theme = config('frontpage.theme', 'default');
    $view = 'themes.' . $theme . '.frontpage';
    if (view()->exists($view)) {
        if ($theme === 'games') {
            $category = config('games.category', env('GAMES_CATEGORY', 'Game'));
            $products = \App\Models\Product::with('media')->where('category', $category)->orderBy('name')->get();

            if (auth()->check()) {
                $favIds = \App\Models\Favourite::where('user_id', auth()->id())
                    ->where('favoritable_type', \App\Models\Product::class)
                    ->pluck('favoritable_id')
                    ->toArray();

                if (! empty($favIds)) {
                    $products = $products->sortByDesc(function ($p) use ($favIds) {
                        return in_array($p->id, $favIds) ? 1 : 0;
                    })->values();
                }
            }

            return view($view, ['products' => $products]);
        }

        return view($view);
    }

    return view('home');
})->name('home');
if (config('apilab.enabled')) {
    Route::view('/api-lab', 'api.lab')->name('api.lab');
}

// Whois lookup endpoint used by the whois frontpage theme
Route::post('/whois/lookup', [WhoisController::class, 'lookup'])->middleware('throttle:10,1')->name('whois.lookup');
// Cert lookup endpoint used by the cert frontpage theme
Route::post('/cert/lookup', [CertController::class, 'lookup'])->middleware('throttle:10,1')->name('cert.lookup');
// Subdomains lookup endpoint used by the subdomains frontpage theme
Route::post('/subdomains/lookup', [SubdomainController::class, 'lookup'])->middleware('throttle:10,1')->name('subdomains.lookup');
if (config('license.enabled') && config('license.public_validation')) {
    Route::get('/license/{license_code}', PublicLicenseValidatorController::class)
        ->middleware('throttle:60,1')
        ->name('licenses.validator');
    Route::get('/license/validate/{key}', LicenseValidatorJsonController::class)
        ->middleware('throttle:60,1');
}

if (config('shop.enabled')) {
    Route::get('/shop', [ShopController::class, 'index'])->name('shop');
    Route::get('/shop/{product:product_code}', [ShopController::class, 'show'])->name('shop.products.show');
}

// Games frontpage
if (config('games.enabled')) {
    Route::get('/games', [App\Http\Controllers\GamesController::class, 'index'])->name('games.index');
}

// Game single sign-on: games send players here, and get a one-time code back.
if (config('sso.enabled')) {
    Route::get('/sso/authorize', SsoAuthorizeController::class)
        ->middleware(['auth', 'throttle:30,1'])
        ->name('sso.authorize');
    Route::get('/games/{game}/leaderboard', [GameStatsController::class, 'leaderboard'])
        ->name('games.leaderboard');
}

// WordPress posts index and single post
if (config('posts.enabled')) {
    Route::get('/posts', [WpPostController::class, 'index'])->name('posts.index');
    Route::get('/posts/{slug}', [WpPostController::class, 'show'])->name('posts.show');
}

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/login/two-factor', [LoginController::class, 'showTwoFactorForm'])->name('login.two-factor.show');
    Route::post('/login/two-factor', [LoginController::class, 'verifyTwoFactor'])->middleware('throttle:10,1')->name('login.two-factor.verify');
    Route::post('/login/two-factor/resend', [LoginController::class, 'resendTwoFactor'])->name('login.two-factor.resend');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
});

// Social sign-in. Outside the guest group so a signed-in user can link a
// provider to their account from the profile page.
Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
    ->whereIn('provider', array_keys(config('social.providers', [])))
    ->name('oauth.redirect');
Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
    ->whereIn('provider', array_keys(config('social.providers', [])))
    ->name('oauth.callback');
Route::post('/auth/unlink', [SocialLoginController::class, 'unlink'])
    ->middleware('auth')
    ->name('oauth.unlink');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/logged-out', 'auth.logged-out')->name('logged-out');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    if (config('sso.enabled')) {
        Route::post('/profile/games/revoke-all', [App\Http\Controllers\ConnectedGamesController::class, 'revokeAll'])->name('profile.games.revoke-all');
        Route::post('/profile/games/{game}/revoke', [App\Http\Controllers\ConnectedGamesController::class, 'revoke'])->name('profile.games.revoke');
    }

    // favourites
    Route::post('/favorites', [App\Http\Controllers\FavouriteController::class, 'toggle'])->name('favorites.toggle');
});

if (config('license.enabled') && config('license.purchase_enabled')) {
    Route::post('/dashboard/licenses', [UserLicenseController::class, 'store'])
        ->middleware('auth')
        ->name('licenses.store');
}

Route::post('/paypal/orders', [PayPalOrderController::class, 'store'])
    ->middleware('auth')
    ->name('paypal.orders.store');

Route::post('/stripe/intents', [StripePaymentController::class, 'intent'])
    ->middleware('auth')
    ->name('stripe.intents.create');

Route::post('/stripe/complete', [StripePaymentController::class, 'complete'])
    ->middleware('auth')
    ->name('stripe.complete');

if (config('license.enabled')) {
    Route::get('/dashboard/licenses/{license}', [UserLicenseController::class, 'show'])
        ->middleware('auth')
        ->name('licenses.show');
}

Route::middleware('auth')->group(function () {
    Route::get('/email-test', [EmailTestController::class, 'create'])->name('email.test');
    Route::post('/email-test', [EmailTestController::class, 'store'])->name('email.test.send');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'admin'])
    ->group(function () {
        if (config('license.admin_enabled')) {
            Route::redirect('/', '/admin/licenses')->name('home');
            Route::resource('licenses', AdminLicenseController::class)->except(['show']);
        }
        Route::resource('products', AdminProductController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        if (config('admin.servers_enabled')) {
            Route::resource('servers', AdminServerController::class)->except(['show']);
        }
        if (config('sso.enabled')) {
            Route::resource('game-clients', AdminGameClientController::class)->except(['show']);
            Route::post('game-clients/{game_client}/rotate-secret', [AdminGameClientController::class, 'rotateSecret'])->name('game-clients.rotate-secret');
        }
        Route::get('tools/license-validation', LicenseValidationTestController::class)->name('tools.license-validation');
        Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');
        Route::get('event-logs', [EventLogController::class, 'index'])->name('event-logs.index');
        if (config('admin.external_logs_enabled')) {
            Route::get('external-logs', [ExternalLogController::class, 'index'])->name('external-logs.index');
        }
        // Admin media management
        Route::get('media', [App\Http\Controllers\Admin\MediaController::class, 'index'])->name('media.index');
        Route::get('media/create', [App\Http\Controllers\Admin\MediaController::class, 'create'])->name('media.create');
        Route::post('media', [App\Http\Controllers\Admin\MediaController::class, 'store'])->name('media.store');
        Route::delete('media/{media}', [App\Http\Controllers\Admin\MediaController::class, 'destroy'])->name('media.destroy');
    });
