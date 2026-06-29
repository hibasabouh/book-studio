<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('book'));
    }

    public function rules(): array
    {
        return [];
    }
}
