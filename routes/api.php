<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Master\ItemController;
use App\Http\Controllers\Master\plateController;
use App\Http\Controllers\Master\userController;
use App\Http\Controllers\Transaction\transactionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

Route::apiResource('items', ItemController::class);

// Plate routes (plural for frontend compatibility)
Route::get('/plates/rfid/{uid}', [plateController::class, 'getByRfid']);
Route::apiResource('plates', plateController::class);

Route::apiResource('users', userController::class);

// Transaction routes (new flow for cart-based checkout)
Route::get('/transactions', [transactionController::class, 'index']); // History
Route::post('/transactions', [transactionController::class, 'create']); // Create from cart
Route::get('/transactions/{id}', [transactionController::class, 'show']); // Detail

// Payment routes
Route::post('/payment/snap/{id}', [transactionController::class, 'payTransaction']);
Route::post('/payment/midtrans/callback', [transactionController::class, 'midtransCallback']);

// Dashboard routes
Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats']);
    Route::get('/revenue', [DashboardController::class, 'getRevenue']);
    Route::get('/popular-plates', [DashboardController::class, 'getPopularPlates']);
    Route::get('/recent-transactions', [DashboardController::class, 'getRecentTransactions']);
});
