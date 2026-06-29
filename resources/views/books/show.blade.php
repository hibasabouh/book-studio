@extends('layouts.app')

@section('content')
    <div x-data="bookEditor({{ $book->id }})" x-init="init()" class="flex h-[calc(100vh-4rem)] overflow-hidden" x-cloak>

        {{-- Sidebar: chapter list --}}
        <aside class="flex w-64 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <a href="/books" class="text-sm text-gray-500 hover:text-gray-700">← <span x-text="$t('nav.library')"></span></a>
                <button @click="settingsOpen = !settingsOpen"
                        :class="settingsOpen ? 'text-indigo-600' : 'text-gray-400 hover:text-gray-600'"
                        class="rounded p-1"
                        :title="$t('settings.tab')">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
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
                        <li @click="select(chapter); settingsOpen = false"
                            :class="!settingsOpen && selected?.id === chapter.id ? 'bg-indigo-50' : 'hover:bg-gray-50'"
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

        {{-- Main panel --}}
        <main class="flex flex-1 flex-col overflow-hidden bg-gray-50">

            {{-- Settings panel --}}
            <template x-if="settingsOpen">
                <div class="flex flex-1 flex-col overflow-y-auto p-6">
                    <div class="mx-auto w-full max-w-2xl space-y-8">
                        <h2 class="text-lg font-semibold text-gray-900" x-text="$t('settings.title')"></h2>

                        {{-- Metadata --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_metadata')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_author')"></label>
                                    <input type="text" x-model="settingsForm.author"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_subtitle')"></label>
                                    <input type="text" x-model="settingsForm.subtitle"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                </div>
                            </div>
                        </section>

                        {{-- Layout --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_layout')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_template')"></label>
                                        <select x-model="settingsForm.template"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <template x-for="opt in templates" :key="opt.value">
                                                <option :value="opt.value" x-text="opt.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_text_direction')"></label>
                                        <select x-model="settingsForm.text_direction"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="ltr" x-text="$t('settings.direction_ltr')"></option>
                                            <option value="rtl" x-text="$t('settings.direction_rtl')"></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_page_numbers" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span x-text="$t('settings.field_show_page_numbers')"></span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_table_of_contents" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span x-text="$t('settings.field_show_toc')"></span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_chapter_headers" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span x-text="$t('settings.field_show_chapter_headers')"></span>
                                    </label>
                                </div>
                            </div>
                        </section>

                        {{-- Typography --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_typography')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_latin_font')"></label>
                                        <select x-model="settingsForm.latin_font"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <template x-for="f in latinFonts" :key="f.value">
                                                <option :value="f.value" x-text="f.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_arabic_font')"></label>
                                        <select x-model="settingsForm.arabic_font"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <template x-for="f in arabicFonts" :key="f.value">
                                                <option :value="f.value" x-text="f.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_heading_font')"></label>
                                        <select x-model="settingsForm.heading_font"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <template x-for="f in allFonts" :key="f.value">
                                                <option :value="f.value" x-text="f.label"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_font_size')"></label>
                                        <input type="number" x-model.number="settingsForm.font_size" min="8" max="72" step="0.5"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_chapter_header_font_size')"></label>
                                        <input type="number" x-model.number="settingsForm.chapter_header_font_size" min="8" max="72" step="0.5"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_line_height')"></label>
                                        <input type="number" x-model.number="settingsForm.line_height" min="1" max="3" step="0.1"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <button @click="saveSettings()" :disabled="settingsSaving"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50"
                                    x-text="settingsSaving ? $t('settings.saving') : $t('settings.save')"></button>
                        </div>
                    </div>
                </div>
            </template>

            {{-- No selection placeholder --}}
            <template x-if="!settingsOpen && !selected">
                <div class="flex flex-1 items-center justify-center">
                    <p class="text-sm text-gray-400" x-text="$t('chapters.no_selection')"></p>
                </div>
            </template>

            {{-- Chapter editor --}}
            <template x-if="!settingsOpen && selected">
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

                settingsOpen: false,
                settingsForm: {},
                settingsSaving: false,

                templates: [
                    { value: 'classic',    label: 'Classic' },
                    { value: 'modern',     label: 'Modern' },
                    { value: 'elegant',    label: 'Elegant' },
                    { value: 'manuscript', label: 'Manuscript' },
                    { value: 'arabic',     label: 'Arabic' },
                ],
                latinFonts: [
                    { value: 'eb_garamond',       label: 'EB Garamond' },
                    { value: 'playfair_display',   label: 'Playfair Display' },
                    { value: 'merriweather',       label: 'Merriweather' },
                    { value: 'lora',               label: 'Lora' },
                    { value: 'crimson_pro',        label: 'Crimson Pro' },
                    { value: 'libre_baskerville',  label: 'Libre Baskerville' },
                    { value: 'cardo',              label: 'Cardo' },
                ],
                arabicFonts: [
                    { value: 'amiri',              label: 'Amiri' },
                    { value: 'cairo',              label: 'Cairo' },
                    { value: 'noto_naskh_arabic',  label: 'Noto Naskh Arabic' },
                    { value: 'tajawal',            label: 'Tajawal' },
                    { value: 'el_messiri',         label: 'El Messiri' },
                    { value: 'lateef',             label: 'Lateef' },
                    { value: 'reem_kufi',          label: 'Reem Kufi' },
                ],
                get allFonts() {
                    return [...this.latinFonts, ...this.arabicFonts];
                },

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
                        const [chaptersData, settingsData] = await Promise.all([
                            window.auth.fetchJson(`/api/books/${this.bookId}/chapters`),
                            window.auth.fetchJson(`/api/books/${this.bookId}/setting`),
                        ]);
                        this.chapters = chaptersData.data;
                        const { updated_at, ...settings } = settingsData.data;
                        this.settingsForm = settings;
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
                        this.settingsOpen = false;
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

                async saveSettings() {
                    this.settingsSaving = true;
                    try {
                        const data = await window.auth.fetchJson(`/api/books/${this.bookId}/setting`, {
                            method: 'PATCH',
                            body: JSON.stringify(this.settingsForm),
                        });
                        const { updated_at, ...settings } = data.data;
                        this.settingsForm = settings;
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('settings.saved'), type: 'success' },
                        }));
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.settingsSaving = false;
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
