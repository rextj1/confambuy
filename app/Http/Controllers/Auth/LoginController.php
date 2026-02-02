<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse; // Added this import

class LoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

       session()->regenerate();

        return response()->json([
            'message' => 'Logged in successfully',
            'user' => auth()->user(), 
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Explicitly using the web guard for cookie logout
        auth()->guard('web')->logout();

      session()->invalidate();
        session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}