<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\StoreManagement\Http\Controllers\StoreManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('storemanagements', StoreManagementController::class)->names('storemanagement');
});
