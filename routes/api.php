<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;

Route::get('/debug-session', function () {
    return [
        'session_domain' => config('session.domain'),
        'sanctum_stateful' => config('sanctum.stateful_domains'),
    ];
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes - require session authentication via Sanctum
Route::middleware('auth:sanctum')->group(function () {

    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });

    Route::get('/user', [UserController::class, 'show']);

    // Route::get('/profile', [ProfileController::class, 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

// Handle CORS preflight requests for all routes
// Route::options('{any}', function () {
//     return response()->json([], 200);
// })->where('any', '.*');