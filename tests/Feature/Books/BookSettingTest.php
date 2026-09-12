<?php

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\PdfTemplate;
use App\Enums\TextDirection;
use App\Models\Book;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * A book with the default settings row the create endpoint would have made.
 */
function bookWithDefaultSetting(User $owner): Book
{
    $book = Book::factory()->for($owner)->create();
    $book->setting()->create();

    return $book;
}

beforeEach(function () {
    Sanctum::actingAs($this->owner = User::factory()->create());
    $this->book = bookWithDefaultSetting($this->owner);
});

describe('reading settings', function () {
    it('returns the schema defaults for a new book', function () {
        $this->getJson("/api/books/{$this->book->id}/setting")
            ->assertOk()
            ->assertJsonPath('data.template', 'classic')
            ->assertJsonPath('data.latin_font', 'eb_garamond')
            ->assertJsonPath('data.arabic_font', 'amiri')
            ->assertJsonPath('data.text_direction', 'ltr')
            ->assertJsonPath('data.font_size', 12)
            ->assertJsonPath('data.chapter_header_font_size', 18)
            ->assertJsonPath('data.line_height', 1.5)
            ->assertJsonPath('data.show_page_numbers', true)
            ->assertJsonPath('data.show_table_of_contents', true)
            ->assertJsonPath('data.show_chapter_headers', true);
    });

    it('returns every settings field', function () {
        $this->getJson("/api/books/{$this->book->id}/setting")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'template', 'latin_font', 'arabic_font', 'text_direction', 'language',
                'font_size', 'chapter_header_font_size', 'line_height', 'author', 'subtitle',
                'show_page_numbers', 'show_table_of_contents', 'show_chapter_headers', 'updated_at',
            ]]);
    });
});

describe('updating settings', function () {
    it('updates a single field and leaves the rest alone', function () {
        $this->patchJson("/api/books/{$this->book->id}/setting", ['template' => 'elegant'])
            ->assertOk()
            ->assertJsonPath('data.template', 'elegant')
            ->assertJsonPath('data.latin_font', 'eb_garamond');

        expect($this->book->setting->refresh()->template)->toBe(PdfTemplate::Elegant);
    });

    it('updates several fields at once', function () {
        $this->patchJson("/api/books/{$this->book->id}/setting", [
            'latin_font' => 'lora',
            'arabic_font' => 'cairo',
            'text_direction' => 'rtl',
            'language' => 'ar',
            'font_size' => 14.5,
            'line_height' => 2.0,
            'author' => 'Amelia Stone',
            'subtitle' => 'A winter tale',
            'show_page_numbers' => false,
        ])->assertOk();

        $setting = $this->book->setting->refresh();

        expect($setting->latin_font)->toBe(BookFont::Lora)
            ->and($setting->arabic_font)->toBe(BookFont::Cairo)
            ->and($setting->text_direction)->toBe(TextDirection::Rtl)
            ->and($setting->language)->toBe(BookLanguage::Ar)
            ->and($setting->font_size)->toBe(14.5)
            ->and($setting->line_height)->toBe(2.0)
            ->and($setting->author)->toBe('Amelia Stone')
            ->and($setting->subtitle)->toBe('A winter tale')
            ->and($setting->show_page_numbers)->toBeFalse();
    });

    it('accepts every template', function (string $template) {
        $this->patchJson("/api/books/{$this->book->id}/setting", ['template' => $template])
            ->assertOk()
            ->assertJsonPath('data.template', $template);
    })->with(array_column(PdfTemplate::cases(), 'value'));

    it('accepts every font in both slots', function (string $font) {
        $this->patchJson("/api/books/{$this->book->id}/setting", [
            'latin_font' => $font,
            'arabic_font' => $font,
        ])->assertOk();
    })->with(array_column(BookFont::cases(), 'value'));

    it('accepts every language', function (string $language) {
        $this->patchJson("/api/books/{$this->book->id}/setting", ['language' => $language])
            ->assertOk()
            ->assertJsonPath('data.language', $language);
    })->with(array_column(BookLanguage::cases(), 'value'));

    it('clears the optional author and subtitle when sent as null', function () {
        $this->patchJson("/api/books/{$this->book->id}/setting", ['author' => 'Someone'])->assertOk();

        $this->patchJson("/api/books/{$this->book->id}/setting", ['author' => null, 'subtitle' => null])
            ->assertOk()
            ->assertJsonPath('data.author', null)
            ->assertJsonPath('data.subtitle', null);
    });

    it('accepts an empty payload as a no-op', function () {
        $this->patchJson("/api/books/{$this->book->id}/setting", [])
            ->assertOk()
            ->assertJsonPath('data.template', 'classic');
    });

    it('accepts values at the range boundaries', function (array $payload) {
        $this->patchJson("/api/books/{$this->book->id}/setting", $payload)->assertOk();
    })->with([
        'smallest font' => [['font_size' => 8]],
        'largest font' => [['font_size' => 72]],
        'tightest lines' => [['line_height' => 1]],
        'loosest lines' => [['line_height' => 3]],
    ]);

    it('rejects invalid input', function (array $payload, string $field) {
        $this->patchJson("/api/books/{$this->book->id}/setting", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        expect($this->book->setting->refresh()->template)->toBe(PdfTemplate::Classic);
    })->with([
        'unknown template' => [['template' => 'gothic'], 'template'],
        'unknown latin font' => [['latin_font' => 'comic_sans'], 'latin_font'],
        'unknown arabic font' => [['arabic_font' => 'comic_sans'], 'arabic_font'],
        'unknown direction' => [['text_direction' => 'sideways'], 'text_direction'],
        'unknown language' => [['language' => 'de'], 'language'],
        'font too small' => [['font_size' => 7], 'font_size'],
        'font too large' => [['font_size' => 73], 'font_size'],
        'non-numeric font size' => [['font_size' => 'big'], 'font_size'],
        'header font too large' => [['chapter_header_font_size' => 100], 'chapter_header_font_size'],
        'lines too tight' => [['line_height' => 0.5], 'line_height'],
        'lines too loose' => [['line_height' => 4], 'line_height'],
        'overlong author' => [['author' => str_repeat('a', 256)], 'author'],
        'overlong subtitle' => [['subtitle' => str_repeat('a', 256)], 'subtitle'],
        'non-boolean toggle' => [['show_page_numbers' => 'maybe'], 'show_page_numbers'],
    ]);
});

describe('authorization', function () {
    it("denies a stranger reading or writing someone else's settings", function () {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/books/{$this->book->id}/setting")->assertForbidden();
        $this->patchJson("/api/books/{$this->book->id}/setting", ['template' => 'modern'])->assertForbidden();

        expect($this->book->setting->refresh()->template)->toBe(PdfTemplate::Classic);
    });

    it("lets an admin manage any book's settings", function () {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson("/api/books/{$this->book->id}/setting")->assertOk();
        $this->patchJson("/api/books/{$this->book->id}/setting", ['template' => 'modern'])->assertOk();
    });

    it('requires authentication', function () {
        $this->app['auth']->forgetGuards();

        $this->getJson("/api/books/{$this->book->id}/setting")->assertUnauthorized();
        $this->patchJson("/api/books/{$this->book->id}/setting", [])->assertUnauthorized();
    });
});
