<?php

use App\Enums\UserStatus;
use App\Models\User;

describe('API login', function () {
    it('issues a token with valid credentials', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.id', $user->id);

        expect($user->tokens()->count())->toBe(1);
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        expect($user->tokens()->count())->toBe(0);
    });

    it('requires email and password', function () {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('rejects blocked accounts with valid credentials', function (UserStatus $status) {
        $user = User::factory()->create(['status' => $status]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden();

        expect($user->tokens()->count())->toBe(0);
    })->with([
        'inactive' => UserStatus::Inactive,
        'suspended' => UserStatus::Suspended,
    ]);
});

describe('web login', function () {
    it('authenticates the session with valid credentials', function () {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);

        $this->assertAuthenticatedAs($user);
    });

    it('does not keep a session for blocked accounts', function () {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertForbidden();

        $this->assertGuest();
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create();

        $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    });
});

describe('logout', function () {
    it('revokes the current API token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        expect($user->tokens()->count())->toBe(0);
    });

    it('ends the web session', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/logout')
            ->assertOk();

        $this->assertGuest();
    });
});

describe('active middleware', function () {
    it('blocks and revokes the token of a user suspended mid-session', function () {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        // forceFill: status is deliberately not mass assignable.
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertForbidden();

        expect($user->tokens()->count())->toBe(0);
    });

    it('allows an active user through', function () {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    });
});
