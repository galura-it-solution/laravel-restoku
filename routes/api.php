<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\TableController;
use App\Http\Controllers\Api\V1\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('categories', CategoryController::class)
            ->only(['index', 'show']);
        Route::apiResource('categories', CategoryController::class)
            ->except(['index', 'show'])
            ->middleware('staff');
        Route::post('categories/{category}/image', [CategoryController::class, 'uploadImage'])
            ->middleware('staff');

        Route::apiResource('menus', MenuController::class)
            ->only(['index', 'show']);
        Route::apiResource('menus', MenuController::class)
            ->except(['index', 'show'])
            ->middleware('staff');
        Route::post('menus/{menu}/image', [MenuController::class, 'uploadImage'])
            ->middleware('staff');

        Route::get('tables/events', [TableController::class, 'stream']);
        Route::get('tables/stream', [TableController::class, 'stream']);
        Route::get('tables', [TableController::class, 'index']);
        Route::get('tables/{table}', [TableController::class, 'show'])
            ->whereNumber('table');
        Route::apiResource('tables', TableController::class)
            ->except(['index', 'show'])
            ->middleware('staff');

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/poll', [OrderController::class, 'poll']);
        Route::get('orders/events', [OrderController::class, 'stream']);
        Route::get('orders/stream', [OrderController::class, 'stream']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{order}', [OrderController::class, 'show'])
            ->whereNumber('order');
        Route::patch('orders/{order}/assign', [OrderController::class, 'assign'])
            ->middleware('staff');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->middleware('staff');

        Route::get('notifications/stream', [NotificationController::class, 'stream']);

        Route::get('users', [UserController::class, 'index'])
            ->middleware('staff');
    });
});
