<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
use Illuminate\Support\Facades\DB;

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
