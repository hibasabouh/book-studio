<?php

namespace App\Http\Controllers;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Render the profile page shell.
     */
    public function edit(): View
    {
        return view('profile.edit');
    }

    /**
     * Update name/email. Changing the email marks the account unverified again
     * and triggers a fresh verification OTP to the new address.
     */
    public function update(UpdateProfileRequest $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->validated('email');

        $user->name = $request->validated('name');
        $user->email = $request->validated('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $code = $otp->generate($user, OtpChannel::Email, OtpPurpose::Verify);
            $otp->send($user, OtpChannel::Email, $code);
        }

        return response()->json([
            'message' => __('messages.profile_updated'),
            'email_changed' => $emailChanged,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Update the password after confirming the current one.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = $request->validated('password');
        $user->save();

        return response()->json(['message' => __('messages.password_updated')]);
    }

    /**
     * Replace the user's avatar with an uploaded image.
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        // Remove the previous file (skip external Google URLs, which aren't on disk).
        if ($user->avatar && ! str_starts_with($user->avatar, 'http')) {
            delete_file_if_exist($user->avatar);
        }

        $user->avatar = upload_file($request->file('avatar'), 'avatar', 'avatars');
        $user->save();

        return response()->json([
            'message' => __('messages.profile_updated'),
            'user' => new UserResource($user),
        ]);
    }
}
