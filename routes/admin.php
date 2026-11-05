<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CarsController;
use App\Http\Controllers\Admin\OperationsController;
use App\Http\Controllers\Admin\ReservationsController;
use App\Http\Controllers\Admin\ClientsController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ReviewsController;
use App\Http\Controllers\Admin\SupportController;

Route::middleware(['auth', 'verified', 'active', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        // Default admin landing is Fleet Operations Command Center
        Route::get('/', [OperationsController::class, 'index'])->name('home');

        // Operations Command Center
        Route::get('operations', [OperationsController::class, 'index'])->name('operations');
        Route::patch('operations/dispatch/{reservation}', [OperationsController::class, 'dispatch'])->name('operations.dispatch');
        Route::patch('operations/checkin/{reservation}', [OperationsController::class, 'checkin'])->name('operations.checkin');
        Route::patch('operations/cars/{car}/status', [OperationsController::class, 'updateCarStatus'])->name('operations.carStatus');

        // Cars
        Route::resource('cars', CarsController::class)->except(['show']);

        // Reservations
        Route::resource('reservations', ReservationsController::class)->only(['index', 'show', 'edit', 'update']);
        Route::get('reservations/{reservation}/print', [ReservationsController::class, 'print'])->name('reservations.print');

        // Clients
        Route::resource('clients', ClientsController::class)->only(['index', 'show']);
        Route::patch('clients/{client}/suspend', [ClientsController::class, 'suspend'])->name('clients.suspend');
        Route::patch('clients/{client}/activate', [ClientsController::class, 'activate'])->name('clients.activate');
        Route::patch('clients/{client}/kyc/approve', [ClientsController::class, 'approveKyc'])->name('clients.kyc.approve');
        Route::patch('clients/{client}/kyc/reject', [ClientsController::class, 'rejectKyc'])->name('clients.kyc.reject');

        // Payments
        Route::resource('payments', PaymentsController::class)->only(['index']);

        // Reports
        Route::resource('reports', ReportsController::class)->except(['show']);

        // Reviews
        Route::resource('reviews', ReviewsController::class)->only(['index', 'destroy']);

        // Support
        Route::resource('support', SupportController::class)->only(['index']);
        Route::get('/support/tickets/{ticket}', [SupportController::class, 'show'])
        ->name('support.show');
        Route::post('/support/tickets/{ticket}/reply', [SupportController::class, 'reply'])
        ->name('support.reply');
        Route::post('/support/tickets/{ticket}/close', [SupportController::class, 'close'])
        ->name('support.close');

    });
