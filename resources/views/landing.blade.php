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
    </div>
@endsection
