<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;

// Welcome route (public)
Route::get('/', fn() => response()->json([
    'api'     => 'NoBa_API',
    'version' => '1.0',
    'status'  => 'running',
]))->name('api.welcome');


/*
|--------------------------------------------------------------------------
| PUBLIC AUTH ROUTES
|--------------------------------------------------------------------------
| These routes DO NOT require authentication
*/
Route::prefix('auth')->name('auth.')->group(function () {

    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login',    [AuthController::class, 'login'])->name('login');

});


/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
| Everything here requires auth token
*/
Route::middleware('auth:sanctum')->group(function () {

    /*
    |---------------------------
    | AUTH (Protected)
    |---------------------------
    */
    // Route::prefix('auth')->name('auth.')->group(function () {

        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me',      [AuthController::class, 'me'])->name('me');

    // });


    /*
    |---------------------------
    | POS MODULE
    |---------------------------
    */
    Route::prefix('pos')->name('pos.')->group(function () {

        // Categories
        Route::prefix('categories')->name('categories.')->group(function () {

            Route::get('/',        [CategoryController::class, 'index'])->name('index');
            Route::post('/',       [CategoryController::class, 'store'])->name('store');
            Route::get('/{id}',    [CategoryController::class, 'show'])->name('show');
            Route::put('/{id}',    [CategoryController::class, 'update'])->name('update');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');

        });


        // Products
        Route::prefix('products')->name('products.')->group(function () {

            Route::get('/',        [ProductController::class, 'index'])->name('index');
            Route::post('/',       [ProductController::class, 'store'])->name('store');
            Route::get('/{id}',    [ProductController::class, 'show'])->name('show');
            Route::put('/{id}',    [ProductController::class, 'update'])->name('update');
            Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');

        });


        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {

            Route::get('/',            [OrderController::class, 'index'])->name('index');
            Route::post('/',           [OrderController::class, 'store'])->name('store');
            Route::get('/{id}',        [OrderController::class, 'show'])->name('show');
            Route::put('/{id}/status', [OrderController::class, 'updateStatus'])->name('status.update');
            Route::delete('/{id}',     [OrderController::class, 'destroy'])->name('destroy');

            // Order Items
            Route::prefix('{orderId}/items')->name('items.')->group(function () {

                Route::get('/',            [OrderItemController::class, 'index'])->name('index');
                Route::post('/',           [OrderItemController::class, 'store'])->name('store');
                Route::get('/{itemId}',    [OrderItemController::class, 'show'])->name('show');
                Route::put('/{itemId}',    [OrderItemController::class, 'update'])->name('update');
                Route::delete('/{itemId}', [OrderItemController::class, 'destroy'])->name('destroy');

            });

        });

    });

});