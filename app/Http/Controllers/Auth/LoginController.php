<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse; // Added this import
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (! auth()->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
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
