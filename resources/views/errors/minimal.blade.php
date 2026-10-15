<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), config('app.rtl_locales', [])) ? 'rtl' : 'ltr' }}"
      class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title') · {{ config('app.name', 'Book Studio') }}</title>

        {{-- Guarded so a missing build can't turn the error page itself into a crash. --}}
        @if (file_exists(public_path('hot')) || file_exists(public_path('build/manifest.json')))
            @vite('resources/css/app.css')
        @endif
    </head>
    <body class="h-full bg-stone-50 text-gray-900 antialiased">
        <main class="flex min-h-full flex-col justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <a href="/" class="block text-center text-2xl font-semibold tracking-tight text-emerald-600">
                    {{ config('app.name', 'Book Studio') }}
                </a>
            </div>

            <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
                <div class="bg-white px-6 py-10 text-center shadow-sm ring-1 ring-gray-900/5 sm:rounded-lg sm:px-10">
                    <h1 class="text-5xl font-semibold tracking-tight text-emerald-600">@yield('code')</h1>
                    <p class="mt-4 text-sm text-gray-600">@yield('message')</p>

                    <a href="/"
                       class="mt-8 inline-flex justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                        {{ __('errors.back_home') }}
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>
