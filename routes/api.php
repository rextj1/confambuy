<?php

use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Confam Buy API',
        'version' => '1.0.0',
    ]);
});

// Public routes
Route::post('/login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/me', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [LoginController::class, 'logout']);
});

Route::apiResource('products', ProductController::class)->names('api.products');

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('products', ProductController::class)->names('products');
});
