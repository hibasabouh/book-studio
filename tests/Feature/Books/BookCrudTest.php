<?php

use App\Enums\PdfTemplate;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs($this->owner = User::factory()->create());
});

describe('creating a book', function () {
    it('creates the book with a default settings row', function () {
        $response = $this->postJson('/api/books', [
            'title' => 'The Long Winter',
            'description' => 'A story about snow.',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'The Long Winter')
            ->assertJsonPath('data.description', 'A story about snow.')
            ->assertJsonPath('data.chapters_count', 0)
            ->assertJsonPath('data.cover_url', null);

        $book = Book::sole();

        expect($book->user_id)->toBe($this->owner->id)
            ->and($book->setting)->not->toBeNull()
            // Defaults come from the schema, not the request.
            ->and($book->setting->template)->toBe(PdfTemplate::Classic)
            ->and($book->setting->show_page_numbers)->toBeTrue();
    });

    it('accepts a book without a description', function () {
        $this->postJson('/api/books', ['title' => 'Untitled'])
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    });

    it('cannot be assigned to another user through the request body', function () {
        $stranger = User::factory()->create();

        $this->postJson('/api/books', ['title' => 'Mine', 'user_id' => $stranger->id])
            ->assertCreated();

        expect(Book::sole()->user_id)->toBe($this->owner->id);
    });

    it('rejects invalid input', function (array $payload, string $field) {
        $this->postJson('/api/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect(Book::count())->toBe(0);
    })->with([
        'missing title' => [[], 'title'],
        'blank title' => [['title' => ''], 'title'],
        'overlong title' => [['title' => str_repeat('a', 256)], 'title'],
        'overlong description' => [['title' => 'Fine', 'description' => str_repeat('a', 2001)], 'description'],
    ]);

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/books', ['title' => 'Nope'])->assertUnauthorized();
    });
});

describe('listing books', function () {
    it('paginates and includes the chapter count', function () {
        Book::factory()->for($this->owner)->has(Chapter::factory()->count(3), 'chapters')->create();

        $this->getJson('/api/books')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.chapters_count', 3)
            ->assertJsonStructure(['data' => [['id', 'title', 'description', 'cover_url', 'chapters_count']], 'meta' => ['current_page', 'total']]);
    });

    it('honours per_page', function () {
        Book::factory()->for($this->owner)->count(10)->create();

        $this->getJson('/api/books?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.total', 10);
    });

    it('lists the newest books first', function () {
        Book::factory()->for($this->owner)->create(['title' => 'Older', 'created_at' => now()->subDay()]);
        Book::factory()->for($this->owner)->create(['title' => 'Newest', 'created_at' => now()->addDay()]);

        expect($this->getJson('/api/books')->assertOk()->json('data.0.title'))->toBe('Newest');
    });

    it('filters by a partial title', function () {
        Book::factory()->for($this->owner)->create(['title' => 'The Long Winter']);
        Book::factory()->for($this->owner)->create(['title' => 'Summer Days']);

        $this->getJson('/api/books?name=Winter')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'The Long Winter');
    });

    it('returns an empty page when nothing matches the filter', function () {
        Book::factory()->for($this->owner)->create(['title' => 'The Long Winter']);

        $this->getJson('/api/books?name=Nonexistent')->assertOk()->assertJsonCount(0, 'data');
    });

    it("never includes another user's books, even under a filter", function () {
        Book::factory()->create(['title' => 'Secret Winter']);

        $this->getJson('/api/books?name=Winter')->assertOk()->assertJsonCount(0, 'data');
    });

    it('rejects invalid query parameters', function (string $query, string $field) {
        $this->getJson("/api/books?{$query}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    })->with([
        'per_page above the cap' => ['per_page=500', 'per_page'],
        'per_page below one' => ['per_page=0', 'per_page'],
        'non-numeric per_page' => ['per_page=many', 'per_page'],
        'overlong name' => ['name='.str_repeat('a', 256), 'name'],
    ]);
});

describe('reading a book', function () {
    it('returns the book with its chapter count', function () {
        $book = Book::factory()->for($this->owner)->has(Chapter::factory()->count(2), 'chapters')->create();

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $book->id)
            ->assertJsonPath('data.chapters_count', 2);
    });

    it('404s for an unknown book', function () {
        $this->getJson('/api/books/999999')->assertNotFound();
    });
});

describe('updating a book', function () {
    it('updates the title and description', function () {
        $book = Book::factory()->for($this->owner)->create();

        $this->patchJson("/api/books/{$book->id}", [
            'title' => 'Revised Title',
            'description' => 'Revised description.',
        ])->assertOk()->assertJsonPath('data.title', 'Revised Title');

        expect($book->refresh()->description)->toBe('Revised description.');
    });

    it('clears the description when sent as null', function () {
        $book = Book::factory()->for($this->owner)->create(['description' => 'Something']);

        $this->patchJson("/api/books/{$book->id}", ['title' => $book->title, 'description' => null])
            ->assertOk();

        expect($book->refresh()->description)->toBeNull();
    });

    it('rejects invalid input', function (array $payload, string $field) {
        $book = Book::factory()->for($this->owner)->create(['title' => 'Original']);

        $this->patchJson("/api/books/{$book->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect($book->refresh()->title)->toBe('Original');
    })->with([
        'missing title' => [[], 'title'],
        'blank title' => [['title' => ''], 'title'],
        'overlong title' => [['title' => str_repeat('a', 256)], 'title'],
        'overlong description' => [['title' => 'Fine', 'description' => str_repeat('a', 2001)], 'description'],
    ]);
});

describe('deleting a book', function () {
    it('deletes the book and cascades to its chapters and settings', function () {
        $book = Book::factory()->for($this->owner)
            ->has(Chapter::factory()->count(2), 'chapters')
            ->create();
        $book->setting()->create();

        $this->deleteJson("/api/books/{$book->id}")->assertOk();

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseMissing('chapters', ['book_id' => $book->id]);
        $this->assertDatabaseMissing('book_settings', ['book_id' => $book->id]);
    });

    it("leaves other users' books alone", function () {
        $book = Book::factory()->for($this->owner)->create();
        $other = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")->assertOk();

        $this->assertDatabaseHas('books', ['id' => $other->id]);
    });
});
