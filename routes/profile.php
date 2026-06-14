<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:regular_user'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::get('/profile/settings', [ProfileSettingsController::class, 'edit'])->name('profile.settings.edit');
    Route::put('/profile/settings/account', [ProfileSettingsController::class, 'updateAccount'])->name('profile.settings.account.update');
    Route::put('/profile/settings/password', [ProfileSettingsController::class, 'updatePassword'])->name('profile.settings.password.update');
});
