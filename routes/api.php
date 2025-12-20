<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Password;

Route::get('/debug-session', function () {
    return [
        'session_domain' => config('session.domain'),
        'sanctum_stateful' => config('sanctum.stateful_domains'),
    ];
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/products/search', [ProductController::class, 'search']);
Route::get('/products/featured', [ProductController::class, 'featured']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);

// Protected routes - require session authentication via Sanctum
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [UserController::class, 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);

    // Route::get('/cart/count', [CartController::class, 'count']);

    // Route::post('/cart/add', [CartController::class, 'add']);

    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove']);
    Route::patch('/cart/update/{id}', [CartController::class, 'updateQuantity']);
    Route::get('/cart/count', [CartController::class, 'count']);
});

// Handle CORS preflight requests for all routes
// Route::options('{any}', function () {
//     return response()->json([], 200);
// })->where('any', '.*');