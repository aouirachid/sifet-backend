<?php

use Illuminate\Support\Facades\Route;
use Modules\GlobalAdmin\Http\Controllers\GlobalAdminController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('globaladmins', GlobalAdminController::class)->names('globaladmin');
});
