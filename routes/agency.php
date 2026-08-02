<?php

use App\Http\Controllers\Agency\AgencyListingController;
use App\Http\Controllers\Agency\AgencyProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:agency'])
    ->prefix('agency')
    ->name('agency.')
    ->group(function () {
        Route::get('/setup', [AgencyProfileController::class, 'create'])->name('setup');
        Route::post('/setup', [AgencyProfileController::class, 'store'])->name('setup.store');

        Route::middleware('agency.exists')->group(function () {
            Route::get('/', [AgencyProfileController::class, 'show'])->name('index');
            Route::get('/edit', [AgencyProfileController::class, 'edit'])->name('edit');
            Route::put('/', [AgencyProfileController::class, 'update'])->name('update');

            Route::get('/listings/create', [AgencyListingController::class, 'create'])->name('listings.create');
            Route::post('/listings', [AgencyListingController::class, 'store'])->name('listings.store');
            Route::get('/listings/{listing}/edit', [AgencyListingController::class, 'edit'])->name('listings.edit');
            Route::put('/listings/{listing}', [AgencyListingController::class, 'update'])->name('listings.update');
            Route::delete('/listings/{listing}', [AgencyListingController::class, 'destroy'])->name('listings.destroy');
        });
    });
