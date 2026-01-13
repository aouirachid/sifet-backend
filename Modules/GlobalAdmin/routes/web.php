<?php

use Illuminate\Support\Facades\Route;
use Modules\GlobalAdmin\Http\Controllers\GlobalAdminController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('globaladmins', GlobalAdminController::class)->names('globaladmin');
});
