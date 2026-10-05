<?php

use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\SavedSearchUnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'not-suspended'])->group(function () {
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');

    Route::post('/saved-searches', [SavedSearchController::class, 'store'])
        ->middleware('throttle:saved-search')
        ->name('saved-searches.store');

    Route::patch('/saved-searches/{savedSearch}', [SavedSearchController::class, 'update'])
        ->middleware('throttle:saved-search')
        ->name('saved-searches.update');

    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])
        ->middleware('throttle:saved-search')
        ->name('saved-searches.destroy');
});

Route::middleware('signed')->group(function () {
    Route::get('/saved-searches/unsubscribe/{user}', [SavedSearchUnsubscribeController::class, 'show'])
        ->name('saved-searches.unsubscribe.show');

    Route::post('/saved-searches/unsubscribe/{user}', [SavedSearchUnsubscribeController::class, 'store'])
        ->name('saved-searches.unsubscribe');
});
