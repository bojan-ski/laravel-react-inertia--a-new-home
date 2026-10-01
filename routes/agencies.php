<?php

use App\Http\Controllers\AgencyDirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/agencies', [AgencyDirectoryController::class, 'index'])->name('agencies.index');
Route::get('/agencies/{agency:slug}', [AgencyDirectoryController::class, 'show'])->name('agencies.show');
