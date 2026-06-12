<?php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');

Route::get('/listings/{listing:slug}', [ListingController::class, 'show'])
    ->middleware('auth')
    ->name('listings.show');
