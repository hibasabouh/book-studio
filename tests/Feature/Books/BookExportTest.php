<?php

use App\Enums\PdfTemplate;
use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use Database\Factories\BookSettingFactory;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function bookWithThreeChapters(User $owner, BookSettingFactory $setting, string $word = 'Chapter'): Book
{
    return Book::factory()
        ->for($owner)
        ->has($setting, 'setting')
        ->has(Chapter::factory()->count(3)->sequence(
            fn ($sequence) => ['title' => "{$word} {$sequence->index}", 'content' => $word],
        ), 'chapters')
        ->create();
}

function linkedPages(string $pdf): array
{
    preg_match('#/Type /Pages\s*/Kids \[([^\]]+)\]#', $pdf, $tree);
    preg_match_all('/(\d+) 0 R/', $tree[1] ?? '', $pageObjects);

    $pageOfObject = array_flip($pageObjects[1]);

    preg_match_all('#/Subtype /Link[^>]*?/Dest \[(\d+) 0 R#s', $pdf, $destinations);

    $pages = array_map(
        // +1 for a page number rather than an index; null marks a dangling link.
        fn ($object) => isset($pageOfObject[$object]) ? $pageOfObject[$object] + 1 : null,
        array_unique($destinations[1])
    );

    sort($pages);

    return array_values($pages);
}

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

    it('links every table-of-contents entry to its chapter page', function (PdfTemplate $template) {
        Sanctum::actingAs($owner = User::factory()->create());

        $book = bookWithThreeChapters($owner, BookSetting::factory()->state(['template' => $template]));

        $pdf = $this->get("/api/books/{$book->id}/export")->assertOk()->getContent();

        // Cover, table of contents, then one page per chapter.
        expect(linkedPages($pdf))->toBe([3, 4, 5]);
    })->with(PdfTemplate::cases());

    // mpdf drops its own <a href="#x">/<a name="x"> targets once bidi text is
    // involved, sending every link to page 1 — see BookExportService.
    it('links the table of contents in an RTL Arabic book', function () {
        Sanctum::actingAs($owner = User::factory()->create());

        $book = bookWithThreeChapters($owner, BookSetting::factory()->arabic(), 'الفصل');

        $pdf = $this->get("/api/books/{$book->id}/export")->assertOk()->getContent();

        expect(linkedPages($pdf))->toBe([3, 4, 5]);
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

describe('PDF preview', function () {
    it('returns the book PDF as base64 JSON for its owner', function () {
        $owner = User::factory()->create();
        $book = Book::factory()
            ->for($owner)
            ->has(BookSetting::factory(), 'setting')
            ->has(Chapter::factory()->count(2), 'chapters')
            ->create();

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/books/{$book->id}/preview");

        $response->assertOk()->assertJsonStructure(['pdf']);

        // JSON, not application/pdf: a PDF body never survives the trip to the
        // browser intact — see BookExportController::preview().
        expect(base64_decode($response->json('pdf')))->toStartWith('%PDF');
    });

    it("denies previewing someone else's book", function () {
        $book = Book::factory()
            ->has(BookSetting::factory(), 'setting')
            ->create();

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/books/{$book->id}/preview")->assertForbidden();
    });

    it('requires authentication', function () {
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}/preview")->assertUnauthorized();
    });
});
