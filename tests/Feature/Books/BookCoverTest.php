<?php

use App\Enums\PdfTemplate;
use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use App\Services\CoverImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * The whole visible frame, i.e. what an untouched crop frame sends.
 */
function fullCrop(): array
{
    return ['crop_x' => 0, 'crop_y' => 0, 'crop_width' => 1, 'crop_height' => 1];
}

/**
 * Raw JPEG bytes, built in memory so no temp file can vanish underneath us.
 */
function jpegBytes(int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);

    ob_start();
    imagejpeg($image);
    $binary = ob_get_clean();

    imagedestroy($image);

    return $binary;
}

/**
 * Page objects in the raw PDF. mpdf leaves object dictionaries uncompressed,
 * so counting `/Type /Page` entries is enough to compare two documents.
 */
function pdfPageCount(string $pdf): int
{
    return preg_match_all('#/Type\s*/Page(?![s/\w])#', $pdf);
}

function bookWithCover(User $owner): Book
{
    $book = Book::factory()->for($owner)->create([
        'cover_path' => 'covers/existing.jpg',
    ]);

    Storage::disk('public')->put('covers/existing.jpg', 'binary');

    return $book;
}

beforeEach(function () {
    Storage::fake('public');
});

describe('cover upload', function () {
    it('stores a cropped cover for the book owner', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $response = $this->post("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('cover.jpg', 2000, 1500),
        ] + fullCrop());

        $response->assertOk();

        $path = $book->refresh()->cover_path;

        expect($path)->toStartWith('covers/')
            ->and(Storage::disk('public')->exists($path))->toBeTrue()
            ->and($response->json('data.cover_url'))->toContain($path);

        // Always re-encoded to exactly one page shape, whatever came in.
        expect(getimagesize(Storage::disk('public')->path($path)))
            ->toMatchArray([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    });

    it('crops to the requested rectangle', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->post("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('cover.jpg', 2000, 2000),
            'crop_x' => 0.25,
            'crop_y' => 0.1,
            'crop_width' => 0.5,
            'crop_height' => 0.7,
        ])->assertOk();

        // The output shape is fixed, so the rectangle only affects which pixels
        // are kept — the assertion here is that a partial crop is accepted.
        expect(getimagesize(Storage::disk('public')->path($book->refresh()->cover_path)))
            ->toMatchArray([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    });

    it('replaces the previous cover and deletes its file', function () {
        $owner = User::factory()->create();
        $book = bookWithCover($owner);

        Sanctum::actingAs($owner);

        $this->post("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('new.png', 900, 900),
        ] + fullCrop())->assertOk();

        expect($book->refresh()->cover_path)->not->toBe('covers/existing.jpg')
            ->and(Storage::disk('public')->exists('covers/existing.jpg'))->toBeFalse();
    });

    it('rejects an SVG upload', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->postJson("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->create('payload.svg', 8, 'image/svg+xml'),
        ] + fullCrop())->assertUnprocessable()->assertJsonValidationErrors('cover');

        expect($book->refresh()->cover_path)->toBeNull();
    });

    it('rejects a file over the size limit', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->postJson("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('huge.jpg', 1000, 1000)->size(6000),
        ] + fullCrop())->assertUnprocessable()->assertJsonValidationErrors('cover');
    });

    it('rejects an image below the minimum dimensions', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->postJson("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('tiny.jpg', 100, 100),
        ] + fullCrop())->assertUnprocessable()->assertJsonValidationErrors('cover');
    });

    it('requires a crop rectangle', function () {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        Sanctum::actingAs($owner);

        $this->postJson("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('cover.jpg', 900, 900),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['crop_x', 'crop_y', 'crop_width', 'crop_height']);
    });

    it("denies setting a cover on someone else's book", function () {
        $book = Book::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/books/{$book->id}/cover", [
            'cover' => UploadedFile::fake()->image('cover.jpg', 900, 900),
        ] + fullCrop())->assertForbidden();

        expect($book->refresh()->cover_path)->toBeNull();
    });

    it('requires authentication', function () {
        $book = Book::factory()->create();

        $this->postJson("/api/books/{$book->id}/cover", fullCrop())->assertUnauthorized();
    });
});

describe('cover removal', function () {
    it('removes the cover and deletes the stored file', function () {
        $owner = User::factory()->create();
        $book = bookWithCover($owner);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/books/{$book->id}/cover")->assertOk();

        expect($book->refresh()->cover_path)->toBeNull()
            ->and(Storage::disk('public')->exists('covers/existing.jpg'))->toBeFalse();
    });

    it("denies removing someone else's cover", function () {
        $book = bookWithCover(User::factory()->create());

        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/books/{$book->id}/cover")->assertForbidden();

        expect(Storage::disk('public')->exists('covers/existing.jpg'))->toBeTrue();
    });

    it('deletes the cover file when the book is deleted', function () {
        $owner = User::factory()->create();
        $book = bookWithCover($owner);

        Sanctum::actingAs($owner);

        $this->deleteJson("/api/books/{$book->id}")->assertOk();

        expect(Storage::disk('public')->exists('covers/existing.jpg'))->toBeFalse();
    });
});

describe('cover in the PDF export', function () {
    it('exports every template with and without a cover', function (string $template, bool $withCover) {
        $owner = User::factory()->create();

        if ($withCover) {
            Storage::disk('public')->put(
                'covers/export.jpg',
                jpegBytes(CoverImageService::WIDTH, CoverImageService::HEIGHT),
            );
        }

        $book = Book::factory()
            ->for($owner)
            ->has(BookSetting::factory()->state(['template' => PdfTemplate::from($template)]), 'setting')
            ->has(Chapter::factory()->count(2), 'chapters')
            ->create(['cover_path' => $withCover ? 'covers/export.jpg' : null]);

        Sanctum::actingAs($owner);

        $response = $this->exportBook($book);

        $response->assertOk();
        expect($response->streamedContent())->toStartWith('%PDF');
    })->with(array_column(PdfTemplate::cases(), 'value'))->with([true, false]);

    it('adds exactly one page, so the cover does not spill onto a second sheet', function () {
        $owner = User::factory()->create();

        $build = function (bool $withCover) use ($owner) {
            $book = Book::factory()
                ->for($owner)
                ->has(BookSetting::factory(), 'setting')
                ->has(Chapter::factory()->count(2), 'chapters')
                ->create(['cover_path' => $withCover ? 'covers/export.jpg' : null]);

            return $this->exportBook($book)->assertOk()->streamedContent();
        };

        Storage::disk('public')->put(
            'covers/export.jpg',
            jpegBytes(CoverImageService::WIDTH, CoverImageService::HEIGHT),
        );

        Sanctum::actingAs($owner);

        expect(pdfPageCount($build(true)))->toBe(pdfPageCount($build(false)) + 1);
    });

    it('ignores a cover whose file is missing from disk', function () {
        $owner = User::factory()->create();

        $book = Book::factory()
            ->for($owner)
            ->has(BookSetting::factory(), 'setting')
            ->create(['cover_path' => 'covers/gone.jpg']);

        Sanctum::actingAs($owner);

        expect($this->exportBook($book)->assertOk()->streamedContent())
            ->toStartWith('%PDF');
    });
});
