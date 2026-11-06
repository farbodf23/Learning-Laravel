<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [AuthController::class, 'me']);

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/me/stores', [AuthController::class, 'myStores']);
        Route::get('/stores/{store}/members', [StoreController::class, 'members']);
        Route::get('/stores/{store}/tasks', [StoreController::class, 'tasks']);

    });

});
