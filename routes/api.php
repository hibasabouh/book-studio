<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public, token-issuing auth endpoints (consumed by web fetch and mobile alike).
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);

// Token-protected endpoints.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Email verification via OTP (operates on the authenticated user).
    Route::post('/email/verify-otp', [AuthController::class, 'verifyEmail'])
        ->middleware('throttle:6,1')
        ->name('verification.verify');

    Route::post('/email/resend-otp', [AuthController::class, 'resendOtp'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Profile management.
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);

    // Admin-only data.
    Route::get('/admin/stats', [AdminController::class, 'stats'])->middleware('can:admin');
});
