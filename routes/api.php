<?php

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

Route::get('/user', function (Request $request) {
    return $request->user();
});


// Public routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout']);

Route::get('/me', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// // Protected routes (Only logged-in users can access)
// Route::middleware('auth:sanctum')->group(function () {
    
//     // Get the current user
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });

//     // Logout
//     Route::post('/logout', [LoginController::class, 'logout']);
    
