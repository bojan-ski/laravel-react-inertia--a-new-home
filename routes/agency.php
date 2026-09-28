<?php

use App\Http\Controllers\Agency\AgencyListingController;
use App\Http\Controllers\Agency\AgencyProfileController;
use App\Http\Controllers\Agency\AgencySettingsController;
use App\Http\Controllers\ListingImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:agency'])
    ->prefix('agency')
    ->name('agency.')
    ->group(function () {
        Route::get('/setup', [AgencyProfileController::class, 'create'])->name('setup');
        Route::post('/setup', [AgencyProfileController::class, 'store'])->name('setup.store');

        Route::get('/settings', [AgencySettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/password', [AgencySettingsController::class, 'updatePassword'])->middleware('throttle:password-confirm')->name('settings.password.update');
        Route::delete('/account', [AgencySettingsController::class, 'destroy'])->middleware('throttle:password-confirm')->name('account.destroy');

        Route::middleware('agency.exists')->group(function () {
            Route::get('/', [AgencyProfileController::class, 'show'])->name('index');
            Route::get('/edit', [AgencyProfileController::class, 'edit'])->name('edit');
            Route::put('/', [AgencyProfileController::class, 'update'])->name('update');

            Route::get('/listings/create', [AgencyListingController::class, 'create'])->name('listings.create');
            Route::post('/listings', [AgencyListingController::class, 'store'])->middleware('throttle:listing-write')->name('listings.store');
            Route::get('/listings/{listing}/edit', [AgencyListingController::class, 'edit'])->name('listings.edit');
            Route::put('/listings/{listing}', [AgencyListingController::class, 'update'])->middleware('throttle:listing-write')->name('listings.update');
            Route::delete('/listings/{listing}', [AgencyListingController::class, 'destroy'])->name('listings.destroy');
            Route::post('/listings/{listing}/close', [AgencyListingController::class, 'close'])->middleware('throttle:listing-write')->name('listings.close');
            Route::post('/listings/{listing}/reopen', [AgencyListingController::class, 'reopen'])->middleware('throttle:listing-write')->name('listings.reopen');
            Route::post('/listings/{listing}/renew', [AgencyListingController::class, 'renew'])->middleware('throttle:listing-write')->name('listings.renew');

            Route::post('/listings/{listing}/images', [ListingImageController::class, 'store'])->middleware('throttle:listing-images')->name('listings.images.store');
            Route::delete('/listings/{listing}/images/{image}', [ListingImageController::class, 'destroy'])->name('listings.images.destroy');
        });
    });
