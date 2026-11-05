<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\DemoPresentationController;
use App\Http\Controllers\HomePagesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomePagesController::class, 'index'])->name('home');
Route::get('/fleet', [HomePagesController::class, 'fleet'])->name('fleet');
Route::get('/about', [HomePagesController::class, 'about'])->name('about');
Route::get('/contact', [HomePagesController::class, 'contact'])->name('contact');
Route::post('/contact/guestContact', [HomePagesController::class, 'guestContact'])->name('contact.guestContact');



Route::get('/fleet/{car}', [BookingController::class, 'show'])->name('fleet.show');
Route::post('/fleet/{car}', [BookingController::class, 'book'])->name('fleet.book');
Route::post('/api/coupons/validate', [BookingController::class, 'validateCoupon'])->name('coupons.validate');

Route::get('/booking/{reservation}', [BookingController::class, 'confirmation'])->name('booking.confirmation');
Route::get('/booking/{reservation}/agreement', [BookingController::class, 'agreement'])->middleware('auth')->name('booking.agreement');
Route::get('/verify/voucher/{reservation_number}', [BookingController::class, 'verifyVoucher'])->name('voucher.verify');

// Academic Presentation & Evaluator Demo Mode
Route::get('/presentation', [DemoPresentationController::class, 'index'])->name('demo.presentation');
Route::match(['get', 'post'], '/demo/switch-role', [DemoPresentationController::class, 'switchRole'])->name('demo.switchRole');
Route::match(['get', 'post'], '/demo/launch-scenario/{scenario}', [DemoPresentationController::class, 'launchScenario'])->name('demo.launchScenario');

Route::get('/dashboard', function () {
    if (auth()->user()->role === \App\Enums\UserRole::ADMIN) {
        return redirect()->route('admin.home');
    }
    return redirect()->route('client.home');
})->middleware('auth')->name('dashboard');


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/client.php';
