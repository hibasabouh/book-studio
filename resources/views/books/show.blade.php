@extends('layouts.app')

@section('content')
    <div x-data="bookEditor({{ $book->id }})" x-init="init()" class="flex h-[calc(100vh-4rem)] overflow-hidden" x-cloak>

        {{-- Sidebar: chapter list --}}
        <aside class="flex w-64 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
            <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3">
                <a href="/books" class="text-sm text-gray-500 hover:text-gray-700">← <span x-text="$t('nav.library')"></span></a>
            </div>

            <div class="border-b border-gray-200 px-4 py-3">
                <button @click="addChapter()"
                        class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                        x-text="$t('chapters.add')"></button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <template x-if="ready && chapters.length === 0">
                    <p class="px-4 py-6 text-center text-sm text-gray-400" x-text="$t('chapters.empty')"></p>
                </template>

                <ul class="divide-y divide-gray-100">
                    <template x-for="(chapter, index) in chapters" :key="chapter.id">
                        <li @click="select(chapter)"
                            :class="selected?.id === chapter.id ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                            class="flex cursor-pointer items-center gap-1 px-2 py-2">

                            {{-- Reorder arrows --}}
                            <div class="flex flex-col" @click.stop>
                                <button @click="moveUp(index)"
                                        :disabled="index === 0"
                                        class="rounded p-0.5 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                        title="Move up">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                </button>
                                <button @click="moveDown(index)"
                                        :disabled="index === chapters.length - 1"
                                        class="rounded p-0.5 text-gray-400 hover:text-gray-600 disabled:opacity-30"
                                        title="Move down">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                            </div>

                            {{-- Title --}}
                            <span class="flex-1 truncate text-sm text-gray-800" x-text="chapter.title"></span>

                            {{-- Delete --}}
                            <button @click.stop="destroy(chapter)"
                                    class="flex-shrink-0 rounded p-1 text-gray-400 hover:text-red-500">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </aside>

        {{-- Editor panel --}}
        <main class="flex flex-1 flex-col overflow-hidden bg-gray-50">

            {{-- No selection placeholder --}}
            <template x-if="!selected">
                <div class="flex flex-1 items-center justify-center">
                    <p class="text-sm text-gray-400" x-text="$t('chapters.no_selection')"></p>
                </div>
            </template>

            {{-- Chapter editor --}}
            <template x-if="selected">
                <div class="flex flex-1 flex-col overflow-hidden p-6">
                    <div class="mb-4">
                        <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 mb-1"
                               x-text="$t('chapters.field_title')"></label>
                        <input type="text"
                               x-model="form.title"
                               class="block w-full rounded-md border-gray-300 text-lg font-semibold shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p class="mt-1 text-xs text-red-600" x-show="fieldError('title')" x-text="fieldError('title')"></p>
                    </div>

                    <div class="flex flex-1 flex-col overflow-hidden">
                        <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 mb-1"
                               x-text="$t('chapters.field_content')"></label>
                        <textarea x-model="form.content"
                                  class="flex-1 block w-full resize-none rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  placeholder=""></textarea>
                        <p class="mt-1 text-xs text-red-600" x-show="fieldError('content')" x-text="fieldError('content')"></p>
                    </div>

                    <div class="mt-4 flex items-center justify-end">
                        <button @click="save()" :disabled="saving"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                x-text="saving ? $t('chapters.saving') : $t('chapters.save')"></button>
                    </div>
                </div>
            </template>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        function bookEditor(bookId) {
            return {
                bookId,
                chapters: [],
                ready: false,
                loaded: false,
                selected: null,
                form: { title: '', content: '' },
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
                        const data = await window.auth.fetchJson(`/api/books/${this.bookId}/chapters`);
                        this.chapters = data.data;
                        this.ready = true;
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/email/verify';
                    }
                },

                select(chapter) {
                    this.selected = chapter;
                    this.form = { title: chapter.title, content: chapter.content ?? '' };
                    this.errors = {};
                },

                async addChapter() {
                    try {
                        const data = await window.auth.fetchJson(`/api/books/${this.bookId}/chapters`, {
                            method: 'POST',
                            body: JSON.stringify({ title: 'New chapter', content: '' }),
                        });
                        this.chapters.push(data.data);
                        this.select(data.data);
                    } catch (e) { console.error(e); }
                },

                async save() {
                    if (!this.selected) return;
                    this.saving = true;
                    this.errors = {};
                    try {
                        const data = await window.auth.fetchJson(`/api/chapters/${this.selected.id}`, {
                            method: 'PATCH',
                            body: JSON.stringify(this.form),
                        });
                        const i = this.chapters.findIndex(c => c.id === this.selected.id);
                        if (i > -1) this.chapters[i] = data.data;
                        this.selected = data.data;
                    } catch (e) {
                        if (e.status === 422) this.errors = e.data?.errors || {};
                    } finally {
                        this.saving = false;
                    }
                },

                async destroy(chapter) {
                    if (!confirm(this.$store.i18n.t('chapters.confirm_delete'))) return;
                    try {
                        await window.auth.fetchJson(`/api/chapters/${chapter.id}`, { method: 'DELETE' });
                        this.chapters = this.chapters.filter(c => c.id !== chapter.id);
                        if (this.selected?.id === chapter.id) {
                            this.selected = null;
                            this.form = { title: '', content: '' };
                        }
                    } catch (e) { console.error(e); }
                },

                async moveUp(index) {
                    if (index === 0) return;
                    await this.swap(index, index - 1);
                },

                async moveDown(index) {
                    if (index === this.chapters.length - 1) return;
                    await this.swap(index, index + 1);
                },

                async swap(i, j) {
                    // Optimistic update
                    const tmp = this.chapters[i];
                    this.chapters[i] = this.chapters[j];
                    this.chapters[j] = tmp;
                    this.chapters = [...this.chapters];

                    try {
                        await window.auth.fetchJson(`/api/books/${this.bookId}/chapters/reorder`, {
                            method: 'POST',
                            body: JSON.stringify({ ids: this.chapters.map(c => c.id) }),
                        });
                    } catch (e) {
                        // Roll back on failure
                        const tmp2 = this.chapters[i];
                        this.chapters[i] = this.chapters[j];
                        this.chapters[j] = tmp2;
                        this.chapters = [...this.chapters];
                    }
                },

                fieldError(f) { return this.errors[f]?.[0] || ''; },
            };
        }
    </script>
@endpush
