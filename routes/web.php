<?php

use App\Http\Controllers\ListingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'landing' => __('app.landing'),
    ]);
})->name('home');

Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');

require __DIR__.'/auth.php';
