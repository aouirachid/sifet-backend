<?php

use Illuminate\Support\Facades\Route;
use Modules\OrderProcessing\Http\Controllers\OrderProcessingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('orderprocessings', OrderProcessingController::class)->names('orderprocessing');
});
