<?php

use App\Models\ContactMessage;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('admin stats', function () {
    it('counts users, verified users and unread messages', function () {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        User::factory()->unverified()->count(3)->create();

        ContactMessage::factory()->count(2)->create();
        ContactMessage::factory()->read()->count(4)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/stats')
            ->assertOk()
            // 1 admin + 2 verified + 3 unverified
            ->assertJsonPath('users_total', 6)
            ->assertJsonPath('users_verified', 3)
            ->assertJsonPath('messages_unread', 2);
    });

    it('reports zeroes on an empty instance apart from the admin themselves', function () {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonPath('users_total', 1)
            ->assertJsonPath('messages_unread', 0);
    });

    it('returns at most five recent users, newest first', function () {
        $admin = User::factory()->admin()->create(['created_at' => now()->subDay()]);

        foreach (range(1, 7) as $i) {
            User::factory()->create(['name' => "User {$i}", 'created_at' => now()->addMinutes($i)]);
        }

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/stats')->assertOk()->assertJsonCount(5, 'recent_users');

        expect(array_column($response->json('recent_users'), 'name'))
            ->toBe(['User 7', 'User 6', 'User 5', 'User 4', 'User 3']);
    });

    it('never exposes password hashes in the recent users list', function () {
        Sanctum::actingAs(User::factory()->admin()->create());

        $response = $this->getJson('/api/admin/stats')->assertOk();

        expect($response->json('recent_users.0'))->not->toHaveKey('password');
    });

    it('denies a non-admin', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/stats')->assertForbidden();
    });

    it('requires authentication', function () {
        $this->getJson('/api/admin/stats')->assertUnauthorized();
    });
});
