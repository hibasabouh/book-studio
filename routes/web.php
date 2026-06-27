<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Thin Blade page shells. They render the form and bootstrap auth client-side;
// the actual credential exchange happens via fetch to the /api endpoints.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// OTP-entry page (redirect target of the `verified` middleware).
Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');
