<?php

use App\Http\Controllers\Api\EventLogReceiverController;
use App\Http\Controllers\Api\LicenseValidationController;
use App\Http\Controllers\Api\GameEventController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\SsoTokenController;
use App\Http\Controllers\LicenseValidatorJsonController;
use Illuminate\Support\Facades\Route;

Route::post('/licenses/validate', LicenseValidationController::class)
	->name('api.licenses.validate');

Route::get('/license/validate/{key}', LicenseValidatorJsonController::class)
	->name('api.license.validate');

if (config('admin.external_logs_enabled')) {
	Route::post('/logs', EventLogReceiverController::class)
		->name('api.logs.store');
}

// Game SSO and player stats. See README "Game SSO & stats".
if (config('sso.enabled')) {
	Route::post('/sso/token', [SsoTokenController::class, 'token'])
		->middleware('throttle:60,1')
		->name('api.sso.token');

	Route::middleware(['player', 'throttle:120,1'])->group(function () {
		Route::get('/sso/me', [SsoTokenController::class, 'me'])->name('api.sso.me');
		Route::post('/sso/logout', [SsoTokenController::class, 'logout'])->name('api.sso.logout');
		Route::post('/games/{game}/events', [GameEventController::class, 'store'])->name('api.games.events.store');
	});

	Route::get('/games/{game}/leaderboard', LeaderboardController::class)
		->middleware('throttle:120,1')
		->name('api.games.leaderboard');
}
