<div x-data
     x-cloak
     class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
     @app:toast.window="$store.toasts.add($event.detail.message, $event.detail.type)">

    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="pointer-events-auto flex w-80 items-start gap-3 rounded-lg px-4 py-3 text-sm shadow-lg ring-1"
             :class="{
                 'bg-red-50 text-red-800 ring-red-600/20':   toast.type === 'error',
                 'bg-green-50 text-green-800 ring-green-600/20': toast.type === 'success',
             }"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-x-4"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-4">
            <span class="grow" x-text="toast.message"></span>
            <button @click="$store.toasts.remove(toast.id)"
                    class="mt-0.5 shrink-0 opacity-50 hover:opacity-100">✕</button>
        </div>
    </template>
</div>
