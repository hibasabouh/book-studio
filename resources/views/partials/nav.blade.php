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
                    <a href="/books" class="text-sm font-medium text-gray-600 hover:text-gray-900">Library</a>
                </div>
            </div>

            {{-- Guest actions --}}
            <div class="flex items-center gap-3" x-show="$store.auth.ready && !$store.auth.check">
                <a href="/login" class="text-sm font-medium text-gray-600 hover:text-gray-900">Log in</a>
                <a href="/register"
                   class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Get started
                </a>
            </div>

            {{-- Authenticated actions --}}
            <div class="flex items-center gap-3" x-show="$store.auth.ready && $store.auth.check">
                <span class="hidden text-sm text-gray-600 sm:inline" x-text="$store.auth.user?.name"></span>
                <button type="button"
                        @click="$store.auth.logout(); window.location.href = '/login'"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900">
                    Log out
                </button>
            </div>
        </div>
    </div>
</nav>
