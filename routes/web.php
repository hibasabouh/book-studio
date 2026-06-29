<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
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

// Authenticated page shells — cookie validated server-side; data fetched via /api with bearer token.
Route::middleware('web.auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/books', [BookController::class, 'index'])->name('books.library');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
});
