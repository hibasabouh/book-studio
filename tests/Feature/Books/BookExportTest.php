<?php

use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

describe('PDF export', function () {
    it('downloads the book as a PDF for its owner', function () {
        $owner = User::factory()->create();
        $book = Book::factory()
            ->for($owner)
            ->has(BookSetting::factory(), 'setting')
            ->has(Chapter::factory()->count(2), 'chapters')
            ->create();

        Sanctum::actingAs($owner);

        $response = $this->get("/api/books/{$book->id}/export");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        expect($response->headers->get('Content-Disposition'))
            ->toContain('attachment')
            ->toContain(Str::slug($book->title, '-').'.pdf');

        // A real PDF document was produced, not an empty or error body.
        expect($response->getContent())->toStartWith('%PDF');
    });

    it("denies exporting someone else's book", function () {
        $book = Book::factory()
            ->has(BookSetting::factory(), 'setting')
            ->create();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/books/{$book->id}/export")->assertForbidden();
    });

    it('requires authentication', function () {
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}/export")->assertUnauthorized();
    });
});
