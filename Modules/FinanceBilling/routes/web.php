<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FinanceBilling\Http\Controllers\FinanceBillingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('financebillings', FinanceBillingController::class)->names('financebilling');
});
