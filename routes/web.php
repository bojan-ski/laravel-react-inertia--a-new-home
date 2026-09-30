<?php

use App\Http\Controllers\AgencyDirectoryController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/agencies', [AgencyDirectoryController::class, 'index'])->name('agencies.index');
Route::get('/agencies/{agency:slug}', [AgencyDirectoryController::class, 'show'])->name('agencies.show');

require __DIR__.'/pages.php';
require __DIR__.'/listings.php';
require __DIR__.'/saved-searches.php';
require __DIR__.'/conversations.php';
require __DIR__.'/profile.php';
require __DIR__.'/agency.php';
require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
