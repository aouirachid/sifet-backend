<?php

use Illuminate\Support\Facades\Route;
use Modules\FleetManagement\Http\Controllers\FleetManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('fleetmanagements', FleetManagementController::class)->names('fleetmanagement');
});
