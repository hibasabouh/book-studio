<?php

use App\Enums\UserStatus;
use App\Models\User;

describe('browser clients', function () {
    it('redirects a blocked user to the login page with an explanation', function (UserStatus $status) {
        $user = User::factory()->create(['status' => $status]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    })->with([
        'inactive' => UserStatus::Inactive,
        'suspended' => UserStatus::Suspended,
    ]);

    it('ends the session, so the block takes effect immediately', function () {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('login'));

        $this->assertGuest();
    });

    it('lets an active user through', function () {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
    });

    it('blocks a user suspended mid-session on their next request', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        // forceFill: status is deliberately not mass assignable.
        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect(route('login'));
    });

    // A blocked user must still be able to end their own session.
    it('leaves web logout reachable while blocked', function () {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($user)->postJson('/logout')->assertOk();

        $this->assertGuest();
    });

    it('guards every authenticated page', function (string $path) {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);

        $this->actingAs($user)->get($path)->assertRedirect(route('login'));
    })->with(['/dashboard', '/profile', '/books', '/admin', '/admin/users', '/admin/messages']);
});

describe('token clients', function () {
    it('revokes only the token used for the blocked request', function () {
        $user = User::factory()->create();
        $current = $user->createToken('current')->plainTextToken;
        $user->createToken('another');

        $user->forceFill(['status' => UserStatus::Suspended])->save();

        $this->withToken($current)->getJson('/api/user')->assertForbidden();

        expect($user->tokens()->count())->toBe(1)
            ->and($user->tokens()->sole()->name)->toBe('another');
    });

    it('leaves API logout reachable while blocked', function () {
        $user = User::factory()->create(['status' => UserStatus::Suspended]);
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')->assertOk();

        expect($user->tokens()->count())->toBe(0);
    });

    it('blocks every API endpoint behind the middleware', function (string $method, string $path) {
        $user = User::factory()->create(['status' => UserStatus::Inactive]);
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->json($method, $path)->assertForbidden();
    })->with([
        ['GET', '/api/user'],
        ['GET', '/api/books'],
        ['POST', '/api/books'],
        ['GET', '/api/dashboard/stats'],
        ['PATCH', '/api/profile'],
    ]);
});
