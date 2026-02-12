<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 *
 * Login and logout endpoints for cookie-based authentication.
 */
class LoginController extends Controller
{
    /**
     * Login.
     *
     * Start an authenticated session for the current user.
     *
     * @unauthenticated
     */
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

    /**
     * Logout.
     *
     * End the authenticated session for the current user.
     *
     * @authenticated
     */
    public function logout(Request $request): JsonResponse
    {
        auth()->guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();

        return response()->json(['message' => 'Logged out']);
    }
}
