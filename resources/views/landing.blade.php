@extends('layouts.app')

@section('content')
    {{-- Hero --}}
    <div x-data class="mx-auto grid max-w-6xl grid-cols-1 items-center gap-12 py-8 sm:py-12 lg:grid-cols-2 lg:gap-8" x-cloak>
        <div>
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl" x-text="$t('landing.tagline')"></h1>
            <p class="mt-6 max-w-xl text-lg text-gray-600" x-text="$t('landing.subtitle')"></p>

            <div class="mt-10 flex flex-wrap items-center gap-4">
                <template x-if="$store.auth.ready && $store.auth.check">
                    <a href="/dashboard"
                       class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                       x-text="$t('landing.go_dashboard')"></a>
                </template>

                <template x-if="$store.auth.ready && !$store.auth.check">
                    <div class="flex flex-wrap items-center gap-4">
                        <a href="/register"
                           class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                           x-text="$t('landing.get_started')"></a>
                        <a href="/login"
                           class="text-sm font-semibold text-gray-700 hover:text-gray-900"
                           x-text="$t('landing.sign_in')"></a>
                    </div>
                </template>
            </div>

            <p class="mt-10 text-sm text-gray-500">
                <span x-text="$t('landing.portfolio_note')"></span>
                <a href="https://hibasabouh.dev" target="_blank" rel="noopener" class="font-medium text-emerald-600 hover:text-emerald-500">Hiba Sabouh</a>
                &middot;
                <a href="/about" class="font-medium text-emerald-600 hover:text-emerald-500" x-text="$t('landing.about_link')"></a>
            </p>
        </div>

        {{-- Decorative composition: stacked "books" with feature badges, echoing the product without a stock photo. --}}
        <div class="relative hidden h-96 items-center justify-center sm:flex" aria-hidden="true">
            <div class="absolute h-72 w-72 rounded-full bg-emerald-100 opacity-70 blur-3xl"></div>

            {{-- Stacked book spines --}}
            <div class="absolute h-56 w-40 -rotate-6 rounded-lg bg-amber-300 shadow-lg"></div>
            <div class="absolute h-56 w-40 rotate-3 translate-x-6 rounded-lg bg-stone-300 shadow-lg"></div>

            {{-- Foreground "open page" card --}}
            <div class="relative -rotate-2 rounded-2xl bg-white p-4 shadow-xl ring-1 ring-gray-900/5">
                <div class="h-2 w-16 rounded-full bg-emerald-600"></div>
                <div class="mt-4 h-2.5 w-40 rounded-full bg-gray-800"></div>
                <div class="mt-3 space-y-2">
                    <div class="h-2 w-44 rounded-full bg-gray-200"></div>
                    <div class="h-2 w-40 rounded-full bg-gray-200"></div>
                    <div class="h-2 w-32 rounded-full bg-gray-200"></div>
                </div>
                <div class="mt-4 h-2.5 w-28 rounded-full bg-gray-800"></div>
                <div class="mt-3 space-y-2">
                    <div class="h-2 w-44 rounded-full bg-gray-200"></div>
                    <div class="h-2 w-36 rounded-full bg-gray-200"></div>
                </div>
            </div>

            {{-- Floating feature badges --}}
            <span :title="$t('landing.badge_typesetting')"
                  class="absolute left-0 top-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 shadow-md ring-1 ring-emerald-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.6 9h16.8M3.6 15h16.8M12 3a13.5 13.5 0 010 18 13.5 13.5 0 010-18z"/>
                </svg>
                <span class="sr-only" x-text="$t('landing.badge_typesetting')"></span>
            </span>

            <span :title="$t('landing.badge_export')"
                  class="absolute right-2 top-16 flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-700 shadow-md ring-1 ring-violet-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2"/>
                </svg>
                <span class="sr-only" x-text="$t('landing.badge_export')"></span>
            </span>

            <span :title="$t('landing.badge_ocr')"
                  class="absolute bottom-6 left-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-md ring-1 ring-amber-200">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="sr-only" x-text="$t('landing.badge_ocr')"></span>
            </span>
        </div>
    </div>

    {{-- Feature strip --}}
    <div x-data class="mx-auto max-w-6xl border-t border-gray-200 py-10" x-cloak>
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-3 sm:divide-x sm:divide-gray-200">
            <a href="/about" class="group block sm:pe-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600" x-text="$t('landing.tag_authoring')"></p>
                <p class="mt-2 text-lg font-semibold text-gray-900 group-hover:text-emerald-700" x-text="$t('about.feature_authoring_title')"></p>
            </a>
            <a href="/about" class="group block sm:px-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600" x-text="$t('landing.tag_layout')"></p>
                <p class="mt-2 text-lg font-semibold text-gray-900 group-hover:text-emerald-700" x-text="$t('about.feature_typography_title')"></p>
            </a>
            <a href="/about" class="group block sm:ps-8">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600" x-text="$t('landing.tag_export')"></p>
                <p class="mt-2 text-lg font-semibold text-gray-900 group-hover:text-emerald-700" x-text="$t('about.feature_export_title')"></p>
            </a>
        </div>
    </div>

    <div class="mx-auto max-w-6xl pb-16 sm:pb-24">
        <div class="rounded-2xl bg-emerald-50 p-6 text-center ring-1 ring-emerald-100 sm:p-8" x-data x-cloak>
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('landing.cta_title')"></h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-gray-600" x-text="$t('landing.cta_body')"></p>
            <a href="/contact"
               class="mt-4 inline-flex rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
               x-text="$t('landing.cta_button')"></a>
        </div>
    </div>
@endsection
