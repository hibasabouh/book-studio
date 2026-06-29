<?php

namespace App\Http\Controllers;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\GoogleAuthRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
     * Render the OTP-entry page shell (code input + resend button).
     */
    public function showVerifyEmail(): View
    {
        return view('auth.verify-email');
    }

    /**
     * Render the forgot/reset password page shell (two-step: email → code+password).
     */
    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Session login for the browser frontend (web route, CSRF-protected).
     * Returns the authenticated user as JSON; the session cookie handles auth
     * for all subsequent same-origin requests.
     */
    public function webLogin(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['user' => new UserResource(Auth::user())]);
    }

    /**
     * Session register for the browser frontend.
     */
    public function webRegister(RegisterRequest $request, OtpService $otp): JsonResponse
    {
        $user = new User();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->password = $request->validated('password');
        $user->role = Role::User;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        $code = $otp->generate($user, OtpChannel::Email, OtpPurpose::Verify);
        $otp->send($user, OtpChannel::Email, $code);

        return response()->json(['user' => new UserResource($user)], 201);
    }

    /**
     * Session logout for the browser frontend.
     */
    public function webLogout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('messages.logged_out')]);
    }

    /**
     * Google session login for the browser frontend.
     */
    public function webGoogle(GoogleAuthRequest $request, GoogleAuthService $google): JsonResponse
    {
        $claims = $google->verify($request->validated('id_token'));

        $user = User::where('email', $claims['email'])->first();

        if (! $user) {
            $user = new User();
            $user->name = $claims['name'];
            $user->email = $claims['email'];
            $user->password = null;
            $user->role = Role::User;
        }

        $user->google_id ??= $claims['google_id'];
        $user->avatar ??= $claims['avatar'];

        if (! $user->email_verified_at && $claims['email_verified']) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => new UserResource($user)]);
    }

    /**
     * Register a new user and issue a token (API / mobile clients).
     *
     * Unverified — an OTP is emailed and must be confirmed before the `verified`
     * middleware allows access to protected features.
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
            $user->password = null; // no password until they set one in their profile
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
     * Return the authenticated user (nested under `user`, like every other
     * endpoint, so the client reads one consistent shape).
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => new UserResource($request->user())]);
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

    /**
     * Send a password-reset OTP to the account's email.
     */
    public function forgotPassword(ForgotPasswordRequest $request, OtpService $otp): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user) {
            return response()->json(['message' => __('messages.account_not_found')], 404);
        }

        $code = $otp->generate($user, OtpChannel::Email, OtpPurpose::Reset);
        $otp->send($user, OtpChannel::Email, $code);

        return response()->json([
            'message' => __('messages.password_reset_sent'),
            'user_id' => $user->id,
        ]);
    }

    /**
     * Verify the reset OTP and set a new password, revoking all existing tokens.
     */
    public function resetPassword(ResetPasswordRequest $request, OtpService $otp): JsonResponse
    {
        $user = User::findOrFail($request->validated('user_id'));

        $verified = $otp->verify($user, OtpPurpose::Reset, $request->validated('code'));

        if (! $verified) {
            return response()->json(['message' => __('messages.otp_invalid')], 422);
        }

        $user->password = $request->validated('password');
        $user->save();

        // Force re-login everywhere with the new password.
        $user->tokens()->delete();

        return response()->json(['message' => __('messages.password_reset_done')]);
    }
}
