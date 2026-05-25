<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
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
     * Register.
     *
     * Create a new user and send email verification notification.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->validated());

        event(new Registered($user));

        auth()->guard('web')->login($user);
        session()->regenerate();

        return response()->json([
            'message' => 'Registered successfully',
            'verification_email_sent' => true,
            'user' => $user->load('roles'),
        ], 201);
    }

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
