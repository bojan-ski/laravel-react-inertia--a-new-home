<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');

Route::get('/listings/{listing:slug}', [ListingController::class, 'show'])
    ->middleware(['auth', 'not-suspended'])
    ->name('listings.show');

Route::middleware('auth')->group(function () {
    Route::post('/listings/{listing}/bookmark', [BookmarkController::class, 'toggle'])->name('listings.bookmark');
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->middleware('not-suspended')->name('bookmarks.index');
});
