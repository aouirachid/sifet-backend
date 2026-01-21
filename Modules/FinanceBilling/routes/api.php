<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FinanceBilling\Http\Controllers\FinanceBillingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('financebillings', FinanceBillingController::class)->names('financebilling');
});
