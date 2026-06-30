<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin');
    }

    /**
     * Every field is optional (`sometimes`) so this single request serves both the
     * inline role change on the users list and the full edit form on the details
     * page — only the supplied fields are validated and applied.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')->id),
            ],
            'avatar' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::min(8)],
            'role' => ['sometimes', 'required', new Enum(Role::class)],
        ];
    }
}
