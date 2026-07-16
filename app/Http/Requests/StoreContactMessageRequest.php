<?php

namespace App\Http\Requests;

use App\Enums\ContactCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Anyone may send a contact message.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'category' => ['required', new Enum(ContactCategory::class)],
            'message' => ['required', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf,txt,doc,docx', 'max:5120'],
        ];
    }
}
