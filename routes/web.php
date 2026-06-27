<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

// Thin Blade page shells. They render the form and bootstrap auth client-side;
// the actual credential exchange happens via fetch to the /api endpoints.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// OTP-entry page (redirect target of the `verified` middleware).
Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');

// Forgot/reset password page (two-step: email → code + new password).
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');

// Authenticated page shells (auth enforced client-side; data via /api).
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
