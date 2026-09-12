<?php

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

/**
 * Every rate-limited auth route allows six attempts per minute.
 */
const ATTEMPTS_PER_MINUTE = 6;

beforeEach(function () {
    Mail::fake();
});

describe('password reset endpoints', function () {
    it('blocks a seventh forgot-password attempt within the minute', function () {
        $user = User::factory()->create();

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(429);
    });

    // Failed lookups must count too, or the limiter would not slow down someone
    // probing for which addresses have accounts.
    it('counts attempts that did not match an account', function () {
        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/forgot-password', ['email' => "nobody{$i}@example.com"])->assertNotFound();
        }

        $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertStatus(429);
    });

    it('blocks a seventh reset-password attempt within the minute', function () {
        $user = User::factory()->create();

        $payload = [
            'user_id' => $user->id,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ];

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/reset-password', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/reset-password', $payload)->assertStatus(429);
    });

    it('lets attempts through again after the window passes', function () {
        $user = User::factory()->create();

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertStatus(429);

        $this->travel(61)->seconds();

        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
    });

    // For a guest the throttle key is the caller's IP, not the route, so one
    // budget covers every rate-limited endpoint they can reach.
    it('shares one budget across the guest reset endpoints', function () {
        $user = User::factory()->create();

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
        }

        $this->postJson('/api/reset-password', [
            'user_id' => $user->id,
            'code' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertStatus(429);
    });
});

describe('email verification endpoints', function () {
    it('blocks a seventh verify-otp attempt within the minute', function () {
        Sanctum::actingAs(User::factory()->unverified()->create());

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/email/verify-otp', ['code' => '000000'])->assertUnprocessable();
        }

        $this->postJson('/api/email/verify-otp', ['code' => '000000'])->assertStatus(429);
    });

    it('blocks a seventh resend-otp attempt within the minute', function () {
        Sanctum::actingAs(User::factory()->unverified()->create());

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/email/resend-otp')->assertOk();
        }

        $this->postJson('/api/email/resend-otp')->assertStatus(429);
    });

    it('reports how long to wait once the limit is hit', function () {
        Sanctum::actingAs(User::factory()->unverified()->create());

        foreach (range(1, ATTEMPTS_PER_MINUTE) as $i) {
            $this->postJson('/api/email/resend-otp')->assertOk();
        }

        $this->postJson('/api/email/resend-otp')
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    });
});
