@extends('layouts.app')

@section('content')
    <div x-data="adminMessageDetailsPage({{ $messageId }})" x-init="init()" class="mx-auto flex max-w-4xl flex-col gap-6 sm:flex-row" x-cloak>
        @include('partials.admin-sidebar', ['active' => 'messages'])

        <div class="min-w-0 flex-1 space-y-6">
            <a href="/admin/messages" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span x-text="$t('admin.back_to_messages')"></span>
            </a>

            <template x-if="message">
                <div class="space-y-6">
                    {{-- Header --}}
                    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h1 class="truncate text-2xl font-semibold tracking-tight text-gray-900" x-text="message.name"></h1>
                                <a :href="`mailto:${message.email}`" class="truncate text-sm text-indigo-600 hover:text-indigo-500" x-text="message.email"></a>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700"
                                          x-text="$t(`contact.category_${message.category}`)"></span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="message.read_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                          x-text="message.read_at ? $t('admin.message_status_read') : $t('admin.message_status_unread')"></span>
                                    <span class="text-xs text-gray-400"
                                          x-text="`${$t('admin.col_received')}: ${message.created_at ? new Date(message.created_at).toLocaleString() : ''}`"></span>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                <button @click="toggleRead()" :title="message.read_at ? $t('admin.mark_unread') : $t('admin.mark_read')" :aria-label="message.read_at ? $t('admin.mark_unread') : $t('admin.mark_read')"
                                        class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600">
                                    <svg x-show="!message.read_at" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg x-cloak x-show="message.read_at" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                                <button @click="destroy()" :title="$t('common.delete')" :aria-label="$t('common.delete')"
                                        class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Message body --}}
                    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <h2 class="text-base font-semibold text-gray-900" x-text="$t('admin.col_message')"></h2>
                        <p class="mt-3 whitespace-pre-wrap leading-relaxed text-gray-700" x-text="message.message"></p>
                    </section>

                    {{-- Attachments --}}
                    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <h2 class="text-base font-semibold text-gray-900" x-text="$t('admin.message_attachments')"></h2>

                        <p x-show="!message.attachments || message.attachments.length === 0" class="mt-3 text-sm text-gray-400"
                           x-text="$t('admin.message_no_attachments')"></p>

                        <div x-show="message.attachments && message.attachments.length" class="mt-4 flex flex-wrap gap-2">
                            <template x-for="att in message.attachments" :key="att.id">
                                <a :href="att.url" target="_blank" rel="noopener"
                                   class="inline-flex max-w-[14rem] items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">
                                    <template x-if="att.is_image">
                                        <img :src="att.url" alt="" class="h-6 w-6 shrink-0 rounded object-cover">
                                    </template>
                                    <template x-if="!att.is_image">
                                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                    </template>
                                    <span class="truncate" x-text="att.original_name"></span>
                                </a>
                            </template>
                        </div>
                    </section>
                </div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminMessageDetailsPage(messageId) {
            return {
                messageId,
                message: null,
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
                        const data = await window.auth.fetchJson(`/api/admin/messages/${this.messageId}`);
                        this.message = data.data;
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/dashboard';
                        else if (e.status === 404) window.location.href = '/admin/messages';
                    }
                },
                async toggleRead() {
                    try {
                        const data = await window.auth.fetchJson(`/api/admin/messages/${this.messageId}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ read: !this.message.read_at }),
                        });
                        this.message = data.data;
                    } catch (e) { /* fetchJson already surfaced the error */ }
                },
                async destroy() {
                    if (!await this.$store.confirm.ask(this.$t('admin.confirm_delete_message'))) return;
                    try {
                        await window.auth.fetchJson(`/api/admin/messages/${this.messageId}`, { method: 'DELETE' });
                        window.location.href = '/admin/messages';
                    } catch (e) { /* fetchJson already surfaced the error */ }
                },
            };
        }
    </script>
@endpush
