<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OcrChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('chapter'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,bmp,gif,pdf', 'max:1024'],
            'language' => ['nullable', 'string', 'size:3'],
        ];
    }
}
