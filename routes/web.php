<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\ReservationController;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [WelcomeController::class, 'index'])->name('dashboard');

    Route::get('/salles', [SalleController::class, 'index'])->name('salles.index');
    Route::get('/salles/creer', [SalleController::class, 'create'])->name('salles.create');
    Route::get('/salles/{id}/modifier', [SalleController::class, 'edit'])->name('salles.edit');
    Route::post('/salles', [SalleController::class, 'store'])->name('salles.store');
    Route::put('/salles/{id}', [SalleController::class, 'update'])->name('salles.update');
    Route::delete('/salles/{id}', [SalleController::class, 'destroy'])->name('salles.destroy');

    // Routes pour la gestion des réservations
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/creer', [ReservationController::class, 'create'])->name('reservations.create');
    Route::get('/reservations/{id}/modifier', [ReservationController::class, 'edit'])->name('reservations.edit');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::put('/reservations/{id}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
