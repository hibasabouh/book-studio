<?php

namespace App\Services;

use App\Enums\BookFont;
use App\Models\Book;
use App\Models\Chapter;
use App\Support\ScriptFontFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class BookExportService
{
    private const PAGE_WIDTH = 210;

    private const PAGE_HEIGHT = 297;

    /**
     * @throws MpdfException
     */
    public function generatePdf(Book $book): string
    {
        $setting = $book->setting;
        $isRtl = $setting->text_direction->isRtl();
        $labels = $setting->language->templateLabels();

        $bodyFont = $setting->language->isArabic()
            ? $setting->arabic_font->mpdfFamily()
            : $setting->latin_font->mpdfFamily();

        $fmt = new ScriptFontFormatter(
            $setting->latin_font,
            $setting->arabic_font,
            $setting->language->isArabic(),
        );

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => $setting->font_size,
            'default_font' => $bodyFont,
            'margin_left' => 22,
            'margin_right' => 22,
            'margin_top' => 25,
            'margin_bottom' => 25,
            'margin_footer' => 10,
            'tempDir' => sys_get_temp_dir().'/mpdf',
            'fontDir' => array_merge($defaultConfig['fontDir'], [storage_path('fonts')]),
            'fontdata' => $defaultFontConfig['fontdata'] + BookFont::mpdfFontData(),
        ]);

        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
        }

        $coverImage = $this->coverImagePath($book);

        if ($coverImage) {
            $this->writeCoverPage($mpdf, $coverImage);
        }

        if ($setting->show_page_numbers) {
            $align = $isRtl ? 'right' : 'center';
            $mpdf->SetHTMLFooter(
                "<div style=\"text-align: {$align}; font-size: 9pt; color: #555;\">– {PAGENO} –</div>"
            );
        }

        $html = view("pdf.{$setting->template->value}", [
            'book' => $book,
            'chapters' => $book->chapters,
            'setting' => $setting,
            'isRtl' => $isRtl,
            'labels' => $labels,
            'bodyFont' => $bodyFont,
            'fmt' => $fmt,
        ])->render();

        $mpdf->WriteHTML($html);

        $this->linkTableOfContents($mpdf, $book->chapters);

        return $mpdf->Output('', 'S');
    }

    /**
     * @param  Collection<int, Chapter>  $chapters
     */
    private function linkTableOfContents(Mpdf $mpdf, Collection $chapters): void
    {
        // One bookmark per chapter, in the order the chapters were written.
        $targets = [];

        foreach (array_values($mpdf->BMoutlines) as $i => $bookmark) {
            if ($chapter = $chapters->values()->get($i)) {
                $targets[$chapter->pdfAnchor()] = $bookmark;
            }
        }

        // Repeated references to one anchor are keyed "x", "#x", "##x"...
        foreach ($mpdf->internallink as $key => $link) {
            $target = $targets[ltrim($key, '#')] ?? null;

            if ($target && is_int($link)) {
                $mpdf->SetLink($link, $target['y'], $target['p']);
            }
        }
    }

    private function writeCoverPage(Mpdf $mpdf, string $image): void
    {
        $mpdf->AddPage();
        $mpdf->Image($image, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, '', '', true, false);
        $mpdf->AddPageByArray(['resetpagenum' => 1]);
    }

    private function coverImagePath(Book $book): ?string
    {
        if (! $book->cover_path || ! Storage::disk('public')->exists($book->cover_path)) {
            return null;
        }

        return Storage::disk('public')->path($book->cover_path);
    }
}
