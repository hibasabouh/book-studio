<?php

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

describe('email verification', function () {
    it('verifies the email with a valid OTP code', function () {
        $user = User::factory()->unverified()->create();
        $code = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify-otp', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    });

    it('rejects an invalid OTP code', function () {
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify-otp', ['code' => '000000'])
            ->assertUnprocessable();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('rejects an expired OTP code', function () {
        $user = User::factory()->unverified()->create();
        $code = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        $this->travel(11)->minutes();

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify-otp', ['code' => $code])
            ->assertUnprocessable();

        expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    });

    it('does not accept the same code twice', function () {
        $user = User::factory()->unverified()->create();
        $code = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify-otp', ['code' => $code])->assertOk();

        // Un-verify to prove the second failure comes from the consumed OTP.
        $user->forceFill(['email_verified_at' => null])->save();

        $this->postJson('/api/email/verify-otp', ['code' => $code])
            ->assertUnprocessable();
    });

    it('is a no-op for an already verified email', function () {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/email/verify-otp', ['code' => '123456'])
            ->assertOk();
    });

    it('requires authentication', function () {
        $this->postJson('/api/email/verify-otp', ['code' => '123456'])
            ->assertUnauthorized();
    });
});

describe('resend OTP', function () {
    it('emails a fresh code and invalidates the previous one', function () {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $oldCode = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        Sanctum::actingAs($user);

        $this->postJson('/api/email/resend-otp')->assertOk();

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo($user->email));

        $this->postJson('/api/email/verify-otp', ['code' => $oldCode])
            ->assertUnprocessable();
    });
});
