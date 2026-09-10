@extends('layouts.app')

@php
    $portfolioUrl = 'https://hibasabouh.vercel.app';
    $githubUrl = 'https://github.com/hibasabouh';
    $repoUrl = 'https://github.com/hibasabouh/book-studio';
    $linkedinUrl = 'https://www.linkedin.com/in/hiba-sabouh-13139b28a';
    $email = 'hiba.sabouh.98@gmail.com';

    $features = ['authoring', 'typography', 'arabic', 'export', 'ocr', 'api'];
    $notes = ['auth', 'settings', 'authz', 'frontend'];
    $roadmap = ['content', 'grammar', 'export', 'sharing'];

    // A card without a `value` renders its value from `about.stack_<key>_value` instead.
    $stack = [
        ['key' => 'backend', 'value' => 'Laravel 12, PHP 8.2+'],
        ['key' => 'frontend', 'value' => 'Blade, Alpine.js 3, Tailwind CSS v4'],
        ['key' => 'database', 'value' => 'MySQL'],
        ['key' => 'auth'],
        ['key' => 'pdf'],
        ['key' => 'integrations'],
        ['key' => 'i18n'],
        ['key' => 'quality'],
    ];

    $linkClass = 'rounded-md px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 hover:text-indigo-600';
    $cardClass = 'rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8';
@endphp

@section('content')
    <div x-data class="mx-auto max-w-4xl space-y-12 py-8" x-cloak>
        {{-- Hero --}}
        <section class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">Book Studio</h1>
            <p class="mt-2 text-lg font-medium text-indigo-600" x-text="$t('about.hero_tagline')"></p>
            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-gray-600" x-text="$t('about.hero_body')"></p>
        </section>

        {{-- Why --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.why_title')"></h2>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.why_body')"></p>
        </section>

        {{-- Features --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.features_title')"></h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($features as $feature)
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <p class="text-sm font-semibold text-gray-900" x-text="$t('about.feature_{{ $feature }}_title')"></p>
                        <p class="mt-1 text-sm leading-relaxed text-gray-600" x-text="$t('about.feature_{{ $feature }}_body')"></p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Stack --}}
        <section>
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.stack_title')"></h2>
            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @foreach ($stack as $item)
                    @php($key = $item['key'])
                    <div class="rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                        <p class="text-sm font-semibold text-indigo-600" x-text="$t('about.stack_{{ $key }}')"></p>
                        @isset($item['value'])
                            <p class="mt-1 text-sm text-gray-600">{{ $item['value'] }}</p>
                        @else
                            <p class="mt-1 text-sm text-gray-600" x-text="$t('about.stack_{{ $key }}_value')"></p>
                        @endisset
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Engineering notes --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.notes_title')"></h2>
            <ul class="mt-4 space-y-3">
                @foreach ($notes as $note)
                    <li class="flex gap-3 text-sm leading-relaxed text-gray-600">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>
                        <span x-text="$t('about.note_{{ $note }}')"></span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Author --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.author_title')"></h2>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.author_body_intro')"></p>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.author_body_project')"></p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ $portfolioUrl }}" target="_blank" rel="noopener" class="{{ $linkClass }}" x-text="$t('about.link_portfolio')"></a>
                <a href="{{ $githubUrl }}" target="_blank" rel="noopener" class="{{ $linkClass }}">GitHub</a>
                <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener" class="{{ $linkClass }}">LinkedIn</a>
                <a href="mailto:{{ $email }}" class="{{ $linkClass }}" x-text="$t('field.email')"></a>
            </div>
        </section>

        {{-- Roadmap --}}
        <section class="{{ $cardClass }}">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.roadmap_title')"></h2>
            <p class="mt-3 leading-relaxed text-gray-600" x-text="$t('about.roadmap_body')"></p>
            <ul class="mt-4 space-y-3">
                @foreach ($roadmap as $item)
                    <li class="flex gap-3 text-sm leading-relaxed text-gray-600">
                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-300"></span>
                        <span x-text="$t('about.roadmap_{{ $item }}')"></span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- CTA --}}
        <section class="rounded-lg bg-indigo-50 p-6 text-center ring-1 ring-indigo-100 sm:p-8">
            <h2 class="text-lg font-semibold text-gray-900" x-text="$t('about.cta_title')"></h2>
            <p class="mt-1 text-sm text-gray-600" x-text="$t('about.cta_body')"></p>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
                <a href="/contact"
                   class="inline-flex rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                   x-text="$t('about.cta_button')"></a>
                <a href="{{ $repoUrl }}" target="_blank" rel="noopener"
                   class="inline-flex rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:text-indigo-600"
                   x-text="$t('about.cta_secondary')"></a>
            </div>
        </section>

        {{-- Credit --}}
        <section class="text-center text-sm text-gray-500">
            <p>
                <span x-text="$t('about.credit_text')"></span>
                <a href="{{ $portfolioUrl }}" target="_blank" rel="noopener" class="ms-1 font-medium text-gray-600 hover:text-indigo-600">Hiba Sabouh</a>
            </p>
            <p class="mt-2">
                <a href="{{ $repoUrl }}" target="_blank" rel="noopener" class="text-gray-600 hover:text-indigo-600" x-text="$t('about.link_source')"></a>
                <span class="mx-2">·</span>
                <a href="{{ $repoUrl }}/blob/main/LICENSE" target="_blank" rel="noopener" class="text-gray-600 hover:text-indigo-600" x-text="$t('about.credit_license')"></a>
            </p>
        </section>
    </div>
@endsection
