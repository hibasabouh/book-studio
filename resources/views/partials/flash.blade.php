{{--
    Sessionless flash messages. There is no server session to read, so messages
    are pushed client-side via a `flash` window event:

        window.dispatchEvent(new CustomEvent('flash', {
            detail: { type: 'success', message: 'Saved!' }
        }));
--}}
<div x-data="{
        items: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.items.push({ id, type: detail.type || 'info', message: detail.message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.items = this.items.filter(i => i.id !== id);
        },
     }"
     @flash.window="push($event.detail)"
     class="pointer-events-none fixed inset-x-0 top-4 z-50 flex flex-col items-center gap-2 px-4"
     x-cloak>
    <template x-for="item in items" :key="item.id">
        <div class="pointer-events-auto w-full max-w-md rounded-md px-4 py-3 text-sm shadow-md ring-1"
             :class="{
                'bg-green-50 text-green-800 ring-green-600/20': item.type === 'success',
                'bg-red-50 text-red-800 ring-red-600/20': item.type === 'error',
                'bg-blue-50 text-blue-800 ring-blue-600/20': item.type === 'info' || !item.type,
             }">
            <div class="flex items-start justify-between gap-3">
                <span x-text="item.message"></span>
                <button type="button" @click="remove(item.id)" class="shrink-0 opacity-60 hover:opacity-100">&times;</button>
            </div>
        </div>
    </template>
</div>
