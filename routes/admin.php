<?php

use App\Http\Controllers\Admin\AgencyModerationController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ListingModerationController;
use App\Http\Controllers\Admin\ReportedMessageModerationController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/listings', [ListingModerationController::class, 'index'])->name('listings.index');
        Route::post('/listings/{listing}/approve', [ListingModerationController::class, 'approve'])->name('listings.approve');
        Route::post('/listings/{listing}/reject', [ListingModerationController::class, 'reject'])->name('listings.reject');

        Route::get('/agencies', [AgencyModerationController::class, 'index'])->name('agencies.index');
        Route::post('/agencies/{agency}/approve', [AgencyModerationController::class, 'approve'])->name('agencies.approve');
        Route::post('/agencies/{agency}/reject', [AgencyModerationController::class, 'reject'])->name('agencies.reject');

        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::get('/reported-messages', [ReportedMessageModerationController::class, 'index'])->name('reported-messages.index');
        Route::post('/reported-messages/{report}/resolve', [ReportedMessageModerationController::class, 'resolve'])->name('reported-messages.resolve');
        Route::post('/reported-messages/{report}/suspend', [ReportedMessageModerationController::class, 'suspend'])->name('reported-messages.suspend');

        Route::get('/contact-messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::post('/contact-messages/{contactMessage}/handled', [ContactMessageController::class, 'markHandled'])->name('contact-messages.handled');
        Route::delete('/contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('contact-messages.destroy');
    });
