{{--
    Sessionless nav. Auth state is read client-side from the Alpine `auth` store
    (backed by the localStorage token), so the same shell renders for guests and
    authenticated users without the server knowing who is logged in.
--}}
<nav x-data class="border-b border-gray-200 bg-white" x-cloak>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="/" class="text-lg font-semibold tracking-tight text-indigo-600">
                    {{ config('app.name', 'Book Studio') }}
                </a>

                <div class="hidden items-center gap-4 sm:flex" x-show="$store.auth.check">
                    <a href="/dashboard" class="text-sm font-medium text-gray-600 hover:text-gray-900" x-text="$t('nav.dashboard')"></a>
                    <a href="/books" class="text-sm font-medium text-gray-600 hover:text-gray-900" x-text="$t('nav.library')"></a>
                    <a href="/admin" x-show="$store.auth.user?.role === 'admin'"
                       class="text-sm font-medium text-gray-600 hover:text-gray-900" x-text="$t('nav.admin')"></a>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-language-switcher />

                {{-- Guest actions --}}
                <template x-if="$store.auth.ready && !$store.auth.check">
                    <div class="flex items-center gap-3">
                        <a href="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900" x-text="$t('nav.login')"></a>
                        <a href="/register"
                           class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                           x-text="$t('nav.get_started')"></a>
                    </div>
                </template>

                {{-- Authenticated actions --}}
                <template x-if="$store.auth.ready && $store.auth.check">
                    <div class="flex items-center gap-3">
                        <a href="/profile" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                            <template x-if="$store.auth.user?.avatar">
                                <img :src="$store.auth.user.avatar" alt="" class="h-7 w-7 rounded-full object-cover ring-1 ring-gray-200">
                            </template>
                            <template x-if="!$store.auth.user?.avatar">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-500"
                                      x-text="($store.auth.user?.name || '?').charAt(0).toUpperCase()"></span>
                            </template>
                            <span x-text="$store.auth.user?.name || $t('nav.profile')"></span>
                        </a>
                        <button type="button"
                                @click="$store.auth.logout(); window.location.href = '/login'"
                                class="text-sm font-medium text-gray-600 hover:text-gray-900"
                                x-text="$t('nav.logout')"></button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</nav>
