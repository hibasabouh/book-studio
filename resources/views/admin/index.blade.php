@extends('layouts.app')

@section('content')
    <div x-data="adminPage()" x-init="init()" class="mx-auto flex max-w-6xl flex-col gap-6 sm:flex-row" x-cloak>
        @include('partials.admin-sidebar', ['active' => 'dashboard'])

        <div class="min-w-0 flex-1 space-y-8">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('admin.title')"></h1>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('admin.subtitle')"></p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('admin.users_total')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.users_total ?? '—'"></p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('admin.users_verified')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.users_verified ?? '—'"></p>
            </div>
            <a href="/admin/messages" class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 hover:ring-emerald-200">
                <p class="text-sm text-gray-500" x-text="$t('admin.messages_unread')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.messages_unread ?? '—'"></p>
            </a>
        </div>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-base font-semibold text-gray-900" x-text="$t('admin.recent_users')"></h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-start text-gray-500">
                            <th class="py-2 text-start font-medium" x-text="$t('admin.col_name')"></th>
                            <th class="py-2 text-start font-medium" x-text="$t('admin.col_email')"></th>
                            <th class="py-2 text-start font-medium" x-text="$t('admin.col_role')"></th>
                            <th class="py-2 text-start font-medium" x-text="$t('admin.col_joined')"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="u in (stats.recent_users || [])" :key="u.id">
                            <tr class="border-t border-gray-100">
                                <td class="py-2" x-text="u.name"></td>
                                <td class="py-2 text-gray-600" x-text="u.email"></td>
                                <td class="py-2 text-gray-600" x-text="u.role"></td>
                                <td class="py-2 text-gray-600" x-text="u.created_at ? new Date(u.created_at).toLocaleDateString() : ''"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminPage() {
            return {
                stats: {},
                loaded: false,
                init() {
                    this.$watch('$store.auth.ready', () => this.guard());
                    this.$watch('$store.auth.user', () => this.guard());
                    this.guard();
                },
                guard() {
                    if (!window.adminGuard(this)) return;
                    if (!this.loaded) this.load();
                },
                async load() {
                    this.loaded = true;
                    try {
                        this.stats = await window.auth.fetchJson('/api/admin/stats');
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/dashboard';
                    }
                },
            };
        }
    </script>
@endpush
