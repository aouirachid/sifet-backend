<?php

use Illuminate\Support\Facades\Route;
use Modules\StoreManagement\Http\Controllers\StoreManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('storemanagements', StoreManagementController::class)->names('storemanagement');
});
