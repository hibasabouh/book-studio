@props([
    'name',
    'label' => null,    // static fallback label (used as SSR text)
    'tkey' => null,      // i18n key; when set, the label is translated reactively via $t
    'type' => 'text',
    'model',            // Alpine x-model expression, e.g. "form.email"
    'error' => null,    // Alpine expression returning an error string, e.g. "fieldError('email')"
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700"
           @if ($tkey) x-text="$t('{{ $tkey }}')" @endif>{{ $label }}</label>

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
