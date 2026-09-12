<?php

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Http;

const TOKEN_INFO = 'https://oauth2.googleapis.com/tokeninfo*';

const CLIENT_ID = 'test-client-id.apps.googleusercontent.com';

/**
 * A tokeninfo payload for a valid Google ID token, overridable per test.
 */
function googleClaims(array $overrides = []): array
{
    return [
        'iss' => 'https://accounts.google.com',
        'aud' => CLIENT_ID,
        'sub' => '1234567890',
        'email' => 'gmail-user@example.com',
        'email_verified' => 'true',
        'name' => 'Google User',
        'picture' => 'https://lh3.googleusercontent.com/a/photo.jpg',
        ...$overrides,
    ];
}

function fakeGoogle(array $overrides = []): void
{
    Http::fake([TOKEN_INFO => Http::response(googleClaims($overrides))]);
}

beforeEach(function () {
    config(['services.google.client_id' => CLIENT_ID]);
});

describe('API Google sign-in', function () {
    it('creates a verified, passwordless account and issues a token', function () {
        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'gmail-user@example.com')
            ->assertJsonPath('user.has_password', false);

        $user = User::where('email', 'gmail-user@example.com')->sole();

        expect($user->google_id)->toBe('1234567890')
            ->and($user->name)->toBe('Google User')
            ->and($user->password)->toBeNull()
            ->and($user->role)->toBe(Role::User)
            ->and($user->hasVerifiedEmail())->toBeTrue()
            ->and($user->tokens()->count())->toBe(1);
    });

    it('accepts email_verified as a real boolean as well as a string', function ($verified) {
        fakeGoogle(['email_verified' => $verified]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect(User::sole()->hasVerifiedEmail())->toBeTrue();
    })->with(['string' => 'true', 'boolean' => true]);

    it('leaves the account unverified when Google says the email is not verified', function () {
        fakeGoogle(['email_verified' => false]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect(User::sole()->hasVerifiedEmail())->toBeFalse();
    });

    it('falls back to the email address when Google sends no name', function () {
        fakeGoogle(['name' => null]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect(User::sole()->name)->toBe('gmail-user@example.com');
    });

    it('stores a null avatar when Google sends no picture', function () {
        fakeGoogle(['picture' => null]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect(User::sole()->avatar)->toBeNull();
    });
});

describe('linking an existing account', function () {
    it('links google_id to the account that already owns the email', function () {
        $existing = User::factory()->create(['email' => 'gmail-user@example.com', 'google_id' => null]);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('user.id', $existing->id);

        expect(User::count())->toBe(1)
            ->and($existing->refresh()->google_id)->toBe('1234567890');
    });

    it('keeps the password on an account that already had one', function () {
        $existing = User::factory()->create(['email' => 'gmail-user@example.com']);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('user.has_password', true);

        expect($existing->refresh()->password)->not->toBeNull();
    });

    it('does not overwrite an avatar the user already has', function () {
        $existing = User::factory()->create([
            'email' => 'gmail-user@example.com',
            'avatar' => 'avatars/mine.jpg',
        ]);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect($existing->refresh()->avatar)->toBe('avatars/mine.jpg');
    });

    it('does not re-link a different google_id onto the account', function () {
        $existing = User::factory()->create([
            'email' => 'gmail-user@example.com',
            'google_id' => 'original-google-id',
        ]);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect($existing->refresh()->google_id)->toBe('original-google-id');
    });

    it('verifies an account that had not verified its email yet', function () {
        $existing = User::factory()->unverified()->create(['email' => 'gmail-user@example.com']);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();

        expect($existing->refresh()->hasVerifiedEmail())->toBeTrue();
    });
});

describe('rejected tokens', function () {
    it('rejects a token minted for a different client', function () {
        fakeGoogle(['aud' => 'someone-elses-client-id']);

        $this->postJson('/api/auth/google', ['id_token' => 'foreign-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');

        expect(User::count())->toBe(0);
    });

    it('rejects a token from an unexpected issuer', function () {
        fakeGoogle(['iss' => 'https://evil.example.com']);

        $this->postJson('/api/auth/google', ['id_token' => 'forged-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');
    });

    it('accepts both issuer spellings Google uses', function (string $issuer) {
        fakeGoogle(['iss' => $issuer]);

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertOk();
    })->with(['accounts.google.com', 'https://accounts.google.com']);

    it('rejects a payload missing the subject or email', function (string $missing) {
        fakeGoogle([$missing => null]);

        // A null claim is absent as far as isset() is concerned.
        $this->postJson('/api/auth/google', ['id_token' => 'partial-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');
    })->with(['sub', 'email']);

    it('rejects the error body Google returns for an invalid token', function () {
        Http::fake([TOKEN_INFO => Http::response(['error' => 'invalid_token'], 400)]);

        $this->postJson('/api/auth/google', ['id_token' => 'expired-token'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');
    });

    it('requires an id_token', function () {
        $this->postJson('/api/auth/google', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('id_token');
    });

    it('refuses to sign in a blocked account', function (UserStatus $status) {
        User::factory()->create(['email' => 'gmail-user@example.com', 'status' => $status]);

        fakeGoogle();

        $this->postJson('/api/auth/google', ['id_token' => 'valid-token'])->assertForbidden();

        expect(User::sole()->tokens()->count())->toBe(0);
    })->with([
        'inactive' => UserStatus::Inactive,
        'suspended' => UserStatus::Suspended,
    ]);
});

describe('web Google sign-in', function () {
    it('authenticates the session and returns the user', function () {
        fakeGoogle();

        $this->postJson('/auth/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonPath('user.email', 'gmail-user@example.com');

        $this->assertAuthenticatedAs(User::sole());
    });

    it('does not start a session for a blocked account', function () {
        User::factory()->create(['email' => 'gmail-user@example.com', 'status' => UserStatus::Suspended]);

        fakeGoogle();

        $this->postJson('/auth/google', ['id_token' => 'valid-token'])->assertForbidden();

        $this->assertGuest();
    });

    it('does not start a session for an invalid token', function () {
        fakeGoogle(['aud' => 'someone-elses-client-id']);

        $this->postJson('/auth/google', ['id_token' => 'foreign-token'])->assertUnprocessable();

        $this->assertGuest();
    });
});
