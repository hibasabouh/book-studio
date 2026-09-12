<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('dashboard stats', function () {
    it('counts the books and chapters the user owns', function () {
        $user = User::factory()->create();

        Book::factory()->for($user)->has(Chapter::factory()->count(3), 'chapters')->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(1), 'chapters')->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('books_total', 2)
            ->assertJsonPath('chapters_total', 4)
            // A whole average serializes as an int, not 2.0.
            ->assertJsonPath('chapters_avg', 2);
    });

    it("ignores another user's books and chapters", function () {
        $user = User::factory()->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(2), 'chapters')->create();
        Book::factory()->has(Chapter::factory()->count(5), 'chapters')->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('books_total', 1)
            ->assertJsonPath('chapters_total', 2);
    });

    it('reports zeroes rather than dividing by zero for a new account', function () {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('books_total', 0)
            ->assertJsonPath('chapters_total', 0)
            ->assertJsonPath('chapters_avg', 0)
            ->assertJsonPath('recent_books', []);
    });

    it('rounds the chapter average to one decimal place', function () {
        $user = User::factory()->create();

        // 4 chapters over 3 books — 1.333... before rounding.
        Book::factory()->for($user)->has(Chapter::factory()->count(2), 'chapters')->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(1), 'chapters')->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(1), 'chapters')->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonPath('chapters_avg', 1.3);
    });

    it('returns at most five recent books, newest first', function () {
        $user = User::factory()->create();

        foreach (range(1, 7) as $i) {
            Book::factory()->for($user)->create(['title' => "Book {$i}", 'created_at' => now()->addMinutes($i)]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard/stats')->assertOk()->assertJsonCount(5, 'recent_books');

        expect(array_column($response->json('recent_books'), 'title'))
            ->toBe(['Book 7', 'Book 6', 'Book 5', 'Book 4', 'Book 3']);
    });

    it('includes the chapter count on each recent book', function () {
        $user = User::factory()->create();
        Book::factory()->for($user)->has(Chapter::factory()->count(3), 'chapters')->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('recent_books.0.chapters_count', 3);
    });

    it('requires authentication', function () {
        $this->getJson('/api/dashboard/stats')->assertUnauthorized();
    });
});
