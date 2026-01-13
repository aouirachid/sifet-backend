<?php

use Illuminate\Support\Facades\Route;
use Modules\CompanyManagement\Http\Controllers\CompanyManagementController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('companymanagements', CompanyManagementController::class)->names('companymanagement');
});
