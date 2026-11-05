<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Client\ReservationsController;
use App\Http\Controllers\Client\ReviewController;
use App\Http\Controllers\Client\SupportController;

Route::middleware(['auth', 'verified', 'active', 'client'])
    ->prefix('client')
    ->as('client.')
    ->group(function () {
        // Redirect '/client' to '/client/reservations' with a named route we can reference
        Route::redirect('/', '/client/reservations')->name('home');
        Route::get('/reservations', [ReservationsController::class, 'index'])->name('reservations.index');
        Route::get('/reservations/{id}', [ReservationsController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{id}/print', [ReservationsController::class, 'print'])->name('reservations.print');
        Route::post('/reservations/{reservation}/reviews', [ReviewController::class, 'store'])->name('reservations.reviews.store');
        Route::post('/reservations/{reservation}/cancel', [ReservationsController::class, 'cancel'])->name('reservations.cancel');

        // Support
        Route::get('/support', [SupportController::class, 'index'])->name('support.index');
        Route::get('/support/create', [SupportController::class, 'create'])->name('support.create');
        Route::post('/support', [SupportController::class, 'store'])->name('support.store');
        Route::get('/support/{id}', [SupportController::class, 'show'])->name('support.show');
        Route::post('/support/{id}/reply', [SupportController::class, 'reply'])->name('support.reply');

    });
