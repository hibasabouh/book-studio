<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public, token-issuing auth endpoints (consumed by web fetch and mobile alike).
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Token-protected endpoints.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});
