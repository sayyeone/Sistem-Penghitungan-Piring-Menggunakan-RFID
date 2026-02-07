<?php

use App\Http\Controllers\Master\ItemController;
use App\Http\Controllers\Master\plateController;
use App\Http\Controllers\Master\userController;
use App\Http\Controllers\Transaction\transactionController;
use Illuminate\Support\Facades\Route;

Route::apiResource('item', ItemController::class);

Route::apiResource('plate', plateController::class);

Route::apiResource('user', userController::class);

Route::prefix('transaction')->group(function () {

    Route::post('/start', [transactionController::class, 'makeTransaction']);
    Route::post('/scan/{id}', [transactionController::class, 'scanTransaction']);

    Route::post('/{id}/pay', [transactionController::class, 'payTransaction']);
    Route::post('/midtrans/callback', [transactionController::class, 'midtransCallback']);

});
