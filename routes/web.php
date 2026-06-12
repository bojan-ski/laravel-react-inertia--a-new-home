<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'landing' => __('app.landing'),
    ]);
})->name('home');

require __DIR__.'/listings.php';
require __DIR__.'/auth.php';
