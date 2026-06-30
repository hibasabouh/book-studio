@extends('layouts.app')

@section('content')
    <div x-data="dashboardPage()" x-init="init()" class="mx-auto max-w-4xl space-y-8" x-cloak>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900">
            <span x-text="$t('dashboard.welcome')"></span>
            <span x-text="$store.auth.user?.name"></span>
        </h1>

        <template x-if="$store.auth.user">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                      :class="$store.auth.user.email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                      x-text="$store.auth.user.email_verified_at ? $t('dashboard.email_verified') : $t('dashboard.email_unverified')"></span>
                <a x-show="!$store.auth.user.email_verified_at" href="/email/verify"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('dashboard.verify_now')"></a>
            </div>
        </template>

        {{-- Analytics cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('dashboard.total_books')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.books_total ?? '—'"></p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('dashboard.total_chapters')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.chapters_total ?? '—'"></p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('dashboard.avg_chapters')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.chapters_avg ?? '—'"></p>
            </div>
        </div>

        {{-- Recent books --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-900" x-text="$t('dashboard.recent_books')"></h2>
                <a href="/books" class="text-sm font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('dashboard.view_all')"></a>
            </div>

            <template x-if="loaded && recentBooks.length === 0">
                <p class="mt-4 text-sm text-gray-500" x-text="$t('dashboard.books_empty')"></p>
            </template>

            <ul class="mt-4 divide-y divide-gray-100" x-show="recentBooks.length > 0">
                <template x-for="book in recentBooks" :key="book.id">
                    <li>
                        <a :href="`/books/${book.id}`" class="flex items-center justify-between gap-3 py-3 hover:bg-gray-50">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900" x-text="book.title"></p>
                                <p class="truncate text-xs text-gray-500" x-text="book.description"></p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-400">
                                <span x-text="book.chapters_count ?? 0"></span>
                                <span x-text="$t('books.chapters_count')"></span>
                            </span>
                        </a>
                    </li>
                </template>
            </ul>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function dashboardPage() {
            return {
                stats: {},
                recentBooks: [],
                loaded: false,
                init() {
                    this.$watch('$store.auth.ready', () => this.guard());
                    this.guard();
                },
                guard() {
                    if (!this.$store.auth.ready) return;
                    if (!this.$store.auth.check) { window.location.href = '/login'; return; }
                    if (!this.loaded) this.load();
                },
                async load() {
                    this.loaded = true;
                    try {
                        this.stats = await window.auth.fetchJson('/api/dashboard/stats');
                        this.recentBooks = this.stats.recent_books ?? [];
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/email/verify';
                    }
                },
            };
        }
    </script>
@endpush
