<?php

namespace App\Services;

use App\Models\Book;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class BookExportService
{
    /**
     * @throws MpdfException
     */
    public function generatePdf(Book $book): string
    {
        $setting = $book->setting;
        $isRtl = $setting->text_direction->isRtl();

        $bodyFont = $isRtl
            ? $setting->arabic_font->mpdfFamily()
            : $setting->latin_font->mpdfFamily();

        $headingFont = $setting->heading_font->mpdfFamily();

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
        ]);

        if ($isRtl) {
            $mpdf->SetDirectionality('rtl');
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
            'bodyFont' => $bodyFont,
            'headingFont' => $headingFont,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
