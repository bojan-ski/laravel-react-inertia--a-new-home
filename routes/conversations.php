<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index'])->middleware('not-suspended')->name('conversations.index');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/listings/{listing}/conversations', [ConversationController::class, 'store'])->middleware('throttle:conversation-start')->name('listings.conversations.store');
    Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'sendMessage'])->middleware('throttle:chat')->name('conversations.messages.store');
    Route::post('/messages/{message}/report', [MessageReportController::class, 'store'])->middleware('throttle:chat')->name('messages.report');
});
