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

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-base font-semibold text-gray-900" x-text="$t('dashboard.books_title')"></h2>
            <p class="mt-2 text-sm text-gray-500" x-text="$t('dashboard.books_empty')"></p>
        </section>

        <a href="/profile" class="inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500"
           x-text="$t('dashboard.open_profile')"></a>
    </div>
@endsection

@push('scripts')
    <script>
        function dashboardPage() {
            return {
                init() {
                    const guard = () => {
                        if (this.$store.auth.ready && !this.$store.auth.check) {
                            window.location.href = '/login';
                        }
                    };
                    this.$watch('$store.auth.ready', guard);
                    guard();
                },
            };
        }
    </script>
@endpush
