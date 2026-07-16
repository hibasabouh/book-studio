@extends('layouts.app')

@section('content')
    <div x-data class="mx-auto max-w-3xl py-16 text-center sm:py-24" x-cloak>
        <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl" x-text="$t('landing.tagline')"></h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-gray-600" x-text="$t('landing.subtitle')"></p>

        <div class="mt-10 flex items-center justify-center gap-4">
            <template x-if="$store.auth.ready && $store.auth.check">
                <a href="/dashboard"
                   class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                   x-text="$t('landing.go_dashboard')"></a>
            </template>

            <template x-if="$store.auth.ready && !$store.auth.check">
                <div class="flex items-center gap-4">
                    <a href="/register"
                       class="rounded-md bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                       x-text="$t('landing.get_started')"></a>
                    <a href="/login"
                       class="rounded-md px-5 py-3 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50"
                       x-text="$t('landing.sign_in')"></a>
                </div>
            </template>
        </div>

        <p class="mt-8 text-sm text-gray-500">
            <span x-text="$t('landing.portfolio_note')"></span>
            <a href="https://hibasabouh.dev" target="_blank" rel="noopener" class="font-medium text-indigo-600 hover:text-indigo-500">Hiba Sabouh</a>
            &middot;
            <a href="/about" class="font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('landing.about_link')"></a>
        </p>
    </div>

    <div class="mx-auto max-w-3xl pb-16 sm:pb-24">
        <div class="rounded-lg bg-indigo-50 p-6 text-center ring-1 ring-indigo-100 sm:p-8" x-data x-cloak>
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('landing.cta_title')"></h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-gray-600" x-text="$t('landing.cta_body')"></p>
            <a href="/contact"
               class="mt-4 inline-flex rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
               x-text="$t('landing.cta_button')"></a>
        </div>
    </div>
@endsection
