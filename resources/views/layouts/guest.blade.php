<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'Book Studio') }}</title>

    @include('partials.locale-head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">
    <div class="flex min-h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto flex w-full max-w-md justify-end px-2">
            <x-language-switcher />
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <a href="/" class="block text-center text-2xl font-semibold tracking-tight text-indigo-600">
                {{ config('app.name', 'Book Studio') }}
            </a>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white px-6 py-8 shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg sm:px-10">
                @include('partials.flash')

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
