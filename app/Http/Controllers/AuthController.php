<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Render the login page shell (thin Blade form; submits via fetch).
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Render the registration page shell.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new user and issue a token.
     *
     * Token-based: no session is created. Web and mobile both consume the
     * JSON response and store the returned bearer token client-side.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = new User();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->password = $request->validated('password');
        $user->role = Role::User;
        $user->save();

        event(new Registered($user));

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user),
        ], 201);
    }

    /**
     * Verify credentials and issue a token (no session login).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('web')->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Return the authenticated user.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
