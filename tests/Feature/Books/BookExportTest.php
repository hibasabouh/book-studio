<?php

use App\Enums\ExportFailure;
use App\Enums\ExportStatus;
use App\Enums\PdfTemplate;
use App\Jobs\GenerateBookPdf;
use App\Models\Book;
use App\Models\BookExport;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use App\Services\BookExportService;
use Database\Factories\BookSettingFactory;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Mpdf\MpdfException;

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

beforeEach(function () {
    Storage::fake(BookExport::DISK);
});

describe('queued PDF export', function () {
    it('queues an export job for the book owner', function () {
        Queue::fake();
        $book = Book::factory()->has(BookSetting::factory(), 'setting')->create();
        Sanctum::actingAs($book->user);

        $this->postJson("/api/books/{$book->id}/exports")
            ->assertAccepted()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.book_title', $book->title)
            ->assertJsonPath('data.download_url', null)
            ->assertJsonPath('data.error', null);

        $export = BookExport::sole();
        expect($export->book_id)->toBe($book->id)
            ->and($export->user_id)->toBe($book->user_id);

        Queue::assertPushed(GenerateBookPdf::class, fn (GenerateBookPdf $job) => $job->export->is($export));
    });

    it('returns the export already in progress instead of queueing another', function () {
        Queue::fake();
        $book = Book::factory()->has(BookSetting::factory(), 'setting')->create();
        Sanctum::actingAs($book->user);

        $first = $this->postJson("/api/books/{$book->id}/exports")->json('data.id');
        $second = $this->postJson("/api/books/{$book->id}/exports")->json('data.id');

        expect($second)->toBe($first);
        Queue::assertPushed(GenerateBookPdf::class, 1);
    });

    it('queues a fresh export once the previous one has finished', function (string $state) {
        Queue::fake();
        $previous = BookExport::factory()->{$state}()->create();
        Sanctum::actingAs($previous->user);

        $this->postJson("/api/books/{$previous->book_id}/exports")
            ->assertAccepted()
            ->assertJsonPath('data.status', 'pending');

        expect(BookExport::count())->toBe(2);
        Queue::assertPushed(GenerateBookPdf::class, 1);
    })->with(['completed', 'failed']);

    it('runs the job to completion and offers the PDF for download', function () {
        $book = Book::factory()
            ->has(BookSetting::factory(), 'setting')
            ->has(Chapter::factory()->count(2), 'chapters')
            ->create();
        Sanctum::actingAs($book->user);

        $id = $this->postJson("/api/books/{$book->id}/exports")->json('data.id');

        $status = $this->getJson("/api/exports/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.error', null);

        $download = $this->get($status->json('data.download_url'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        expect($download->headers->get('Content-Disposition'))
            ->toContain('attachment')
            ->toContain(Str::slug($book->title, '-').'.pdf')
            ->and($download->streamedContent())->toStartWith('%PDF');
    });

    it('links every table-of-contents entry to its chapter page', function (PdfTemplate $template) {
        Sanctum::actingAs($owner = User::factory()->create());

        $book = bookWithThreeChapters($owner, BookSetting::factory()->state(['template' => $template]));

        $pdf = $this->exportBook($book)->assertOk()->streamedContent();

        // Cover, table of contents, then one page per chapter.
        expect(linkedPages($pdf))->toBe([3, 4, 5]);
    })->with(PdfTemplate::cases());

    // mpdf drops its own <a href="#x">/<a name="x"> targets once bidi text is
    // involved, sending every link to page 1 — see BookExportService.
    it('links the table of contents in an RTL Arabic book', function () {
        Sanctum::actingAs($owner = User::factory()->create());

        $book = bookWithThreeChapters($owner, BookSetting::factory()->arabic(), 'الفصل');

        $pdf = $this->exportBook($book)->assertOk()->streamedContent();

        expect(linkedPages($pdf))->toBe([3, 4, 5]);
    });
});

describe('export failures', function () {
    it('marks the export failed and explains why', function (Throwable $exception, ExportFailure $failure) {
        $this->mock(BookExportService::class, fn (MockInterface $mock) => $mock
            ->shouldReceive('generatePdf')->andThrow($exception));

        $export = BookExport::factory()->create();
        Sanctum::actingAs($export->user);

        // The sync queue runs the job's failed() hook, then rethrows.
        expect(fn () => Bus::dispatch(new GenerateBookPdf($export)))->toThrow($exception::class);

        expect($export->fresh())
            ->status->toBe(ExportStatus::Failed)
            ->failure->toBe($failure);

        $this->getJson("/api/exports/{$export->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.error', __($failure->messageKey()))
            ->assertJsonPath('data.download_url', null);

        expect(Storage::disk(BookExport::DISK)->allFiles())->toBeEmpty();
    })->with([
        'mpdf rendering error' => fn () => [new MpdfException('Unsupported image type'), ExportFailure::RenderFailed],
        'anything else' => fn () => [new RuntimeException('Disk full'), ExportFailure::Unexpected],
    ]);

    it('reports a timeout when the worker gives up on the job', function (Throwable $exception) {
        $export = BookExport::factory()->create(['status' => ExportStatus::Processing]);

        (new GenerateBookPdf($export))->failed($exception);

        expect($export->fresh())
            ->status->toBe(ExportStatus::Failed)
            ->failure->toBe(ExportFailure::TimedOut);
    })->with([
        'timed out' => fn () => new TimeoutExceededException('Job has timed out.'),
        'retried after a worker crash' => fn () => new MaxAttemptsExceededException('Job has been attempted too many times.'),
    ]);

    it('has a message for every failure reason in every locale', function (ExportFailure $failure, string $locale) {
        expect(trans($failure->messageKey(), [], $locale))->not->toBe($failure->messageKey());
    })->with(ExportFailure::cases())->with(['en', 'ar', 'fr']);

    it("translates the failure message into the requester's locale", function () {
        $export = BookExport::factory()->failed(ExportFailure::RenderFailed)->create();
        Sanctum::actingAs($export->user);

        $this->getJson("/api/exports/{$export->id}", ['X-Locale' => 'fr'])
            ->assertJsonPath('data.error', trans('messages.export_failed_render_failed', [], 'fr'));
    });

    it('refuses to download an export that has not completed', function (ExportStatus $status) {
        $export = BookExport::factory()->create(['status' => $status]);
        Sanctum::actingAs($export->user);

        $this->getJson("/api/exports/{$export->id}/download")
            ->assertStatus(409)
            ->assertJsonPath('message', __('messages.export_not_ready'));
    })->with([ExportStatus::Pending, ExportStatus::Processing, ExportStatus::Failed]);

    it('reports an export whose file has been cleaned up as gone', function () {
        $export = BookExport::factory()->completed('exports/missing.pdf')->create();
        Sanctum::actingAs($export->user);

        $this->getJson("/api/exports/{$export->id}/download")
            ->assertStatus(410)
            ->assertJsonPath('message', __('messages.export_expired'));
    });

    it('drops the job quietly when the book is deleted before it runs', function () {
        $export = BookExport::factory()->create();
        $job = new GenerateBookPdf($export);

        $export->book->delete();

        expect(fn () => Bus::dispatch($job))->not->toThrow(Throwable::class)
            ->and(BookExport::count())->toBe(0);
    });
});

describe('export authorization', function () {
    it("denies queueing an export of someone else's book", function () {
        $book = Book::factory()->has(BookSetting::factory(), 'setting')->create();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/books/{$book->id}/exports")->assertForbidden();
        expect(BookExport::count())->toBe(0);
    });

    it("denies reading someone else's export", function (string $suffix) {
        $export = BookExport::factory()->completed()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/exports/{$export->id}{$suffix}")->assertForbidden();
    })->with(['status' => '', 'download' => '/download']);

    it("lets an admin read anyone's export", function () {
        $export = BookExport::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/exports/{$export->id}")->assertOk();
    });

    it('requires authentication', function (string $method, string $uri) {
        $export = BookExport::factory()->create();

        $this->json($method, str_replace(['{book}', '{export}'], [$export->book_id, $export->id], $uri))
            ->assertUnauthorized();
    })->with([
        'queue' => ['POST', '/api/books/{book}/exports'],
        'status' => ['GET', '/api/exports/{export}'],
        'download' => ['GET', '/api/exports/{export}/download'],
    ]);
});

describe('export cleanup', function () {
    it('prunes day-old exports together with their files', function () {
        $disk = Storage::disk(BookExport::DISK);
        $old = BookExport::factory()->completed('exports/old.pdf')->create(['created_at' => now()->subDays(2)]);
        $recent = BookExport::factory()->completed('exports/recent.pdf')->create();
        $disk->put('exports/old.pdf', '%PDF');
        $disk->put('exports/recent.pdf', '%PDF');

        $this->artisan('model:prune', ['--model' => [BookExport::class]])->assertSuccessful();

        expect(BookExport::find($old->id))->toBeNull()
            ->and(BookExport::find($recent->id))->not->toBeNull()
            ->and($disk->exists('exports/old.pdf'))->toBeFalse()
            ->and($disk->exists('exports/recent.pdf'))->toBeTrue();
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
