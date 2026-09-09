<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookCoverRequest extends FormRequest
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
            // SVG is deliberately absent: it can carry script and would be served
            // from our own origin. The dimension cap bounds GD's memory use.
            'cover' => [
                'required',
                'file',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
                'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000',
            ],
            'crop_x' => ['required', 'numeric', 'between:0,1'],
            'crop_y' => ['required', 'numeric', 'between:0,1'],
            'crop_width' => ['required', 'numeric', 'between:0.01,1'],
            'crop_height' => ['required', 'numeric', 'between:0.01,1'],
        ];
    }

    /**
     * The crop rectangle, as fractions of the uploaded image.
     *
     * @return array{x: float, y: float, width: float, height: float}
     */
    public function crop(): array
    {
        return [
            'x' => (float) $this->validated('crop_x'),
            'y' => (float) $this->validated('crop_y'),
            'width' => (float) $this->validated('crop_width'),
            'height' => (float) $this->validated('crop_height'),
        ];
    }
}
