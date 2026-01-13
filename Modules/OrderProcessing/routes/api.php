<?php

use Illuminate\Support\Facades\Route;
use Modules\OrderProcessing\Http\Controllers\OrderProcessingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('orderprocessings', OrderProcessingController::class)->names('orderprocessing');
});
