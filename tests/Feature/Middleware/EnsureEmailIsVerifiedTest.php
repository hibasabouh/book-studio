<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

/**
 * The `verified` alias guards no route today, so the middleware is exercised
 * through probe routes that mount it on both stacks.
 */
beforeEach(function () {
    Route::middleware(['web', 'auth', 'verified'])->get('/_probe/web/verified', fn () => 'ok');
    Route::middleware(['api', 'auth:sanctum', 'verified'])->get('/_probe/api/verified', fn () => response()->json(['ok' => true]));
});

describe('JSON clients', function () {
    it('lets a verified user through', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/_probe/api/verified')->assertOk()->assertJsonPath('ok', true);
    });

    it('blocks an unverified user with a 403', function () {
        Sanctum::actingAs(User::factory()->unverified()->create());

        $this->getJson('/_probe/api/verified')->assertForbidden();
    });

    it('blocks a guest before it ever checks verification', function () {
        $this->getJson('/_probe/api/verified')->assertUnauthorized();
    });
});

describe('browser clients', function () {
    it('lets a verified user through', function () {
        $this->actingAs(User::factory()->create())
            ->get('/_probe/web/verified')
            ->assertOk();
    });

    it('redirects an unverified user to the OTP notice page', function () {
        $this->actingAs(User::factory()->unverified()->create())
            ->get('/_probe/web/verified')
            ->assertRedirect(route('verification.notice'));
    });
});

describe('after verifying', function () {
    it('admits the user once the email is verified', function () {
        $user = User::factory()->unverified()->create();

        Sanctum::actingAs($user);
        $this->getJson('/_probe/api/verified')->assertForbidden();

        $user->markEmailAsVerified();

        Sanctum::actingAs($user->fresh());
        $this->getJson('/_probe/api/verified')->assertOk();
    });
});
