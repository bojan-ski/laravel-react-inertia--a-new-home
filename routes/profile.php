<?php

use App\Http\Controllers\ListingImageController;
use App\Http\Controllers\MyListingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:regular_user'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('/profile/settings', [ProfileSettingsController::class, 'edit'])->name('profile.settings.edit');
    Route::put('/profile/settings/password', [ProfileSettingsController::class, 'updatePassword'])->middleware('throttle:password-confirm')->name('profile.settings.password.update');
    Route::delete('/profile', [ProfileSettingsController::class, 'destroy'])->middleware('throttle:password-confirm')->name('profile.destroy');

    Route::get('/my-listings/create', [MyListingController::class, 'create'])->name('my-listings.create');
    Route::post('/my-listings', [MyListingController::class, 'store'])->middleware('throttle:listing-write')->name('my-listings.store');
    Route::get('/my-listings/{listing}/edit', [MyListingController::class, 'edit'])->name('my-listings.edit');
    Route::put('/my-listings/{listing}', [MyListingController::class, 'update'])->middleware('throttle:listing-write')->name('my-listings.update');
    Route::delete('/my-listings/{listing}', [MyListingController::class, 'destroy'])->name('my-listings.destroy');
    Route::post('/my-listings/{listing}/close', [MyListingController::class, 'close'])->middleware('throttle:listing-write')->name('my-listings.close');
    Route::post('/my-listings/{listing}/reopen', [MyListingController::class, 'reopen'])->middleware('throttle:listing-write')->name('my-listings.reopen');
    Route::post('/my-listings/{listing}/renew', [MyListingController::class, 'renew'])->middleware('throttle:listing-write')->name('my-listings.renew');

    Route::post('/my-listings/{listing}/images', [ListingImageController::class, 'store'])->middleware('throttle:listing-images')->name('my-listings.images.store');
    Route::delete('/my-listings/{listing}/images/{image}', [ListingImageController::class, 'destroy'])->name('my-listings.images.destroy');
});
