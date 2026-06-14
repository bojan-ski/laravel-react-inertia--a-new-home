<?php

use App\Http\Controllers\Agency\AgencyProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:agency'])
    ->prefix('agency')
    ->name('agency.')
    ->group(function () {
        Route::get('/setup', [AgencyProfileController::class, 'create'])->name('setup');
        Route::post('/setup', [AgencyProfileController::class, 'store'])->name('setup.store');

        Route::get('/', [AgencyProfileController::class, 'show'])->name('index');
        Route::get('/edit', [AgencyProfileController::class, 'edit'])->name('edit');
        Route::put('/', [AgencyProfileController::class, 'update'])->name('update');
    });
