<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('chapter policy over HTTP', function () {
    it('lets the book owner create, view, update, and delete chapters', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $created = $this->postJson("/api/books/{$book->id}/chapters", [
            'title' => 'Chapter One',
            'content' => 'Once upon a time.',
        ])->assertCreated();

        $chapterId = $created->json('data.id');

        $this->getJson("/api/books/{$book->id}/chapters")->assertOk();

        $this->getJson("/api/chapters/{$chapterId}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Chapter One');

        $this->patchJson("/api/chapters/{$chapterId}", ['title' => 'Chapter One (revised)'])
            ->assertOk();

        $this->deleteJson("/api/chapters/{$chapterId}")->assertOk();

        $this->assertDatabaseMissing('chapters', ['id' => $chapterId]);
    });

    it("denies another user access to chapters of someone else's book", function () {
        $book = Book::factory()->create();
        $chapter = Chapter::factory()->for($book)->create();
        $stranger = User::factory()->create();

        Sanctum::actingAs($stranger);

        $this->postJson("/api/books/{$book->id}/chapters", ['title' => 'Intruder'])
            ->assertForbidden();

        $this->getJson("/api/books/{$book->id}/chapters")->assertForbidden();
        $this->getJson("/api/chapters/{$chapter->id}")->assertForbidden();
        $this->patchJson("/api/chapters/{$chapter->id}", ['title' => 'Hijacked'])->assertForbidden();
        $this->deleteJson("/api/chapters/{$chapter->id}")->assertForbidden();

        $this->assertDatabaseHas('chapters', ['id' => $chapter->id, 'title' => $chapter->title]);
    });

    it("lets an admin manage any book's chapters", function () {
        $book = Book::factory()->create();
        $chapter = Chapter::factory()->for($book)->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->getJson("/api/chapters/{$chapter->id}")->assertOk();

        $this->patchJson("/api/chapters/{$chapter->id}", ['title' => 'Admin Edit'])
            ->assertOk();

        $this->deleteJson("/api/chapters/{$chapter->id}")->assertOk();
    });
});
