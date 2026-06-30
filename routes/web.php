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
// the actual credential exchange happens via fetch to the routes below.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// Web session auth — consumed by the browser frontend via fetch.
Route::post('/login', [AuthController::class, 'webLogin']);
Route::post('/register', [AuthController::class, 'webRegister']);
Route::post('/auth/google', [AuthController::class, 'webGoogle']);

// OTP-entry page (redirect target of the `verified` middleware).
Route::get('/email/verify', [AuthController::class, 'showVerifyEmail'])->name('verification.notice');

// Forgot/reset password page (two-step: email → code + new password).
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');

// Authenticated page shells.
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'webLogout']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/{user}', [AdminController::class, 'showUser'])->name('admin.users.show');
    Route::get('/books', [BookController::class, 'index'])->name('books.library');
    Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
});
