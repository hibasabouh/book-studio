<?php

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\PdfTemplate;
use App\Enums\TextDirection;
use App\Models\Book;
use App\Models\BookSetting;
use App\Models\Chapter;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Build a book whose settings row carries the given overrides, then export it.
 */
function exportWithSetting(array $overrides, int $chapters = 2): string
{
    $book = Book::factory()
        ->for(test()->owner)
        ->has(BookSetting::factory()->state($overrides), 'setting')
        ->has(Chapter::factory()->count($chapters), 'chapters')
        ->create();

    return test()->get("/api/books/{$book->id}/export")->assertOk()->getContent();
}

beforeEach(function () {
    Sanctum::actingAs($this->owner = User::factory()->create());
});

describe('settings toggles', function () {
    it('renders with the page numbers footer on or off', function (bool $enabled) {
        expect(exportWithSetting(['show_page_numbers' => $enabled]))->toStartWith('%PDF');
    })->with([true, false]);

    it('renders with the table of contents on or off', function (bool $enabled) {
        expect(exportWithSetting(['show_table_of_contents' => $enabled]))->toStartWith('%PDF');
    })->with([true, false]);

    it('renders with the chapter headers on or off', function (bool $enabled) {
        expect(exportWithSetting(['show_chapter_headers' => $enabled]))->toStartWith('%PDF');
    })->with([true, false]);

    it('renders with every toggle off at once', function () {
        expect(exportWithSetting([
            'show_page_numbers' => false,
            'show_table_of_contents' => false,
            'show_chapter_headers' => false,
        ]))->toStartWith('%PDF');
    });

    it('renders without an author or subtitle', function () {
        expect(exportWithSetting(['author' => null, 'subtitle' => null]))->toStartWith('%PDF');
    });
});

describe('typography', function () {
    it('renders with every bundled font as the Latin body face', function (string $font) {
        expect(exportWithSetting(['latin_font' => BookFont::from($font)], chapters: 1))
            ->toStartWith('%PDF');
    })->with(array_column(BookFont::cases(), 'value'));

    it('renders an Arabic book with every bundled Arabic face', function (BookFont $font) {
        expect(exportWithSetting([
            'arabic_font' => $font,
            'language' => BookLanguage::Ar,
            'text_direction' => TextDirection::Rtl,
        ], chapters: 1))->toStartWith('%PDF');
    })->with(array_filter(BookFont::cases(), fn (BookFont $f) => $f->isArabic()));

    it('renders at the font size and line height extremes', function (array $overrides) {
        expect(exportWithSetting($overrides, chapters: 1))->toStartWith('%PDF');
    })->with([
        'smallest' => [['font_size' => 8.0, 'line_height' => 1.0, 'chapter_header_font_size' => 8.0]],
        'largest' => [['font_size' => 72.0, 'line_height' => 3.0, 'chapter_header_font_size' => 72.0]],
    ]);
});

describe('languages', function () {
    it('renders every language in every template', function (string $language, string $template) {
        expect(exportWithSetting([
            'language' => BookLanguage::from($language),
            'template' => PdfTemplate::from($template),
        ], chapters: 1))->toStartWith('%PDF');
    })
        ->with(array_column(BookLanguage::cases(), 'value'))
        ->with(array_column(PdfTemplate::cases(), 'value'));
});

describe('unusual content', function () {
    it('renders a book with no chapters at all', function () {
        expect(exportWithSetting([], chapters: 0))->toStartWith('%PDF');
    });

    it('renders chapters whose body is empty', function () {
        $book = Book::factory()->for($this->owner)
            ->has(BookSetting::factory(), 'setting')
            ->has(Chapter::factory()->count(2)->state(['content' => null]), 'chapters')
            ->create();

        expect($this->get("/api/books/{$book->id}/export")->assertOk()->getContent())
            ->toStartWith('%PDF');
    });

    // The formatter escapes before mpdf sees the markup, so angle brackets in a
    // title must not be parsed as HTML.
    it('renders titles and content containing HTML characters', function () {
        $book = Book::factory()->for($this->owner)
            ->has(BookSetting::factory(), 'setting')
            ->has(Chapter::factory()->count(1)->state([
                'title' => 'A <b>bold</b> & "quoted" title',
                'content' => 'Body with <script>alert(1)</script> & entities.',
            ]), 'chapters')
            ->create(['title' => 'Book & <em>Co</em>']);

        expect($this->get("/api/books/{$book->id}/export")->assertOk()->getContent())
            ->toStartWith('%PDF');
    });

    it('renders a book mixing Arabic and Latin runs in both directions', function () {
        $book = Book::factory()->for($this->owner)
            ->has(BookSetting::factory()->arabic(), 'setting')
            ->has(Chapter::factory()->count(2)->state([
                'title' => 'الفصل الأول: Introduction',
                'content' => "نص عربي with English inline.\nAnd a second line.",
            ]), 'chapters')
            ->create(['title' => 'كتاب Mixed Script']);

        expect($this->get("/api/books/{$book->id}/export")->assertOk()->getContent())
            ->toStartWith('%PDF');
    });
});

describe('filename', function () {
    it('offers a transliterated ASCII name beside the original UTF-8 one', function (string $title, string $ascii, string $utf8) {
        $book = Book::factory()->for($this->owner)
            ->has(BookSetting::factory(), 'setting')
            ->create(['title' => $title]);

        $disposition = $this->get("/api/books/{$book->id}/export")
            ->assertOk()->headers->get('Content-Disposition');

        expect($disposition)->toContain("filename=\"{$ascii}\"")
            ->toContain("filename*=UTF-8''".rawurlencode($utf8));
    })->with([
        'plain' => ['The Long Winter', 'the-long-winter.pdf', 'The Long Winter.pdf'],
        // Str::slug transliterates Arabic rather than dropping it.
        'arabic' => ['رحلة', 'rhl.pdf', 'رحلة.pdf'],
        // Characters that are illegal in a filename are stripped from both.
        'illegal characters' => ['A/B:C*D?E"F<G>H|I', 'abcdefghi.pdf', 'ABCDEFGHI.pdf'],
    ]);

    it('falls back to a default name when nothing usable survives', function (string $title) {
        $book = Book::factory()->for($this->owner)
            ->has(BookSetting::factory(), 'setting')
            ->create(['title' => $title]);

        expect($this->get("/api/books/{$book->id}/export")->assertOk()->headers->get('Content-Disposition'))
            ->toContain('filename="book.pdf"')
            ->toContain("filename*=UTF-8''book.pdf");
    })->with(['???', '///']);

    it('marks the response as uncacheable, since it is rebuilt every time', function () {
        $book = Book::factory()->for($this->owner)->has(BookSetting::factory(), 'setting')->create();

        $this->get("/api/books/{$book->id}/export")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->getJson("/api/books/{$book->id}/preview")
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    });
});
