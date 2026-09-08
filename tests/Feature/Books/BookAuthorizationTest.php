<?php

use App\Models\Book;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('book policy over HTTP', function () {
    it('lets the owner view, update, and delete their book', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);

        $this->patchJson("/api/books/{$book->id}", ['title' => 'Updated Title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated Title');

        $this->deleteJson("/api/books/{$book->id}")->assertOk();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    });

    it("denies another user access to someone else's book", function () {
        $book = Book::factory()->create();
        $stranger = User::factory()->create();

        Sanctum::actingAs($stranger);

        $this->getJson("/api/books/{$book->id}")->assertForbidden();
        $this->patchJson("/api/books/{$book->id}", ['title' => 'Hijacked'])->assertForbidden();
        $this->deleteJson("/api/books/{$book->id}")->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => $book->title]);
    });

    it("lets an admin manage any user's book", function () {
        $book = Book::factory()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->getJson("/api/books/{$book->id}")->assertOk();

        $this->patchJson("/api/books/{$book->id}", ['title' => 'Admin Edit'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Admin Edit');

        $this->deleteJson("/api/books/{$book->id}")->assertOk();
    });

    it('only lists the authenticated user\'s own books', function () {
        $owner = User::factory()->create();
        Book::factory()->count(2)->for($owner)->create();
        Book::factory()->create();

        Sanctum::actingAs($owner);

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('requires authentication', function () {
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}")->assertUnauthorized();
    });
});
