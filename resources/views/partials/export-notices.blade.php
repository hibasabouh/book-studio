{{-- Finished PDF exports, shown on every page until downloaded or dismissed (see resources/js/exports.js). --}}
<div x-data x-cloak
     class="pointer-events-none fixed bottom-4 end-4 z-50 flex flex-col gap-2"
     role="status" aria-live="polite">
    <template x-for="entry in $store.exports.ready" :key="entry.id">
        <div class="pointer-events-auto flex w-80 items-start gap-3 rounded-lg bg-white px-4 py-3 text-sm shadow-lg ring-1 ring-emerald-600/20"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="min-w-0 grow">
                <p class="font-medium text-gray-900" x-text="$t('export.ready')"></p>
                <p class="truncate text-gray-500" x-text="entry.title"></p>
                {{-- Direct navigation: the session cookie authenticates it and
                     Content-Disposition: attachment keeps the page in place. --}}
                <a :href="entry.downloadUrl" @click="$store.exports.dismiss(entry.id)"
                   class="mt-2 inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span x-text="$t('export.download')"></span>
                </a>
            </div>
            <button type="button" @click="$store.exports.dismiss(entry.id)"
                    class="mt-0.5 shrink-0 text-gray-400 hover:text-gray-600"
                    :title="$t('common.close')">✕</button>
        </div>
    </template>
</div>
