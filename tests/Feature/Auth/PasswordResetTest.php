<?php

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

describe('forgot password', function () {
    it('emails a reset OTP to an existing account', function () {
        Mail::fake();

        $user = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('user_id', $user->id);

        expect($user->otps()->count())->toBe(1);

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo($user->email));
    });

    it('returns 404 for an unknown email', function () {
        Mail::fake();

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])
            ->assertNotFound();

        Mail::assertNothingSent();
    });
});

describe('reset password', function () {
    it('sets the new password and revokes all tokens with a valid code', function () {
        $user = User::factory()->create();
        $user->createToken('web');
        $code = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Reset);

        $this->postJson('/api/reset-password', [
            'user_id' => $user->id,
            'code' => $code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $user->refresh();

        expect(Hash::check('new-password-123', $user->password))->toBeTrue()
            ->and($user->tokens()->count())->toBe(0);
    });

    it('rejects an invalid code and keeps the old password', function () {
        $user = User::factory()->create();
        app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Reset);

        $this->postJson('/api/reset-password', [
            'user_id' => $user->id,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable();

        expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    });

    it('does not accept a verification-purpose code for a reset', function () {
        $user = User::factory()->create();
        $code = app(OtpService::class)->generate($user, OtpChannel::Email, OtpPurpose::Verify);

        $this->postJson('/api/reset-password', [
            'user_id' => $user->id,
            'code' => $code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable();
    });
});
