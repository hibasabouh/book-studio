<?php

namespace App\Http\Controllers;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
     * Render the OTP-entry page shell (code input + resend button).
     */
    public function showVerifyEmail(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Register a new user and issue a token.
     *
     * Token-based: no session is created. The user is logged in immediately but
     * unverified — an OTP is emailed and must be confirmed (verifyEmail) before
     * the `verified` middleware allows access to protected features.
     */
    public function register(RegisterRequest $request, OtpService $otp): JsonResponse
    {
        $user = new User();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->password = $request->validated('password');
        $user->role = Role::User;
        $user->save();

        $code = $otp->generate($user, OtpChannel::Email, OtpPurpose::Verify);
        $otp->send($user, OtpChannel::Email, $code);

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
     * Sign in (or register) with a Google ID token.
     *
     * The same endpoint serves web and mobile: each obtains an ID token from
     * Google client-side and POSTs it here. The token is verified server-side,
     * then the user is found by email (linking google_id if needed) or created.
     * Google emails are pre-verified, so no OTP is required.
     */
    public function google(GoogleAuthRequest $request, GoogleAuthService $google): JsonResponse
    {
        $claims = $google->verify($request->validated('id_token'));

        $user = User::where('email', $claims['email'])->first();

        if (! $user) {
            $user = new User();
            $user->name = $claims['name'];
            $user->email = $claims['email'];
            $user->password = Str::random(40); // unusable placeholder; they sign in via Google
            $user->role = Role::User;
        }

        $user->google_id ??= $claims['google_id'];
        $user->avatar ??= $claims['avatar'];

        if (! $user->email_verified_at && $claims['email_verified']) {
            $user->email_verified_at = now();
        }

        $user->save();

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

        return response()->json(['message' => __('messages.logged_out')]);
    }

    /**
     * Return the authenticated user.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Verify the authenticated user's email with the emailed OTP code.
     */
    public function verifyEmail(VerifyOtpRequest $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('messages.email_already_verified')]);
        }

        $verified = $otp->verify($user, OtpPurpose::Verify, $request->validated('code'));

        if (! $verified) {
            return response()->json(['message' => __('messages.otp_invalid')], 422);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return response()->json([
            'message' => __('messages.email_verified'),
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Send a fresh verification OTP to the authenticated user.
     */
    public function resendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('messages.email_already_verified')]);
        }

        $code = $otp->generate($user, OtpChannel::Email, OtpPurpose::Verify);
        $otp->send($user, OtpChannel::Email, $code);

        return response()->json(['message' => __('messages.otp_sent')]);
    }
}
