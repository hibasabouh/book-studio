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
<body class="h-full bg-stone-50 text-gray-900 antialiased">
    <div class="min-h-full">
        @include('partials.nav')

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @include('partials.flash')

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>

    @include('partials.toasts')
    @include('partials.export-notices')
    @include('partials.confirm')
    @stack('scripts')
</body>
</html>
