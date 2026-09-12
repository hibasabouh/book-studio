<?php

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\ContactCategory;
use App\Enums\OtpChannel;
use App\Enums\OtpPurpose;
use App\Enums\PdfTemplate;
use App\Enums\Role;
use App\Enums\TextDirection;
use App\Enums\UserStatus;

/**
 * Every backed enum in the app, so the shared shape checks below stay exhaustive
 * as cases are added.
 */
function labelledEnums(): array
{
    return [BookFont::class, BookLanguage::class, ContactCategory::class, PdfTemplate::class, Role::class, TextDirection::class, UserStatus::class];
}

describe('enum labels', function () {
    it('gives every case a non-empty label', function (string $enum) {
        foreach ($enum::cases() as $case) {
            expect($case->label())->toBeString()->not->toBe('');
        }
    })->with(labelledEnums());

    it('keeps every case value unique and lowercase-snake', function (string $enum) {
        $values = array_column($enum::cases(), 'value');

        expect($values)->toBe(array_unique($values))
            ->and($values)->each->toMatch('/^[a-z0-9_]+$/');
    })->with([...labelledEnums(), OtpChannel::class, OtpPurpose::class]);
});

describe('BookFont', function () {
    it('classifies every case as latin or arabic', function () {
        foreach (BookFont::cases() as $font) {
            expect($font->script())->toBeIn(['latin', 'arabic'])
                ->and($font->isArabic())->toBe($font->script() === 'arabic');
        }
    });

    it('mirrors the label as the CSS family name', function () {
        foreach (BookFont::cases() as $font) {
            expect($font->family())->toBe($font->label());
        }
    });

    it('derives a unique lowercase mpdf key for every case', function () {
        $keys = array_map(fn (BookFont $f) => $f->mpdfFamily(), BookFont::cases());

        expect($keys)->toBe(array_unique($keys))
            ->and($keys)->each->toMatch('/^[a-z]+$/');
    });

    it('builds mpdf font data for every case', function () {
        $data = BookFont::mpdfFontData();

        expect($data)->toHaveCount(count(BookFont::cases()));

        foreach (BookFont::cases() as $font) {
            $entry = $data[$font->mpdfFamily()];

            expect($entry['R'])->toBe($font->mpdfFamily().'-Regular.ttf')
                ->and($entry['B'])->toBe($font->mpdfFamily().'-Bold.ttf');
        }
    });

    it('enables OpenType shaping and kashida only for Arabic families', function () {
        $data = BookFont::mpdfFontData();

        foreach (BookFont::cases() as $font) {
            $entry = $data[$font->mpdfFamily()];

            $font->isArabic()
                ? expect($entry)->toMatchArray(['useOTL' => 0xFF, 'useKashida' => 75])
                : expect($entry)->not->toHaveKeys(['useOTL', 'useKashida']);
        }
    });

});

describe('BookLanguage', function () {
    it('treats only Arabic as an Arabic-script language', function (BookLanguage $language) {
        expect($language->isArabic())->toBe($language === BookLanguage::Ar);
    })->with(BookLanguage::cases());

    it('translates the same template label keys for every language', function () {
        $keys = ['toc', 'chapter', 'chapter_label', 'author', 'chapters_count', 'contents'];

        foreach (BookLanguage::cases() as $language) {
            expect($language->templateLabels())->toHaveKeys($keys);
        }
    });

    it('gives Arabic and French their own labels rather than the English default', function () {
        $english = BookLanguage::En->templateLabels();

        expect(BookLanguage::Ar->templateLabels()['toc'])->not->toBe($english['toc'])
            ->and(BookLanguage::Fr->templateLabels()['toc'])->not->toBe($english['toc']);
    });
});

describe('TextDirection', function () {
    it('reports RTL only for the rtl case', function (TextDirection $direction) {
        expect($direction->isRtl())->toBe($direction === TextDirection::Rtl);
    })->with(TextDirection::cases());
});

describe('UserStatus', function () {
    it('blocks every status except active', function (UserStatus $status) {
        $status === UserStatus::Active
            ? expect($status->blockMessageKey())->toBeNull()
            : expect($status->blockMessageKey())->toBeString()->toStartWith('auth.');
    })->with(UserStatus::cases());

    it('gives each blocked status its own message key', function () {
        expect(UserStatus::Inactive->blockMessageKey())
            ->not->toBe(UserStatus::Suspended->blockMessageKey());
    });
});

describe('Role', function () {
    it('has exactly the admin and user roles', function () {
        expect(array_column(Role::cases(), 'value'))->toBe(['admin', 'user']);
    });
});
