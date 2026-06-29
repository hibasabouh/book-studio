<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\BookSetting
 */
class BookSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'template'                 => $this->template,
            'latin_font'               => $this->latin_font,
            'arabic_font'              => $this->arabic_font,
            'text_direction'           => $this->text_direction,
            'language'                 => $this->language,
            'font_size'                => $this->font_size,
            'chapter_header_font_size' => $this->chapter_header_font_size,
            'line_height'              => $this->line_height,
            'author'                   => $this->author,
            'subtitle'                 => $this->subtitle,
            'show_page_numbers'        => $this->show_page_numbers,
            'show_table_of_contents'   => $this->show_table_of_contents,
            'show_chapter_headers'     => $this->show_chapter_headers,
            'updated_at'               => $this->updated_at,
        ];
    }
}
