<?php

use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::controller(StaticPageController::class)->name('pages.')->group(function () {
    Route::get('/about', 'about')->name('about');
    Route::get('/privacy-policy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/cookie-policy', 'cookies')->name('cookies');
});
