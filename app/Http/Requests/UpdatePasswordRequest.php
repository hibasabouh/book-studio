<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::min(8)],
        ];

        // Google-only accounts have no password yet, so they're setting one for
        // the first time — only require the current password when one exists.
        if (! is_null($this->user()->password)) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }
}
