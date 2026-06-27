<?php

namespace App\Services;

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Number of minutes an OTP remains valid.
     */
    private const TTL_MINUTES = 10;

    /**
     * Issue a fresh OTP: invalidate any outstanding code for the same purpose,
     * store a hashed 6-digit code, and return the plain code to send out.
     */
    public function generate(User $user, OtpChannel $channel, OtpPurpose $purpose, ?string $target = null): string
    {
        $user->otps()->unused()->forPurpose($purpose)->update(['used_at' => now()]);

        $plain = (string) random_int(100000, 999999);

        $user->otps()->create([
            'code' => Hash::make($plain),
            'channel' => $channel,
            'purpose' => $purpose,
            'target' => $target ?? $user->email,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $plain;
    }

    /**
     * Deliver the plain code over the given channel.
     */
    public function send(User $user, OtpChannel $channel, string $plainCode, ?string $target = null): void
    {
        match ($channel) {
            OtpChannel::Email => Mail::to($target ?? $user->email)->send(new OtpMail($plainCode)),
        };
    }

    /**
     * Verify a plain code for the given purpose. Returns the consumed OTP on
     * success (marking it used), or null when the code is wrong/expired.
     */
    public function verify(User $user, OtpPurpose $purpose, string $plainCode): ?Otp
    {
        $otp = $user->otps()->unused()->notExpired()->forPurpose($purpose)->latest()->first();

        if (! $otp || ! Hash::check($plainCode, $otp->code)) {
            return null;
        }

        $otp->update(['used_at' => now()]);

        return $otp;
    }
}
