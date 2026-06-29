<?php

namespace App\Http\Requests;

use App\Enums\BookFont;
use App\Enums\BookLanguage;
use App\Enums\PdfTemplate;
use App\Enums\TextDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('book'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'template'                 => ['sometimes', Rule::enum(PdfTemplate::class)],
            'latin_font'               => ['sometimes', Rule::enum(BookFont::class)],
            'arabic_font'              => ['sometimes', Rule::enum(BookFont::class)],
            'text_direction'           => ['sometimes', Rule::enum(TextDirection::class)],
            'language'                 => ['sometimes', Rule::enum(BookLanguage::class)],
            'font_size'                => ['sometimes', 'numeric', 'min:8', 'max:72'],
            'chapter_header_font_size' => ['sometimes', 'numeric', 'min:8', 'max:72'],
            'line_height'              => ['sometimes', 'numeric', 'min:1', 'max:3'],
            'author'                   => ['sometimes', 'nullable', 'string', 'max:255'],
            'subtitle'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'show_page_numbers'        => ['sometimes', 'boolean'],
            'show_table_of_contents'   => ['sometimes', 'boolean'],
            'show_chapter_headers'     => ['sometimes', 'boolean'],
        ];
    }
}
