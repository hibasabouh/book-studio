<div x-data
     x-cloak
     x-show="$store.confirm.open"
     class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/40 p-4"
     @keydown.escape.window="$store.confirm.respond(false)">
    <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl" @click.outside="$store.confirm.respond(false)">
        <h2 class="text-base font-semibold text-gray-900" x-text="$t('common.confirm_title')"></h2>
        <p class="mt-2 text-sm text-gray-600" x-text="$store.confirm.message"></p>
        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="$store.confirm.respond(false)"
                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                    x-text="$t('common.cancel')"></button>
            <button type="button" @click="$store.confirm.respond(true)"
                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                    x-text="$store.confirm.confirmLabel"></button>
        </div>
    </div>
</div>
