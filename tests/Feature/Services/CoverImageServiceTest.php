<?php

use App\Services\CoverImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The whole visible frame — what an untouched crop frame sends.
 */
function wholeFrame(): array
{
    return ['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0];
}

function storedSize(string $path): array
{
    [$width, $height] = getimagesize(Storage::disk('public')->path($path));

    return [$width, $height];
}

beforeEach(function () {
    Storage::fake('public');
    $this->covers = app(CoverImageService::class);
});

describe('aspect ratio', function () {
    it('reports the output width over height', function () {
        expect(CoverImageService::aspectRatio())
            ->toBe(CoverImageService::WIDTH / CoverImageService::HEIGHT);
    });

    it('is portrait, matching an A4 page', function () {
        expect(CoverImageService::aspectRatio())->toBeLessThan(1.0);
    });
});

describe('storing a cover', function () {
    it('stores the file under covers/ as a jpg', function () {
        $path = $this->covers->store(UploadedFile::fake()->image('cover.jpg', 1000, 1400), wholeFrame());

        expect($path)->toStartWith('covers/')->toEndWith('.jpg')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    });

    it('re-encodes to the fixed cover size whatever came in', function (int $width, int $height) {
        $path = $this->covers->store(UploadedFile::fake()->image('cover.jpg', $width, $height), wholeFrame());

        expect(storedSize($path))->toBe([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    })->with([
        'landscape' => [2000, 800],
        'portrait' => [800, 2000],
        'square' => [1200, 1200],
        'smaller than the output' => [300, 300],
    ]);

    it('accepts png and webp sources', function (string $name) {
        $path = $this->covers->store(UploadedFile::fake()->image($name, 900, 900), wholeFrame());

        expect(storedSize($path))->toBe([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    })->with(['cover.png', 'cover.webp']);

    it('writes a real JPEG, so EXIF and other metadata are stripped', function () {
        $path = $this->covers->store(UploadedFile::fake()->image('cover.jpg', 900, 900), wholeFrame());

        expect(getimagesize(Storage::disk('public')->path($path))[2])->toBe(IMAGETYPE_JPEG);
    });

    it('gives every upload its own name', function () {
        $first = $this->covers->store(UploadedFile::fake()->image('cover.jpg', 900, 900), wholeFrame());
        $second = $this->covers->store(UploadedFile::fake()->image('cover.jpg', 900, 900), wholeFrame());

        expect($first)->not->toBe($second);
    });
});

describe('crop rectangle', function () {
    it('accepts a partial rectangle', function () {
        $path = $this->covers->store(
            UploadedFile::fake()->image('cover.jpg', 2000, 2000),
            ['x' => 0.25, 'y' => 0.1, 'width' => 0.5, 'height' => 0.7],
        );

        expect(storedSize($path))->toBe([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    });

    // The rectangle is clamped to the source bounds, so a rounded-up or
    // malformed frame can never make GD read past the edge of the image.
    it('clamps a rectangle that runs past the source edge', function (array $crop) {
        $path = $this->covers->store(UploadedFile::fake()->image('cover.jpg', 800, 800), $crop);

        expect(storedSize($path))->toBe([CoverImageService::WIDTH, CoverImageService::HEIGHT]);
    })->with([
        'origin at the far corner' => [['x' => 1.0, 'y' => 1.0, 'width' => 1.0, 'height' => 1.0]],
        'offset plus full size' => [['x' => 0.9, 'y' => 0.9, 'width' => 1.0, 'height' => 1.0]],
        'zero size' => [['x' => 0.0, 'y' => 0.0, 'width' => 0.0, 'height' => 0.0]],
    ]);
});

describe('unreadable uploads', function () {
    it('rejects a file that is not a decodable image', function () {
        expect(fn () => $this->covers->store(
            UploadedFile::fake()->create('payload.jpg', 8, 'image/jpeg'),
            wholeFrame(),
        ))->toThrow(ValidationException::class);
    });

    it('rejects an image format GD cannot open here', function () {
        expect(fn () => $this->covers->store(
            UploadedFile::fake()->create('vector.svg', 8, 'image/svg+xml'),
            wholeFrame(),
        ))->toThrow(ValidationException::class);
    });

    it('stores nothing when the upload is rejected', function () {
        try {
            $this->covers->store(UploadedFile::fake()->create('payload.jpg', 8, 'image/jpeg'), wholeFrame());
        } catch (ValidationException) {
            // expected
        }

        expect(Storage::disk('public')->allFiles())->toBeEmpty();
    });
});
