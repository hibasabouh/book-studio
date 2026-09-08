<?php

use App\Enums\Role;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

describe('API registration', function () {
    it('creates an unverified user, issues a token, and emails an OTP', function () {
        Mail::fake();

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $user = User::where('email', 'test@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->role)->toBe(Role::User)
            ->and($user->hasVerifiedEmail())->toBeFalse()
            ->and($user->otps()->count())->toBe(1);

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('test@example.com'));
    });

    it('rejects a duplicate email', function () {
        Mail::fake();

        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    });

    it('rejects a password that is too short or unconfirmed', function () {
        Mail::fake();

        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');
    });
});

describe('web registration', function () {
    it('creates the user, logs them into the session, and emails an OTP', function () {
        Mail::fake();

        $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertCreated();

        $user = User::where('email', 'test@example.com')->first();

        expect($user)->not->toBeNull();
        $this->assertAuthenticatedAs($user);

        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('test@example.com'));
    });
});
