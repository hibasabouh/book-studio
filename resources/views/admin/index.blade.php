@extends('layouts.app')

@section('content')
    <div x-data="adminPage()" x-init="init()" class="mx-auto max-w-5xl space-y-8" x-cloak>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('admin.title')"></h1>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('admin.subtitle')"></p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('admin.users_total')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.users_total ?? '—'"></p>
            </div>
            <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                <p class="text-sm text-gray-500" x-text="$t('admin.users_verified')"></p>
                <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.users_verified ?? '—'"></p>
            </div>
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
                    if (!this.$store.auth.ready) return;
                    if (!this.$store.auth.check) { window.location.href = '/login'; return; }
                    const u = this.$store.auth.user;
                    if (!u) return; // wait for the user to load
                    if (u.role !== 'admin') { window.location.href = '/dashboard'; return; }
                    this.$store.mode.set('admin');
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
