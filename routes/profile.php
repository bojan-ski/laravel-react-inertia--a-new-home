<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:regular_user'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
});
