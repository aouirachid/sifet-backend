<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/debug-db', function () {
    try {
        $databaseName = DB::connection()->getDatabaseName();

        return response()->json([
            'status' => 'success',
            'database' => $databaseName,
            'connection' => config('database.default'),
            'host' => config('database.connections.'.config('database.default').'.host'),
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

// Authentication routes
Route::prefix('auth')->group(function () {
    // Landlord authentication
    Route::post('landlord/login', function (Request $request) {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('landlord')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('landlord')->factory()->getTTL() * 60,
        ]);
    });

    // Tenant authentication
    Route::post('tenant/login', function (Request $request) {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('tenant')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('tenant')->factory()->getTTL() * 60,
        ]);
    });

    // Regular API authentication
    Route::post('login', function (Request $request) {
        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    });

    // Refresh token
    Route::post('refresh', function () {
        // Try to get the authenticated guard
        $guard = null;
        foreach (['landlord', 'tenant', 'api'] as $guardName) {
            if (auth($guardName)->check()) {
                $guard = $guardName;
                break;
            }
        }

        return response()->json([
            'access_token' => auth($guard)->refresh(),
            'token_type' => 'bearer',
            'expires_in' => auth($guard)->factory()->getTTL() * 60,
        ]);
    });

    // Logout
    Route::post('logout', function () {
        // Try to get the authenticated guard
        foreach (['landlord', 'tenant', 'api'] as $guardName) {
            if (auth($guardName)->check()) {
                auth($guardName)->logout();
                break;
            }
        }

        return response()->json(['message' => 'Successfully logged out']);
    });

    // Current user
    Route::get('me', function () {
        // Try to get the authenticated user from any guard
        foreach (['landlord', 'tenant', 'api'] as $guardName) {
            if ($user = auth($guardName)->user()) {
                return response()->json($user);
            }
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    });
});

// Protected landlord routes
Route::prefix('landlord')->middleware('auth:landlord')->group(function () {
    Route::get('protected', function () {
        return response()->json(['user' => auth('landlord')->user()->email]);
    });
});

// Protected tenant routes
Route::prefix('tenant')->middleware('auth:tenant')->group(function () {
    Route::get('protected', function () {
        return response()->json(['user' => auth('tenant')->user()->email]);
    });

    Route::get('check-context', function () {
        return response()->json([
            'tenant_id' => app('currentTenant')?->id ?? null,
        ]);
    });
});

// Protected user routes
Route::prefix('user')->middleware('auth:api')->group(function () {
    Route::get('protected', function () {
        return response()->json(['user' => auth('api')->user()->email]);
    });
});
