<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->name('login.submit');

Route::get('/verify-email', function () {
    return redirect()->route('login');
})->name('verification.notice');

Route::get('/verify-email/{id}/{hash}', function () {
    return redirect()->route('login');
})->name('verification.verify');

Route::post('/email/verification-notification', function () {
    return redirect()->route('login');
})->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
