@extends('layouts.app')

@section('content')
    <div x-data="library()" x-init="init()" class="mx-auto max-w-5xl space-y-6" x-cloak>
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('books.title')"></h1>
            <button @click="openCreate()"
                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    x-text="$t('books.new')"></button>
        </div>

        <template x-if="ready && books.length === 0">
            <p class="rounded-lg bg-white p-8 text-center text-sm text-gray-500 ring-1 ring-gray-900/5" x-text="$t('books.empty')"></p>
        </template>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <template x-for="book in books" :key="book.id">
                <div class="flex flex-col rounded-lg bg-white p-5 shadow-sm ring-1 ring-gray-900/5">
                    <h3 class="font-semibold text-gray-900" x-text="book.title"></h3>
                    <p class="mt-1 line-clamp-2 grow text-sm text-gray-500" x-text="book.description"></p>
                    <p class="mt-3 text-xs text-gray-400">
                        <span x-text="book.chapters_count ?? 0"></span>
                        <span x-text="$t('books.chapters_count')"></span>
                    </p>
                    <div class="mt-4 flex items-center gap-3 text-sm">
                        <a :href="`/books/${book.id}`" class="font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('books.open')"></a>
                        <button @click="openEdit(book)" class="text-gray-600 hover:text-gray-900" x-text="$t('books.edit')"></button>
                        <button @click="destroy(book)" class="text-red-600 hover:text-red-700" x-text="$t('books.delete')"></button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Create/edit modal --}}
        <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4" @keydown.escape.window="modalOpen = false" x-cloak>
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.outside="modalOpen = false">
                <h2 class="text-base font-semibold text-gray-900" x-text="editing ? $t('books.edit_title') : $t('books.create_title')"></h2>
                <form @submit.prevent="save" class="mt-4 space-y-4">
                    <x-input-field name="book_title" tkey="books.field_title" model="form.title" error="fieldError('title')" required />
                    <div>
                        <label for="book_description" class="block text-sm font-medium text-gray-700" x-text="$t('books.field_description')"></label>
                        <textarea id="book_description" x-model="form.description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <p class="mt-1 text-xs text-red-600" x-show="fieldError('description')" x-text="fieldError('description')"></p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="modalOpen = false" class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900" x-text="$t('books.cancel')"></button>
                        <button type="submit" :disabled="saving"
                                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                x-text="saving ? $t('books.saving') : $t('books.save')"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function library() {
            return {
                books: [],
                ready: false,
                loaded: false,
                modalOpen: false,
                editing: null,
                form: { title: '', description: '' },
                errors: {},
                saving: false,

                init() {
                    this.$watch('$store.auth.ready', () => this.guard());
                    this.guard();
                },
                guard() {
                    if (!this.$store.auth.ready) return;
                    if (!this.$store.auth.check) { window.location.href = '/login'; return; }
                    if (!this.loaded) this.load();
                },
                async load() {
                    this.loaded = true;
                    try {
                        const data = await window.auth.fetchJson('/api/books');
                        this.books = data.data;
                        this.ready = true;
                    } catch (e) {
                        // Unverified users are blocked from book endpoints.
                        if (e.status === 403) window.location.href = '/email/verify';
                    }
                },

                fieldError(f) { return this.errors[f]?.[0] || ''; },

                openCreate() {
                    this.editing = null;
                    this.form = { title: '', description: '' };
                    this.errors = {};
                    this.modalOpen = true;
                },
                openEdit(book) {
                    this.editing = book;
                    this.form = { title: book.title, description: book.description || '' };
                    this.errors = {};
                    this.modalOpen = true;
                },

                async save() {
                    this.saving = true;
                    this.errors = {};
                    try {
                        if (this.editing) {
                            const data = await window.auth.fetchJson(`/api/books/${this.editing.id}`, {
                                method: 'PATCH',
                                body: JSON.stringify(this.form),
                            });
                            const i = this.books.findIndex(b => b.id === this.editing.id);
                            if (i > -1) this.books[i] = data.data;
                        } else {
                            const data = await window.auth.fetchJson('/api/books', {
                                method: 'POST',
                                body: JSON.stringify(this.form),
                            });
                            this.books.unshift(data.data);
                        }
                        this.modalOpen = false;
                    } catch (e) {
                        if (e.status === 422) this.errors = e.data?.errors || {};
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy(book) {
                    if (!confirm(this.$store.i18n.t('books.confirm_delete'))) return;
                    try {
                        await window.auth.fetchJson(`/api/books/${book.id}`, { method: 'DELETE' });
                        this.books = this.books.filter(b => b.id !== book.id);
                    } catch (e) { console.error(e); }
                },
            };
        }
    </script>
@endpush
