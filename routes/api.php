<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookExportController;
use App\Http\Controllers\BookSettingController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public, token-issuing auth endpoints (consumed by web fetch and mobile alike).
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google']);

// Password reset via OTP (email-only).
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');

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

    // Dashboard overview stats for the authenticated user.
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Admin-only data.
    Route::middleware('can:admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{user}', [AdminController::class, 'userDetails']);
        Route::patch('/users/{user}', [AdminController::class, 'updateUser']);
        Route::delete('/users/{user}', [AdminController::class, 'destroyUser']);
    });

    // Books management
    Route::apiResource('books', BookController::class);

    // Book settings
    Route::get('books/{book}/setting', [BookSettingController::class, 'show']);
    Route::patch('books/{book}/setting', [BookSettingController::class, 'update']);

    // Book export
    Route::get('books/{book}/export', [BookExportController::class, 'download']);

    // Chapters management (shallow: index/store nested, show/update/destroy flat)
    Route::post('books/{book}/chapters/reorder', [ChapterController::class, 'reorder']);
    Route::apiResource('books.chapters', ChapterController::class)->shallow();
});
