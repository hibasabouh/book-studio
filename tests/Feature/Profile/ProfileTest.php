<?php

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Mail::fake();
    Storage::fake('public');
});

describe('updating name and email', function () {
    it('saves a new name without touching verification', function () {
        Sanctum::actingAs($user = User::factory()->create(['name' => 'Old Name']));

        $this->patchJson('/api/profile', ['name' => 'New Name', 'email' => $user->email])
            ->assertOk()
            ->assertJsonPath('email_changed', false)
            ->assertJsonPath('user.name', 'New Name');

        expect($user->refresh()->name)->toBe('New Name')
            ->and($user->hasVerifiedEmail())->toBeTrue();

        Mail::assertNothingSent();
    });

    it('marks the account unverified and emails an OTP when the email changes', function () {
        Sanctum::actingAs($user = User::factory()->create());

        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('email_changed', true)
            ->assertJsonPath('user.email', 'new@example.com');

        $user->refresh();

        expect($user->email)->toBe('new@example.com')
            ->and($user->hasVerifiedEmail())->toBeFalse()
            ->and($user->otps()->count())->toBe(1);

        // The code goes to the new address, not the old one.
        Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo('new@example.com'));
    });

    it('rejects an email another account already uses', function () {
        User::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs($user = User::factory()->create());

        $this->patchJson('/api/profile', ['name' => $user->name, 'email' => 'taken@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    });

    it('lets a user keep their own email address', function () {
        Sanctum::actingAs($user = User::factory()->create());

        $this->patchJson('/api/profile', ['name' => 'Renamed', 'email' => $user->email])
            ->assertOk();
    });

    it('rejects invalid input', function (array $payload, string $field) {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/profile', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [['email' => 'a@example.com'], 'name'],
        'missing email' => [['name' => 'Someone'], 'email'],
        'malformed email' => [['name' => 'Someone', 'email' => 'not-an-email'], 'email'],
        'uppercase email' => [['name' => 'Someone', 'email' => 'Mixed@Example.com'], 'email'],
        'overlong name' => [['name' => str_repeat('a', 256), 'email' => 'a@example.com'], 'name'],
    ]);

    it('requires authentication', function () {
        $this->patchJson('/api/profile', ['name' => 'X', 'email' => 'x@example.com'])
            ->assertUnauthorized();
    });
});

describe('changing the password', function () {
    it('replaces the password once the current one is confirmed', function () {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/profile/password', [
            'current_password' => 'password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertOk();

        expect(Hash::check('brand-new-password', $user->refresh()->password))->toBeTrue();
    });

    it('rejects a wrong current password', function () {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/profile/password', [
            'current_password' => 'not-my-password',
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
    });

    it('rejects a weak or unconfirmed new password', function (array $payload) {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile/password', ['current_password' => 'password', ...$payload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    })->with([
        'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
        'unconfirmed' => [['password' => 'brand-new-password', 'password_confirmation' => 'something-else']],
        'missing' => [[]],
    ]);

    // Google-only accounts have no password yet, so they are setting one for the
    // first time rather than replacing one.
    it('lets a passwordless Google account set a first password without the current one', function () {
        Sanctum::actingAs($user = User::factory()->create(['password' => null, 'google_id' => 'g-1']));

        $this->putJson('/api/profile/password', [
            'password' => 'first-real-password',
            'password_confirmation' => 'first-real-password',
        ])->assertOk();

        expect(Hash::check('first-real-password', $user->refresh()->password))->toBeTrue();
    });

    it('requires the current password once the account has one', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/profile/password', [
            'password' => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');
    });

    it('requires authentication', function () {
        $this->putJson('/api/profile/password', [])->assertUnauthorized();
    });
});

describe('changing the avatar', function () {
    it('stores the upload and returns its URL', function () {
        Sanctum::actingAs($user = User::factory()->create());

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')])
            ->assertOk()
            ->assertJsonPath('user.avatar', fn ($url) => str_contains($url, 'avatars/'));

        $path = $user->refresh()->avatar;

        expect($path)->toStartWith('avatars/')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    });

    it('deletes the previous avatar file', function () {
        Sanctum::actingAs($user = User::factory()->create(['avatar' => 'avatars/old.jpg']));
        Storage::disk('public')->put('avatars/old.jpg', 'binary');

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('new.jpg')])
            ->assertOk();

        expect(Storage::disk('public')->exists('avatars/old.jpg'))->toBeFalse()
            ->and($user->refresh()->avatar)->not->toBe('avatars/old.jpg');
    });

    // A Google avatar is a remote URL, so there is no local file to remove.
    it('replaces an external Google avatar without trying to delete a file', function () {
        $remote = 'https://lh3.googleusercontent.com/a/photo.jpg';
        Sanctum::actingAs($user = User::factory()->create(['avatar' => $remote]));

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('new.jpg')])
            ->assertOk();

        expect($user->refresh()->avatar)->toStartWith('avatars/');
    });

    it('rejects an invalid upload', function (UploadedFile $file) {
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/profile/avatar', ['avatar' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');

        expect($user->refresh()->avatar)->toBeNull();
    })->with([
        'not an image' => fn () => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
        'disallowed type' => fn () => UploadedFile::fake()->create('animation.gif', 10, 'image/gif'),
        'over the size cap' => fn () => UploadedFile::fake()->image('huge.jpg')->size(3000),
    ]);

    it('requires a file', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/profile/avatar', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    });

    it('requires authentication', function () {
        $this->postJson('/api/profile/avatar', [])->assertUnauthorized();
    });
});
