<?php

namespace App\Http\Requests;

use App\Models\Chapter;
use Illuminate\Foundation\Http\FormRequest;

class IndexChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', [Chapter::class, $this->route('book')]);
    }

    public function rules(): array
    {
        return [];
    }
}
