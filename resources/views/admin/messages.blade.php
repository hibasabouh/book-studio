@extends('layouts.app')

@php
    use App\Enums\ContactCategory;
@endphp

@section('content')
    <div x-data="adminMessagesPage()" x-init="init()" class="flex w-full flex-col gap-6 sm:flex-row" x-cloak>
        @include('partials.admin-sidebar', ['active' => 'messages'])

        <div class="min-w-0 flex-1 space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('admin.messages_title')"></h1>
                    <p class="mt-1 text-sm text-gray-500" x-text="$t('admin.messages_subtitle')"></p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <select x-model="category" @change="applyFilters()"
                            class="rounded-md border-gray-300 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="" x-text="$t('admin.messages_filter_all_categories')"></option>
                        @foreach (ContactCategory::cases() as $cat)
                            <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                        @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" x-model="unreadOnly" @change="applyFilters()"
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span x-text="$t('admin.messages_filter_unread_only')"></span>
                    </label>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-900/5">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50">
                        <tr class="text-gray-500">
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_from')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_category')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_message')"></th>
                            <th class="px-4 py-3 text-start font-medium" x-text="$t('admin.col_received')"></th>
                            <th class="px-4 py-3 text-end font-medium" x-text="$t('admin.col_actions')"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="loaded && messages.length === 0">
                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400" x-text="$t('admin.no_messages')"></td></tr>
                        </template>

                        <template x-for="msg in messages" :key="msg.id">
                            <tr class="border-t border-gray-100" :class="!msg.read_at ? 'bg-emerald-50/40' : ''">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900" x-text="msg.name"></p>
                                    <p class="text-xs text-gray-500" x-text="msg.email"></p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700"
                                          x-text="$t(`contact.category_${msg.category}`)"></span>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-gray-600">
                                    <p class="line-clamp-2" x-text="msg.message"></p>
                                    <div class="mt-2 flex flex-wrap gap-1.5" x-show="msg.attachments && msg.attachments.length">
                                        <template x-for="att in msg.attachments" :key="att.id">
                                            <a :href="att.url" target="_blank" rel="noopener"
                                               class="inline-flex max-w-[9rem] items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200">
                                                <template x-if="att.is_image">
                                                    <img :src="att.url" alt="" class="h-4 w-4 shrink-0 rounded object-cover">
                                                </template>
                                                <template x-if="!att.is_image">
                                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                                    </svg>
                                                </template>
                                                <span class="truncate" x-text="att.original_name"></span>
                                            </a>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600" x-text="msg.created_at ? new Date(msg.created_at).toLocaleDateString() : ''"></td>
                                <td class="px-4 py-3 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="`/admin/messages/${msg.id}`" :title="$t('admin.view_details')" :aria-label="$t('admin.view_details')"
                                           class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-emerald-600">
                                            <svg class="h-5 w-5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                            </svg>
                                        </a>
                                        <button @click="toggleRead(msg)" :title="msg.read_at ? $t('admin.mark_unread') : $t('admin.mark_read')"
                                                class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-emerald-600">
                                            <svg x-show="!msg.read_at" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <svg x-cloak x-show="msg.read_at" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                        <button @click="destroy(msg)" :title="$t('common.delete')"
                                                class="inline-flex rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div x-show="meta && meta.last_page > 1" class="flex items-center justify-between text-sm">
                <p class="text-gray-500">
                    <span x-text="meta?.from ?? 0"></span>–<span x-text="meta?.to ?? 0"></span>
                    <span x-text="$t('admin.of')"></span>
                    <span x-text="meta?.total ?? 0"></span>
                </p>
                <div class="flex gap-2">
                    <button @click="goTo(page - 1)" :disabled="page <= 1"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50 disabled:opacity-40"
                            x-text="$t('admin.prev')"></button>
                    <button @click="goTo(page + 1)" :disabled="meta && page >= meta.last_page"
                            class="rounded-md px-3 py-1.5 font-medium text-gray-600 ring-1 ring-gray-300 hover:bg-gray-50 disabled:opacity-40"
                            x-text="$t('admin.next')"></button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminMessagesPage() {
            return {
                messages: [],
                meta: null,
                category: '',
                unreadOnly: false,
                page: 1,
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
                    await this.fetchMessages();
                },
                async fetchMessages() {
                    try {
                        const params = new URLSearchParams({ page: this.page });
                        if (this.category) params.set('category', this.category);
                        if (this.unreadOnly) params.set('unread', '1');
                        const data = await window.auth.fetchJson(`/api/admin/messages?${params.toString()}`);
                        this.messages = data.data;
                        this.meta = data.meta;
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/dashboard';
                    }
                },
                applyFilters() {
                    this.page = 1;
                    this.fetchMessages();
                },
                goTo(page) {
                    if (page < 1 || (this.meta && page > this.meta.last_page)) return;
                    this.page = page;
                    this.fetchMessages();
                },
                async toggleRead(msg) {
                    try {
                        const data = await window.auth.fetchJson(`/api/admin/messages/${msg.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify({ read: !msg.read_at }),
                        });
                        const i = this.messages.findIndex(m => m.id === msg.id);
                        if (i > -1) this.messages[i] = data.data;
                    } catch (e) { /* fetchJson already surfaced the error */ }
                },
                async destroy(msg) {
                    if (!await this.$store.confirm.ask(this.$t('admin.confirm_delete_message'))) return;
                    try {
                        const data = await window.auth.fetchJson(`/api/admin/messages/${msg.id}`, { method: 'DELETE' });
                        this.messages = this.messages.filter(m => m.id !== msg.id);
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: data.message, type: 'success' },
                        }));
                    } catch (e) { /* fetchJson already surfaced the error */ }
                },
            };
        }
    </script>
@endpush
