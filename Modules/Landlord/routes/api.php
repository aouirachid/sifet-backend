<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Landlord\Http\Controllers\LandlordAuthController;

/*
|--------------------------------------------------------------------------
| Landlord API routes (prefix: /api, then v1 in group)
| POST /api/v1/landlord/login — throttle 5 attempts per minute
| Protected routes use auth:landlord middleware (e.g. in route group).
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Login: public, rate-limited (5 attempts per minute)
    Route::post('landlord/login', [LandlordAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('landlord.login');

    // Example protected route (guard isolation: only landlord JWT accepted)
    // Route::middleware('auth:landlord')->group(function () {
    //     Route::get('landlord/me', fn () => response()->json(auth('landlord')->user()));
    // });
});
