<?php

namespace App\Models;

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\PdfTemplate;
use App\Enums\TextDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookSetting extends Model
{
    /** @use HasFactory<\Database\Factories\BookSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'template',
        'latin_font',
        'arabic_font',
        'heading_font',
        'text_direction',
        'language',
        'font_size',
        'chapter_header_font_size',
        'line_height',
        'author',
        'subtitle',
        'show_page_numbers',
        'show_table_of_contents',
        'show_chapter_headers',
    ];

    protected function casts(): array
    {
        return [
            'template' => PdfTemplate::class,
            'latin_font' => BookFont::class,
            'arabic_font' => BookFont::class,
            'heading_font' => BookFont::class,
            'text_direction' => TextDirection::class,
            'language' => BookLanguage::class,
            'font_size' => 'float',
            'chapter_header_font_size' => 'float',
            'line_height' => 'float',
            'show_page_numbers' => 'boolean',
            'show_table_of_contents' => 'boolean',
            'show_chapter_headers' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
