<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('chapter'));
    }

    public function rules(): array
    {
        return [];
    }
}
