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
        Route::post(
            '/stores/{store}/tasks',
            [StoreController::class, 'storeTask']
        );
        Route::get(
            '/stores/{store}/tasks/{task}',
            [StoreController::class, 'showTask']
        )->scopeBindings();
        // Update task details
        Route::patch(
            '/stores/{store}/tasks/{task}',
            [StoreController::class, 'updateTask']
        )->scopeBindings();

        // Assign or unassign a task
        Route::put(
            '/stores/{store}/tasks/{task}/assignee',
            [StoreController::class, 'assignTask']
        )->scopeBindings();

        // Change task status
        Route::put(
            '/stores/{store}/tasks/{task}/status',
            [StoreController::class, 'changeTaskStatus']
        )->scopeBindings();
        Route::post(
            '/stores/{store}/tasks/{task}/comments',
            [StoreController::class, 'storeComment']
        )->scopeBindings();

        Route::get(
            '/stores/{store}/tasks/{task}/comments',
            [StoreController::class, 'taskComments']
        )->scopeBindings();

        Route::get(
            '/stores/{store}/tasks/{task}/events',
            [StoreController::class, 'taskEvents']
        )->scopeBindings();

        Route::get(
            '/stores/{store}/task-summary',
            [StoreController::class, 'taskSummary']
        );
    });

});
