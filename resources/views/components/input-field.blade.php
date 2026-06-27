@props([
    'name',
    'label',
    'type' => 'text',
    'model',            // Alpine x-model expression, e.g. "form.email"
    'error' => null,    // Alpine expression returning an error string, e.g. "fieldError('email')"
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>

    <input
        id="{{ $name }}"
        type="{{ $type }}"
        x-model="{{ $model }}"
        {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']) }}
    >

    @if ($error)
        <p class="mt-1 text-xs text-red-600" x-show="{{ $error }}" x-text="{{ $error }}"></p>
    @endif
</div>
