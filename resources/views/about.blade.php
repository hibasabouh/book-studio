@extends('layouts.app')

@section('content')
    <div x-data class="mx-auto max-w-4xl space-y-12 py-8" x-cloak>
        {{-- Hero --}}
        <section class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Book Studio</h1>
            <p class="mt-2 text-lg font-medium text-indigo-600" x-text="$t('about.hero_tagline')"></p>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-gray-600" x-text="$t('about.hero_body')"></p>
        </section>

        {{-- Project --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.project_title')"></h2>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.project_body')"></p>
        </section>

        {{-- Stack --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.stack_title')"></h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_backend')"></p>
                    <p class="mt-1 text-sm text-gray-600">Laravel 12, PHP</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_frontend')"></p>
                    <p class="mt-1 text-sm text-gray-600">Blade, Alpine.js, Tailwind CSS</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_auth')"></p>
                    <p class="mt-1 text-sm text-gray-600">Sanctum, Google sign-in, OTP email verification</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_database')"></p>
                    <p class="mt-1 text-sm text-gray-600">MySQL</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_integrations')"></p>
                    <p class="mt-1 text-sm text-gray-600">OCR.space for image-to-text import</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_i18n')"></p>
                    <p class="mt-1 text-sm text-gray-600">English, Arabic, French — with full RTL support</p>
                </div>
            </div>
        </section>

        {{-- Roadmap --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.roadmap_title')"></h2>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.roadmap_body')"></p>
        </section>

        {{-- CTA --}}
        <section class="rounded-lg bg-indigo-50 p-6 text-center ring-1 ring-indigo-100 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.cta_title')"></h2>
            <p class="mt-1 text-sm text-gray-600" x-text="$t('about.cta_body')"></p>
            <a href="/contact"
               class="mt-4 inline-flex rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
               x-text="$t('about.cta_button')"></a>
        </section>

        {{-- Credit --}}
        <section class="text-center text-sm text-gray-500">
            <span x-text="$t('about.credit_text')"></span>
            <a href="https://github.com/hibasabouh" target="_blank" rel="noopener" class="ms-1 font-medium text-gray-600 hover:text-indigo-600">Hiba Sabouh</a>
            <span class="mx-2">·</span>
            <a href="https://github.com/hibasabouh" target="_blank" rel="noopener" class="text-gray-600 hover:text-indigo-600">GitHub</a>
            <span class="mx-2">·</span>
            <a href="https://linkedin.com/in/hiba-sabouh-13139b28a" target="_blank" rel="noopener" class="text-gray-600 hover:text-indigo-600">LinkedIn</a>
            <span class="mx-2">·</span>
            <a href="mailto:hiba.sabouh.98@gmail.com" class="text-gray-600 hover:text-indigo-600" x-text="$t('field.email')"></a>
        </section>
    </div>
@endsection
