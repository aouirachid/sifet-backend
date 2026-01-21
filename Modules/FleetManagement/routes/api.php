<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\FleetManagement\Http\Controllers\FleetManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('fleetmanagements', FleetManagementController::class)->names('fleetmanagement');
});
