<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SanctumAuthController;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Confam Buy API',
        'version' => '1.0.0',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['web']], function () {
    // Cookie-based Sanctum login for first-party SPA (Postman: use cookies and XSRF token)
    Route::post('/login', [SanctumAuthController::class, 'login']);
    Route::post('/logout', [SanctumAuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [SanctumAuthController::class, 'me'])->middleware('auth:sanctum');
});
