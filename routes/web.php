<?php

use App\Http\Controllers\AgencyDirectoryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'landing' => __('app.landing'),
    ]);
})->name('home');

Route::get('/agencies', [AgencyDirectoryController::class, 'index'])->name('agencies.index');

require __DIR__.'/listings.php';
require __DIR__.'/profile.php';
require __DIR__.'/agency.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
