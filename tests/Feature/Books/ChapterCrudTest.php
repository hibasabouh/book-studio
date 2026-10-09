<?php

use App\Models\Book;
use App\Models\Chapter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs($this->owner = User::factory()->create());
    $this->book = Book::factory()->for($this->owner)->create();
});

describe('creating a chapter', function () {
    it('stores the chapter against the book', function () {
        $this->postJson("/api/books/{$this->book->id}/chapters", [
            'title' => 'Chapter One',
            'content' => 'Once upon a time.',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Chapter One')
            ->assertJsonPath('data.content', 'Once upon a time.')
            ->assertJsonPath('data.book_id', $this->book->id);

        expect(Chapter::sole()->book_id)->toBe($this->book->id);
    });

    it('accepts a chapter with no content yet', function () {
        $this->postJson("/api/books/{$this->book->id}/chapters", ['title' => 'Draft'])
            ->assertCreated()
            ->assertJsonPath('data.content', null);
    });

    it('appends each new chapter after the last one', function () {
        $first = $this->postJson("/api/books/{$this->book->id}/chapters", ['title' => 'One'])
            ->assertCreated()->json('data.order');

        $second = $this->postJson("/api/books/{$this->book->id}/chapters", ['title' => 'Two'])
            ->assertCreated()->json('data.order');

        expect($second)->toBeGreaterThan($first);
    });

    it('appends after the highest existing order, not the chapter count', function () {
        Chapter::factory()->for($this->book)->create(['order' => 42]);

        $this->postJson("/api/books/{$this->book->id}/chapters", ['title' => 'Next'])
            ->assertCreated()
            ->assertJsonPath('data.order', 43);
    });

    it('numbers the first chapter of a book independently of other books', function () {
        Chapter::factory()->for(Book::factory()->for($this->owner)->create())->create(['order' => 99]);

        $order = $this->postJson("/api/books/{$this->book->id}/chapters", ['title' => 'First'])
            ->assertCreated()->json('data.order');

        expect($order)->toBeLessThan(99);
    });

    it('rejects invalid input', function (array $payload, string $field) {
        $this->postJson("/api/books/{$this->book->id}/chapters", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect(Chapter::count())->toBe(0);
    })->with([
        'missing title' => [[], 'title'],
        'blank title' => [['title' => ''], 'title'],
        'overlong title' => [['title' => str_repeat('a', 256)], 'title'],
        'overlong content' => [['title' => 'Long', 'content' => str_repeat('a', Chapter::MAX_CONTENT_LENGTH + 1)], 'content'],
    ]);

    it('404s when the book does not exist', function () {
        $this->postJson('/api/books/999999/chapters', ['title' => 'Orphan'])->assertNotFound();
    });
});

describe('listing chapters', function () {
    it('returns the chapters in their stored order, not by id', function () {
        $third = Chapter::factory()->for($this->book)->create(['title' => 'Third', 'order' => 3]);
        $first = Chapter::factory()->for($this->book)->create(['title' => 'First', 'order' => 1]);
        $second = Chapter::factory()->for($this->book)->create(['title' => 'Second', 'order' => 2]);

        $titles = array_column(
            $this->getJson("/api/books/{$this->book->id}/chapters")->assertOk()->json('data'),
            'title'
        );

        expect($titles)->toBe(['First', 'Second', 'Third']);
    });

    it("does not list another book's chapters", function () {
        Chapter::factory()->for($this->book)->create();
        Chapter::factory()->for(Book::factory()->for($this->owner)->create())->count(3)->create();

        $this->getJson("/api/books/{$this->book->id}/chapters")->assertOk()->assertJsonCount(1, 'data');
    });

    it('returns an empty list for a book with no chapters', function () {
        $this->getJson("/api/books/{$this->book->id}/chapters")->assertOk()->assertJsonCount(0, 'data');
    });
});

describe('reading a chapter', function () {
    it('returns the full chapter payload', function () {
        $chapter = Chapter::factory()->for($this->book)->create();

        $this->getJson("/api/chapters/{$chapter->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $chapter->id)
            ->assertJsonStructure(['data' => ['id', 'book_id', 'title', 'content', 'order', 'created_at', 'updated_at']]);
    });

    it('404s for an unknown chapter', function () {
        $this->getJson('/api/chapters/999999')->assertNotFound();
    });
});

describe('updating a chapter', function () {
    it('updates the title and content', function () {
        $chapter = Chapter::factory()->for($this->book)->create();

        $this->patchJson("/api/chapters/{$chapter->id}", [
            'title' => 'Revised',
            'content' => 'Rewritten body.',
        ])->assertOk()->assertJsonPath('data.title', 'Revised');

        expect($chapter->refresh()->content)->toBe('Rewritten body.');
    });

    // `sometimes` on the title lets the editor autosave body text alone.
    it('updates the content without resending the title', function () {
        $chapter = Chapter::factory()->for($this->book)->create(['title' => 'Kept']);

        $this->patchJson("/api/chapters/{$chapter->id}", ['content' => 'Autosaved body.'])->assertOk();

        $chapter->refresh();

        expect($chapter->content)->toBe('Autosaved body.')
            ->and($chapter->title)->toBe('Kept');
    });

    it('rejects a title that is present but blank', function () {
        $chapter = Chapter::factory()->for($this->book)->create(['title' => 'Kept']);

        $this->patchJson("/api/chapters/{$chapter->id}", ['title' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');

        expect($chapter->refresh()->title)->toBe('Kept');
    });

    it('rejects an overlong title', function () {
        $chapter = Chapter::factory()->for($this->book)->create();

        $this->patchJson("/api/chapters/{$chapter->id}", ['title' => str_repeat('a', 256)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('title');
    });

    it('accepts content at the length limit and rejects anything longer', function () {
        $chapter = Chapter::factory()->for($this->book)->create(['content' => 'Kept']);

        $this->patchJson("/api/chapters/{$chapter->id}", ['content' => str_repeat('a', Chapter::MAX_CONTENT_LENGTH + 1)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        expect($chapter->refresh()->content)->toBe('Kept');

        $this->patchJson("/api/chapters/{$chapter->id}", ['content' => str_repeat('a', Chapter::MAX_CONTENT_LENGTH)])
            ->assertOk();
    });

    it('does not let the order be changed through the update endpoint', function () {
        $chapter = Chapter::factory()->for($this->book)->create(['order' => 5]);

        $this->patchJson("/api/chapters/{$chapter->id}", ['title' => 'Same', 'order' => 99])->assertOk();

        expect($chapter->refresh()->order)->toBe(5);
    });
});

describe('deleting a chapter', function () {
    it('deletes the chapter', function () {
        $chapter = Chapter::factory()->for($this->book)->create();

        $this->deleteJson("/api/chapters/{$chapter->id}")->assertOk();

        $this->assertDatabaseMissing('chapters', ['id' => $chapter->id]);
    });

    it('leaves the sibling chapters in place', function () {
        $chapter = Chapter::factory()->for($this->book)->create();
        $sibling = Chapter::factory()->for($this->book)->create();

        $this->deleteJson("/api/chapters/{$chapter->id}")->assertOk();

        $this->assertDatabaseHas('chapters', ['id' => $sibling->id]);
    });
});

describe('reordering chapters', function () {
    it('rewrites the order to match the ids it was given', function () {
        $a = Chapter::factory()->for($this->book)->create(['title' => 'A', 'order' => 1]);
        $b = Chapter::factory()->for($this->book)->create(['title' => 'B', 'order' => 2]);
        $c = Chapter::factory()->for($this->book)->create(['title' => 'C', 'order' => 3]);

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", [
            'ids' => [$c->id, $a->id, $b->id],
        ])->assertOk();

        $titles = array_column(
            $this->getJson("/api/books/{$this->book->id}/chapters")->assertOk()->json('data'),
            'title'
        );

        expect($titles)->toBe(['C', 'A', 'B']);
    });

    // Otherwise a crafted payload could renumber chapters in books the caller
    // has no access to.
    it("ignores ids that belong to another book", function () {
        $mine = Chapter::factory()->for($this->book)->create(['order' => 1]);
        $theirs = Chapter::factory()->create(['order' => 7]);

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", [
            'ids' => [$theirs->id, $mine->id],
        ])->assertOk();

        expect($theirs->refresh()->order)->toBe(7)
            ->and($mine->refresh()->order)->toBe(1);
    });

    it('tolerates an id that no longer exists', function () {
        $chapter = Chapter::factory()->for($this->book)->create(['order' => 5]);

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", [
            'ids' => [999999, $chapter->id],
        ])->assertOk();

        expect($chapter->refresh()->order)->toBe(1);
    });

    it('rejects invalid input', function (array $payload, string $field) {
        $chapter = Chapter::factory()->for($this->book)->create(['order' => 3]);

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect($chapter->refresh()->order)->toBe(3);
    })->with([
        'missing ids' => [[], 'ids'],
        // `required` treats an empty array as absent.
        'empty ids' => [['ids' => []], 'ids'],
        'ids not an array' => [['ids' => 'not-an-array'], 'ids'],
        'non-integer id' => [['ids' => ['abc']], 'ids.0'],
    ]);

    it("denies reordering someone else's book", function () {
        $chapter = Chapter::factory()->for($this->book)->create(['order' => 1]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", ['ids' => [$chapter->id]])
            ->assertForbidden();

        expect($chapter->refresh()->order)->toBe(1);
    });

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->postJson("/api/books/{$this->book->id}/chapters/reorder", ['ids' => []])
            ->assertUnauthorized();
    });
});
