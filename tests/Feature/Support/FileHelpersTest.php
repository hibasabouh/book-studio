<?php

use App\Enums\BookFont;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

describe('file_url', function () {
    it('builds a public storage URL for a stored path', function () {
        expect(file_url('avatars/me.jpg'))->toBe(asset('storage/avatars/me.jpg'));
    });

    it('does not double up the slash on a leading-slash path', function () {
        expect(file_url('/avatars/me.jpg'))->toBe(asset('storage/avatars/me.jpg'));
    });

    it('passes an absolute URL through untouched', function (string $url) {
        expect(file_url($url))->toBe($url);
    })->with([
        'http' => 'http://example.com/a.jpg',
        'https' => 'https://lh3.googleusercontent.com/a/photo.jpg',
    ]);

    it('returns null for an empty path', function (?string $path) {
        expect(file_url($path))->toBeNull();
    })->with([null, '']);
});

describe('upload_file', function () {
    it('stores the file in the given folder and returns its path', function () {
        $path = upload_file(UploadedFile::fake()->image('portrait.jpg'), 'avatar', 'avatars');

        expect($path)->toStartWith('avatars/')
            ->and(Storage::disk('public')->exists($path))->toBeTrue();
    });

    it('renames the file to a prefixed uuid, keeping the extension', function () {
        $path = upload_file(UploadedFile::fake()->image('portrait.png'), 'avatar', 'avatars');

        expect(basename($path))->toMatch('/^avatar_[0-9a-f-]{36}\.png$/');
    });

    it('never reuses a name, so two uploads of the same file both survive', function () {
        $first = upload_file(UploadedFile::fake()->image('same.jpg'), 'avatar', 'avatars');
        $second = upload_file(UploadedFile::fake()->image('same.jpg'), 'avatar', 'avatars');

        expect($first)->not->toBe($second)
            ->and(Storage::disk('public')->exists($first))->toBeTrue()
            ->and(Storage::disk('public')->exists($second))->toBeTrue();
    });
});

describe('delete_file_if_exist', function () {
    it('deletes an existing file and reports true', function () {
        Storage::disk('public')->put('avatars/old.jpg', 'binary');

        expect(delete_file_if_exist('avatars/old.jpg'))->toBeTrue()
            ->and(Storage::disk('public')->exists('avatars/old.jpg'))->toBeFalse();
    });

    it('reports false for a path that is not on disk', function () {
        expect(delete_file_if_exist('avatars/never-existed.jpg'))->toBeFalse();
    });

    it('reports false for an empty path rather than erroring', function (?string $path) {
        expect(delete_file_if_exist($path))->toBeFalse();
    })->with([null, '']);
});

describe('bundled PDF fonts', function () {
    // A missing face silently falls back to a default font in the exported PDF.
    it('ships every TTF file the mpdf font data names', function () {
        foreach (BookFont::mpdfFontData() as $entry) {
            expect(storage_path('fonts/'.$entry['R']))->toBeReadableFile()
                ->and(storage_path('fonts/'.$entry['B']))->toBeReadableFile();
        }
    });
});
