<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyManagement\Http\Controllers\CompanyManagementController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('companymanagements', CompanyManagementController::class)->names('companymanagement');
});
