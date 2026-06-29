<?php

namespace App\Support;

use App\Enums\BookFont;

/**
 * Renders a book's text with two fonts at once: the document body font for the
 * book's primary script, and the user's chosen font for the *other* script.
 *
 * A book has one primary script (its language) plus a Latin/Arabic font pair.
 * Runs in the opposite script — e.g. an Arabic quote inside an English book, or
 * an English term inside an Arabic book — are wrapped in a span carrying the
 * secondary font family so mpdf selects the right glyphs (and OpenType shaping
 * for Arabic). Everything else inherits the body font from CSS.
 *
 * The wrap is applied to individual text values rather than the rendered page so
 * it never touches markup or the Latin font names inside the stylesheet.
 */
class ScriptFontFormatter
{
    /** Arabic letters, diacritics, Arabic-Indic digits and presentation forms. */
    private const ARABIC = '\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}';

    /** Basic Latin letters plus Latin-1/extended (accented French letters). */
    private const LATIN = 'a-zA-Z\x{00C0}-\x{024F}';

    private readonly string $secondaryFamily;

    private readonly string $pattern;

    public function __construct(BookFont $latinFont, BookFont $arabicFont, bool $primaryIsArabic)
    {
        [$secondary, $script] = $primaryIsArabic
            ? [$latinFont, self::LATIN]
            : [$arabicFont, self::ARABIC];

        $this->secondaryFamily = $secondary->mpdfFamily();
        $this->pattern = '/['.$script.']+/u';
    }

    /** Escape and wrap secondary-script runs; for single-line values. */
    public function inline(?string $text): string
    {
        return $this->wrap(e((string) $text));
    }

    /** Like {@see inline()} but converts newlines to <br> for body content. */
    public function block(?string $text): string
    {
        return nl2br($this->wrap(e((string) $text)));
    }

    private function wrap(string $escaped): string
    {
        return preg_replace(
            $this->pattern,
            '<span style="font-family: '.$this->secondaryFamily.'">$0</span>',
            $escaped
        );
    }
}
