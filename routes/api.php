<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

Route::options('{any}', function () {
    return response()->json([], 200);
})->where('any', '.*');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/profile', [ProfileController::class, 'show']);

Route::post('/logout', function (Request $request) {
    auth()->logout();
    return response()->json(['message' => 'Logged out successfully']);
});

Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return ['message' => 'Logged out'];
})->middleware('auth:sanctum');
