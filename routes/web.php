<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Confam Buy API',
        'version' => '1.0.0',
    ]);
});