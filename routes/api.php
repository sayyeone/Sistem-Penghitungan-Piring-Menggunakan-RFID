<?php

use App\Http\Controllers\Master\ItemController;
use App\Http\Controllers\Master\plateController;
use App\Http\Controllers\Master\userController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::apiResource('item', ItemController::class);

Route::apiResource('plate', plateController::class);

Route::apiResource('user', userController::class);
