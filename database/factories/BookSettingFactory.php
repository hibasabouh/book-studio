<?php

namespace Database\Factories;

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\PdfTemplate;
use App\Enums\TextDirection;
use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\BookSetting>
 */
class BookSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'template' => PdfTemplate::Classic,
            'latin_font' => BookFont::EbGaramond,
            'arabic_font' => BookFont::Amiri,
            'text_direction' => TextDirection::Ltr,
            'language' => BookLanguage::En,
            'font_size' => 12.0,
            'chapter_header_font_size' => 18.0,
            'line_height' => 1.5,
            'author' => fake()->name(),
            'subtitle' => fake()->optional()->sentence(),
            'show_page_numbers' => true,
            'show_table_of_contents' => true,
            'show_chapter_headers' => true,
        ];
    }

    /**
     * RTL Arabic preset (Arabic font, RTL direction, Arabic language).
     */
    public function arabic(): static
    {
        return $this->state(fn () => [
            'arabic_font' => BookFont::Amiri,
            'text_direction' => TextDirection::Rtl,
            'language' => BookLanguage::Ar,
        ]);
    }
}
