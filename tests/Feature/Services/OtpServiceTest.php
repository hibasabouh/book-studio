<?php

use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->otp = app(OtpService::class);
    $this->user = User::factory()->create();
});

describe('generate', function () {
    it('returns a six-digit numeric code', function () {
        expect($this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify))
            ->toMatch('/^\d{6}$/');
    });

    it('stores the code hashed rather than in plain text', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);
        $stored = $this->user->otps()->sole();

        expect($stored->code)->not->toBe($code)
            ->and(Hash::check($code, $stored->code))->toBeTrue();
    });

    it('records the channel, purpose and a ten-minute expiry', function () {
        $this->freezeTime();

        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Reset);
        $stored = $this->user->otps()->sole();

        expect($stored->channel)->toBe(OtpChannel::Email)
            ->and($stored->purpose)->toBe(OtpPurpose::Reset)
            ->and($stored->used_at)->toBeNull()
            // Second precision: the column round-trips without microseconds.
            ->and($stored->expires_at->toDateTimeString())->toBe(now()->addMinutes(10)->toDateTimeString());
    });

    it('targets the user email by default', function () {
        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->user->otps()->sole()->target)->toBe($this->user->email);
    });

    it('accepts an explicit target address', function () {
        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify, 'other@example.com');

        expect($this->user->otps()->sole()->target)->toBe('other@example.com');
    });

    it('invalidates an outstanding code for the same purpose', function () {
        $first = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);
        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->otp->verify($this->user, OtpPurpose::Verify, $first))->toBeNull();
    });

    it('leaves an outstanding code for a different purpose usable', function () {
        $reset = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Reset);
        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->otp->verify($this->user, OtpPurpose::Reset, $reset))->not->toBeNull();
    });

    it("does not touch another user's outstanding code", function () {
        $other = User::factory()->create();
        $otherCode = $this->otp->generate($other, OtpChannel::Email, OtpPurpose::Verify);

        $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->otp->verify($other, OtpPurpose::Verify, $otherCode))->not->toBeNull();
    });
});

describe('verify', function () {
    it('consumes a valid code and marks it used', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        $consumed = $this->otp->verify($this->user, OtpPurpose::Verify, $code);

        expect($consumed)->not->toBeNull()
            ->and($consumed->used_at)->not->toBeNull()
            ->and($this->user->otps()->sole()->used_at)->not->toBeNull();
    });

    it('rejects a wrong code without consuming the stored one', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->otp->verify($this->user, OtpPurpose::Verify, '000000'))->toBeNull()
            ->and($this->otp->verify($this->user, OtpPurpose::Verify, $code))->not->toBeNull();
    });

    it('rejects a code that has already been used', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        $this->otp->verify($this->user, OtpPurpose::Verify, $code);

        expect($this->otp->verify($this->user, OtpPurpose::Verify, $code))->toBeNull();
    });

    it('rejects a code past its expiry', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        $this->travel(11)->minutes();

        expect($this->otp->verify($this->user, OtpPurpose::Verify, $code))->toBeNull();
    });

    it('still accepts a code just inside its expiry', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        $this->travel(9)->minutes();

        expect($this->otp->verify($this->user, OtpPurpose::Verify, $code))->not->toBeNull();
    });

    it('rejects a code issued for a different purpose', function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);

        expect($this->otp->verify($this->user, OtpPurpose::Reset, $code))->toBeNull();
    });

    it("rejects another user's code", function () {
        $code = $this->otp->generate($this->user, OtpChannel::Email, OtpPurpose::Verify);
        $other = User::factory()->create();

        expect($this->otp->verify($other, OtpPurpose::Verify, $code))->toBeNull();
    });

    it('returns null when no code was ever issued', function () {
        expect($this->otp->verify($this->user, OtpPurpose::Verify, '123456'))->toBeNull();
    });
});

describe('send', function () {
    it('emails the plain code to the user', function () {
        Mail::fake();

        $this->otp->send($this->user, OtpChannel::Email, '123456');

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo($this->user->email) && $mail->code === '123456');
    });

    it('emails an explicit target instead of the account address', function () {
        Mail::fake();

        $this->otp->send($this->user, OtpChannel::Email, '123456', 'other@example.com');

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('other@example.com'));
    });
});
