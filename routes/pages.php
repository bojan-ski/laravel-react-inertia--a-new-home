<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StaticPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

Route::controller(StaticPageController::class)->name('pages.')->group(function () {
    Route::get('/about', 'about')->name('about');
    Route::get('/privacy-policy', 'privacy')->name('privacy');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/cookie-policy', 'cookies')->name('cookies');
});
