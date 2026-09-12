<?php

use App\Enums\BookFont;
use App\Support\ScriptFontFormatter;

/**
 * mpdf family keys, not display names — the wrap emits the key.
 */
const LATIN_FAMILY = 'ebgaramond';
const ARABIC_FAMILY = 'amiri';

function formatter(bool $primaryIsArabic): ScriptFontFormatter
{
    return new ScriptFontFormatter(BookFont::EbGaramond, BookFont::Amiri, $primaryIsArabic);
}

function span(string $family, string $text): string
{
    return '<span style="font-family: '.$family.'">'.$text.'</span>';
}

describe('secondary-script wrapping', function () {
    it('wraps Arabic runs in a Latin-primary book', function () {
        expect(formatter(primaryIsArabic: false)->inline('Hello مرحبا world'))
            ->toBe('Hello '.span(ARABIC_FAMILY, 'مرحبا').' world');
    });

    it('wraps Latin runs in an Arabic-primary book', function () {
        expect(formatter(primaryIsArabic: true)->inline('مرحبا Hello مرحبا'))
            ->toBe('مرحبا '.span(LATIN_FAMILY, 'Hello').' مرحبا');
    });

    it('leaves text without any secondary-script run unwrapped', function () {
        expect(formatter(primaryIsArabic: false)->inline('Plain English only'))
            ->toBe('Plain English only');
    });

    it('wraps several separate runs independently', function () {
        expect(formatter(primaryIsArabic: false)->inline('a مرحبا b سلام c'))
            ->toBe('a '.span(ARABIC_FAMILY, 'مرحبا').' b '.span(ARABIC_FAMILY, 'سلام').' c');
    });

    it('wraps a run that starts the string', function () {
        expect(formatter(primaryIsArabic: false)->inline('مرحبا tail'))
            ->toBe(span(ARABIC_FAMILY, 'مرحبا').' tail');
    });

    it('treats accented Latin as primary script in a Latin book', function () {
        // French accents live in the LATIN range, so they must not be wrapped.
        expect(formatter(primaryIsArabic: false)->inline('déjà vu'))->toBe('déjà vu');
    });

    it('wraps accented Latin as secondary script in an Arabic book', function () {
        expect(formatter(primaryIsArabic: true)->inline('déjà'))
            ->toBe(span(LATIN_FAMILY, 'déjà'));
    });

    it('wraps Arabic-Indic digits but not ASCII digits', function () {
        // ASCII digits sit outside both ranges, so they stay in the primary font.
        expect(formatter(primaryIsArabic: false)->inline('42 ٤٢'))
            ->toBe('42 '.span(ARABIC_FAMILY, '٤٢'));
    });
});

describe('escaping', function () {
    it('escapes HTML in primary-script text', function () {
        expect(formatter(primaryIsArabic: false)->inline('<b>Bold</b> & "quoted"'))
            ->toBe('&lt;b&gt;Bold&lt;/b&gt; &amp; &quot;quoted&quot;');
    });

    it('escapes HTML inside a wrapped run', function () {
        expect(formatter(primaryIsArabic: true)->inline('<Hello>'))
            ->toBe('&lt;'.span(LATIN_FAMILY, 'Hello').'&gt;');
    });

    // Regression: escaping before matching would find the "quot" in &quot; and
    // split the span mid-entity, breaking the quote characters.
    it('does not wrap Latin letters that only appear inside an HTML entity', function () {
        expect(formatter(primaryIsArabic: true)->inline('مرحبا "hello"'))
            ->toBe('مرحبا &quot;'.span(LATIN_FAMILY, 'hello').'&quot;');
    });

    it('keeps an ampersand intact next to a wrapped run', function () {
        expect(formatter(primaryIsArabic: true)->inline('& salut'))
            ->toBe('&amp; '.span(LATIN_FAMILY, 'salut'));
    });
});

describe('block vs inline', function () {
    it('converts newlines to <br> in block content', function () {
        expect(formatter(primaryIsArabic: false)->block("first\nsecond"))
            ->toBe("first<br />\nsecond");
    });

    it('leaves newlines alone in inline content', function () {
        expect(formatter(primaryIsArabic: false)->inline("first\nsecond"))
            ->toBe("first\nsecond");
    });

    it('wraps runs across lines in block content', function () {
        expect(formatter(primaryIsArabic: false)->block("intro\nمرحبا"))
            ->toBe("intro<br />\n".span(ARABIC_FAMILY, 'مرحبا'));
    });
});

describe('empty input', function () {
    it('renders null and empty strings as an empty string', function (?string $value) {
        $formatter = formatter(primaryIsArabic: false);

        expect($formatter->inline($value))->toBe('')
            ->and($formatter->block($value))->toBe('');
    })->with([null, '']);
});
