@extends('layouts.app')

@php
    use App\Enums\BookFont;
    use App\Enums\PdfTemplate;
    use App\Services\CoverImageService;

    // Single source of truth for the pickers: options are rendered statically
    // from the enums (below) so the saved value preselects reliably and the
    // lists never drift from the backend.
    $latinFonts  = array_filter(BookFont::cases(), fn (BookFont $f) => $f->script() === 'latin');
    $arabicFonts = array_filter(BookFont::cases(), fn (BookFont $f) => $f->script() === 'arabic');
@endphp

@section('content')
    <div x-data="bookEditor({{ $book->id }}, @js(file_url($book->cover_path)), {{ CoverImageService::aspectRatio() }})"
         x-init="init()" class="flex h-[calc(100vh-4rem)] overflow-hidden" x-cloak>

        {{-- Sidebar: chapter list --}}
        <aside class="flex w-64 flex-shrink-0 flex-col border-r border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-4 py-3">
                <a href="/books" class="text-sm text-gray-500 hover:text-gray-700">← <span x-text="$t('nav.library')"></span></a>
            </div>

            <div class="border-b border-gray-200 px-4 py-3">
                <button @click="addChapter()"
                        class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                        x-text="$t('chapters.add')"></button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <template x-if="ready && chapters.length === 0">
                    <p class="px-4 py-6 text-center text-sm text-gray-400" x-text="$t('chapters.empty')"></p>
                </template>

                <ul class="divide-y divide-gray-100" x-ref="chapterList">
                    <template x-for="(chapter, index) in chapters" :key="chapter.id">
                        <li @click="select(chapter); settingsOpen = false"
                            :class="!settingsOpen && selected?.id === chapter.id ? 'bg-emerald-50' : 'hover:bg-gray-50'"
                            class="flex cursor-pointer items-center gap-1 px-2 py-2">

                            {{-- Drag handle --}}
                            <span class="drag-handle flex-shrink-0 cursor-grab touch-none rounded p-0.5 text-gray-300 hover:text-gray-500 active:cursor-grabbing"
                                  @click.stop
                                  :title="$t('chapters.drag_handle')">
                                <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 4a1 1 0 11-2 0 1 1 0 012 0zM7 10a1 1 0 11-2 0 1 1 0 012 0zM7 16a1 1 0 11-2 0 1 1 0 012 0zM15 4a1 1 0 11-2 0 1 1 0 012 0zM15 10a1 1 0 11-2 0 1 1 0 012 0zM15 16a1 1 0 11-2 0 1 1 0 012 0z"/>
                                </svg>
                            </span>

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

                            {{-- Order --}}
                            {{-- <span class="flex-shrink-0 text-xs tabular-nums text-gray-400" x-text="index + 1"></span> --}}

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

            {{-- Book-level toolbar: preview, export, settings each get their own labeled,
                 comfortably spaced button so they're easy to tell apart and hit accurately. --}}
            <div class="flex items-center justify-end gap-2 border-b border-gray-200 bg-white px-6 py-3">
                <button @click="openPreview()"
                        :disabled="preview.loading"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span x-text="$t('preview.button')"></span>
                </button>

                <button @click="exportPdf()"
                        :disabled="exporting"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 disabled:opacity-50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    <span x-text="$t('export.button')"></span>
                </button>

                <div class="mx-1 h-6 w-px bg-gray-200"></div>

                <button @click="settingsOpen = !settingsOpen"
                        :class="settingsOpen ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                        class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span x-text="$t('settings.tab')"></span>
                </button>
            </div>

            {{-- Settings panel --}}
            <template x-if="settingsOpen">
                <div class="flex flex-1 flex-col overflow-y-auto p-6">
                    <div class="mx-auto w-full max-w-2xl space-y-8">
                        <h2 class="text-lg font-semibold text-gray-900" x-text="$t('settings.title')"></h2>

                        {{-- Cover image --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('cover.section')"></h3>
                            <div class="flex items-start gap-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div class="w-24 flex-shrink-0 overflow-hidden rounded-md bg-gray-100 ring-1 ring-gray-200"
                                     :style="`aspect-ratio: ${coverRatio}`">
                                    <template x-if="coverUrl">
                                        <img :src="coverUrl" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!coverUrl">
                                        <span class="flex h-full items-center justify-center px-2 text-center text-xs text-gray-400"
                                              x-text="$t('cover.empty')"></span>
                                    </template>
                                </div>

                                <div class="flex-1 space-y-3">
                                    <p class="text-sm text-gray-500" x-text="$t('cover.hint')"></p>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <label class="cursor-pointer rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50">
                                            <span x-text="coverUrl ? $t('cover.replace') : $t('cover.upload')"></span>
                                            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden"
                                                   @change="pickCover($event)">
                                        </label>
                                        <button type="button" x-show="coverUrl" @click="removeCover()" :disabled="cover.saving"
                                                class="rounded-md px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 disabled:opacity-50"
                                                x-text="$t('cover.remove')"></button>
                                    </div>
                                    <p class="text-xs text-red-600" x-show="cover.error" x-text="cover.error"></p>
                                </div>
                            </div>
                        </section>

                        {{-- Metadata --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_metadata')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_author')"></label>
                                    <input type="text" x-model="settingsForm.author"
                                           :dir="settingsForm.text_direction || 'ltr'"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_subtitle')"></label>
                                    <input type="text" x-model="settingsForm.subtitle"
                                           :dir="settingsForm.text_direction || 'ltr'"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                                </div>
                            </div>
                        </section>

                        {{-- Layout --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_layout')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_template')"></label>
                                        <select x-model="settingsForm.template"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                            @foreach (PdfTemplate::cases() as $template)
                                                <option value="{{ $template->value }}">{{ $template->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_language')"></label>
                                        <select x-model="settingsForm.language"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                            <option value="en" x-text="$t('settings.language_en')"></option>
                                            <option value="ar" x-text="$t('settings.language_ar')"></option>
                                            <option value="fr" x-text="$t('settings.language_fr')"></option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_text_direction')"></label>
                                        <select x-model="settingsForm.text_direction"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                            <option value="ltr" x-text="$t('settings.direction_ltr')"></option>
                                            <option value="rtl" x-text="$t('settings.direction_rtl')"></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_page_numbers" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                        <span x-text="$t('settings.field_show_page_numbers')"></span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_table_of_contents" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                        <span x-text="$t('settings.field_show_toc')"></span>
                                    </label>
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" x-model="settingsForm.show_chapter_headers" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
                                        <span x-text="$t('settings.field_show_chapter_headers')"></span>
                                    </label>
                                </div>
                            </div>
                        </section>

                        {{-- Typography --}}
                        <section>
                            <h3 class="mb-3 text-xs font-medium uppercase tracking-wide text-gray-500" x-text="$t('settings.section_typography')"></h3>
                            <div class="space-y-4 rounded-lg bg-white p-4 ring-1 ring-gray-200">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_latin_font')"></label>
                                        <select x-model="settingsForm.latin_font"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                            @foreach ($latinFonts as $font)
                                                <option value="{{ $font->value }}">{{ $font->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_arabic_font')"></label>
                                        <select x-model="settingsForm.arabic_font"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                            @foreach ($arabicFonts as $font)
                                                <option value="{{ $font->value }}">{{ $font->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_font_size')"></label>
                                        <input type="number" x-model.number="settingsForm.font_size" min="8" max="72" step="0.5"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_chapter_header_font_size')"></label>
                                        <input type="number" x-model.number="settingsForm.chapter_header_font_size" min="8" max="72" step="0.5"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="$t('settings.field_line_height')"></label>
                                        <input type="number" x-model.number="settingsForm.line_height" min="1" max="3" step="0.1"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <div class="flex justify-end">
                            <button @click="saveSettings()" :disabled="settingsSaving"
                                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
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
                               :dir="settingsForm.text_direction || 'ltr'"
                               class="block w-full rounded-md border-gray-300 text-lg font-semibold shadow-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        <p class="mt-1 text-xs text-red-600" x-show="fieldError('title')" x-text="fieldError('title')"></p>
                    </div>

                    <div class="flex flex-1 flex-col overflow-hidden">
                        <div class="mb-1 flex items-center justify-between">
                            <label class="block text-xs font-medium uppercase tracking-wide text-gray-500"
                                   x-text="$t('chapters.field_content')"></label>
                            <button type="button" @click="openOcr()"
                                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span x-text="$t('chapters.ocr_button')"></span>
                            </button>
                        </div>
                        <textarea x-model="form.content"
                                  :dir="settingsForm.text_direction || 'ltr'"
                                  class="flex-1 block w-full resize-none rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                                  placeholder=""></textarea>
                        <p class="mt-1 text-xs text-red-600" x-show="fieldError('content')" x-text="fieldError('content')"></p>
                    </div>

                    <div class="mt-4 flex items-center justify-end">
                        <button @click="save()" :disabled="saving"
                                class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                                x-text="saving ? $t('chapters.saving') : $t('chapters.save')"></button>
                    </div>
                </div>
            </template>
        </main>

        {{-- Cover crop modal: the frame is the preview — what you see is what is cut. --}}
        <div x-show="cover.open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"
             @keydown.escape.window="closeCropper()">
            <div class="w-full max-w-xs rounded-lg bg-white p-6 shadow-xl" @click.outside="closeCropper()">
                <h2 class="text-base font-semibold text-gray-900" x-text="$t('cover.crop_title')"></h2>
                <p class="mt-1 text-xs text-gray-500" x-text="$t('cover.crop_hint')"></p>

                <template x-if="cover.cropper">
                    <div class="mt-4 space-y-3">
                        <div class="mx-auto touch-none select-none overflow-hidden rounded-md bg-gray-900"
                             :class="cover.cropper.dragging ? 'cursor-grabbing' : 'cursor-grab'"
                             :style="`width:${cover.cropper.frameWidth}px;height:${cover.cropper.frameHeight}px`"
                             @pointerdown.prevent="cover.cropper.start($event)"
                             @pointermove="cover.cropper.move($event)"
                             @pointerup="cover.cropper.end()"
                             @pointercancel="cover.cropper.end()"
                             @wheel.prevent="cover.cropper.wheel($event)">
                            <img :src="cover.cropper.source.src" :style="cover.cropper.style"
                                 alt="" draggable="false" class="max-w-none">
                        </div>

                        <input type="range" class="w-full accent-emerald-600" step="0.0001"
                               :min="cover.cropper.minScale" :max="cover.cropper.maxScale"
                               :value="cover.cropper.scale"
                               @input="cover.cropper.zoomTo(Number($event.target.value))"
                               :aria-label="$t('cover.zoom')">
                    </div>
                </template>

                <p class="mt-2 text-xs text-red-600" x-show="cover.error" x-text="cover.error"></p>

                <div class="mt-4 flex justify-end gap-3">
                    <button type="button" @click="closeCropper()"
                            class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                            x-text="$t('common.cancel')"></button>
                    <button type="button" @click="saveCover()" :disabled="cover.saving"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                            x-text="cover.saving ? $t('cover.saving') : $t('cover.apply')"></button>
                </div>
            </div>
        </div>

        {{-- PDF preview modal: the exported document itself, rendered inline. --}}
        <div x-show="preview.open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4"
             @keydown.escape.window="closePreview()">
            <div class="flex h-full w-full max-w-5xl flex-col overflow-hidden rounded-lg bg-white shadow-xl"
                 @click.outside="closePreview()">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                    <div class="flex items-baseline gap-2">
                        <h2 class="text-base font-semibold text-gray-900" x-text="$t('preview.title')"></h2>
                        <span class="text-xs text-gray-500" x-show="preview.pages"
                              x-text="`${preview.pages} ${$t('preview.pages_label')}`"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="exportPdf()" :disabled="exporting"
                                class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                                x-text="$t('preview.download')"></button>
                        <button type="button" @click="closePreview()"
                                class="rounded p-1 text-gray-400 hover:text-gray-600"
                                :title="$t('common.close')">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Pages are drawn into this container as canvases by pdf-preview.js. --}}
                <div class="relative flex-1 overflow-y-auto bg-gray-100 p-4">
                    <div x-ref="previewPages"></div>

                    <div x-show="preview.loading || preview.error"
                         class="absolute inset-0 flex flex-col items-center justify-center gap-2 bg-gray-100 px-6 text-center">
                        <p class="text-sm" :class="preview.error ? 'text-red-600' : 'text-gray-500'"
                           x-text="preview.error || $t('preview.loading')"></p>
                        <p class="max-w-md break-words font-mono text-xs text-gray-500"
                           x-show="preview.detail" x-text="preview.detail"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- OCR import modal --}}
        <div x-show="ocr.open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4"
             @keydown.escape.window="closeOcr()">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl" @click.outside="closeOcr()">
                <h2 class="text-base font-semibold text-gray-900" x-text="$t('chapters.ocr_title')"></h2>

                <template x-if="!ocr.text">
                    <div class="mt-4 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" x-text="$t('chapters.ocr_choose_file')"></label>
                        <input type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.pdf,image/*,application/pdf"
                               @change="onOcrFileChange($event)"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-700 hover:file:bg-emerald-100" />
                        <p class="text-xs text-gray-400" x-text="$t('chapters.ocr_hint')"></p>
                        <p class="text-xs text-red-600" x-show="ocr.error" x-text="ocr.error"></p>

                        <div class="mt-4 flex justify-end gap-3">
                            <button type="button" @click="closeOcr()"
                                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                                    x-text="$t('common.cancel')"></button>
                            <button type="button" @click="extractOcrText()" :disabled="!ocr.file || ocr.loading"
                                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                                    x-text="ocr.loading ? $t('chapters.ocr_extracting') : $t('chapters.ocr_extract')"></button>
                        </div>
                    </div>
                </template>

                <template x-if="ocr.text">
                    <div class="mt-4 space-y-3">
                        <label class="block text-sm font-medium text-gray-700" x-text="$t('chapters.ocr_result_label')"></label>
                        <textarea x-model="ocr.text" rows="8"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"></textarea>

                        <div class="mt-4 flex flex-wrap justify-end gap-3">
                            <button type="button" @click="closeOcr()"
                                    class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900"
                                    x-text="$t('common.cancel')"></button>
                            <button type="button" @click="applyOcr('copy')"
                                    class="rounded-md px-3 py-2 text-sm font-medium text-emerald-600 hover:text-emerald-800"
                                    x-text="$t('chapters.ocr_copy')"></button>
                            <button type="button" @click="applyOcr('append')"
                                    class="rounded-md bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-200"
                                    x-text="$t('chapters.ocr_append')"></button>
                            <button type="button" @click="applyOcr('set')"
                                    class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                                    x-text="$t('chapters.ocr_set')"></button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Crop frame width in CSS pixels; its height follows the cover ratio.
        const COVER_FRAME_WIDTH = 240;

        function base64ToBytes(base64) {
            const binary = atob(base64);
            const bytes = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);

            return bytes;
        }

        function bookEditor(bookId, coverUrl, coverRatio) {
            return {
                bookId,
                coverUrl,
                coverRatio,
                cover: { open: false, file: null, cropper: null, saving: false, error: null },
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
                exporting: false,
                preview: { open: false, loading: false, pages: 0, error: null, detail: null },
                previewAbort: null,

                ocr: { open: false, file: null, text: null, loading: false, error: null },

                init() {
                    this.$watch('$store.auth.ready', () => this.guard());
                    this.guard();
                    this.$nextTick(() => this.initSortable());
                },

                initSortable() {
                    if (!this.$refs.chapterList) return;
                    window.Sortable.create(this.$refs.chapterList, {
                        handle: '.drag-handle',
                        animation: 150,
                        onEnd: (evt) => {
                            if (evt.oldIndex !== evt.newIndex) this.reorder(evt.oldIndex, evt.newIndex);
                        },
                    });
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
                    this.ocr = { open: false, file: null, text: null, loading: false, error: null };
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

                /** Load the chosen file into the crop frame; nothing is uploaded yet. */
                async pickCover(event) {
                    const file = event.target.files[0];
                    event.target.value = '';
                    if (!file) return;

                    this.cover.error = null;
                    try {
                        const source = await window.cropper.loadImage(file);
                        this.cover.file = file;
                        this.cover.cropper = window.cropper.createCropper(
                            source,
                            COVER_FRAME_WIDTH,
                            Math.round(COVER_FRAME_WIDTH / this.coverRatio),
                        );
                        this.cover.open = true;
                    } catch (e) {
                        this.cover.error = this.$t('cover.unreadable');
                    }
                },

                closeCropper() {
                    this.cover.cropper?.release();
                    this.cover = { open: false, file: null, cropper: null, saving: false, error: null };
                },

                /** Upload the original file plus the framed rectangle; the server does the cut. */
                async saveCover() {
                    if (!this.cover.cropper) return;
                    this.cover.saving = true;
                    this.cover.error = null;

                    const rect = this.cover.cropper.rect();
                    const body = new FormData();
                    body.append('cover', this.cover.file);
                    body.append('crop_x', rect.x);
                    body.append('crop_y', rect.y);
                    body.append('crop_width', rect.width);
                    body.append('crop_height', rect.height);

                    try {
                        const data = await window.auth.fetchJson(`/api/books/${this.bookId}/cover`, { method: 'POST', body });
                        this.coverUrl = data.data.cover_url;
                        this.closeCropper();
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('cover.saved'), type: 'success' },
                        }));
                    } catch (e) {
                        this.cover.error = e.data?.errors?.cover?.[0] || this.$t('cover.error');
                    } finally {
                        this.cover.saving = false;
                    }
                },

                async removeCover() {
                    if (!await this.$store.confirm.ask(this.$t('cover.confirm_remove'))) return;
                    try {
                        await window.auth.fetchJson(`/api/books/${this.bookId}/cover`, { method: 'DELETE' });
                        this.coverUrl = null;
                    } catch (e) {
                        this.cover.error = this.$t('cover.error');
                    }
                },

                openOcr() {
                    this.ocr = { open: true, file: null, text: null, loading: false, error: null };
                },

                closeOcr() {
                    this.ocr.open = false;
                },

                onOcrFileChange(e) {
                    this.ocr.file = e.target.files[0] ?? null;
                    this.ocr.error = null;
                },

                async extractOcrText() {
                    if (!this.ocr.file || !this.selected) return;
                    this.ocr.loading = true;
                    this.ocr.error = null;
                    try {
                        const body = new FormData();
                        body.append('image', this.ocr.file);
                        const data = await window.auth.fetchJson(`/api/chapters/${this.selected.id}/ocr`, {
                            method: 'POST',
                            body,
                        });
                        this.ocr.text = data.text;
                    } catch (e) {
                        this.ocr.error = e.data?.errors?.image?.[0] || this.$t('chapters.ocr_error');
                    } finally {
                        this.ocr.loading = false;
                    }
                },

                applyOcr(mode) {
                    if (mode === 'set') {
                        this.form.content = this.ocr.text;
                    } else if (mode === 'append') {
                        this.form.content = this.form.content ? `${this.form.content}\n${this.ocr.text}` : this.ocr.text;
                    } else if (mode === 'copy') {
                        navigator.clipboard.writeText(this.ocr.text);
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('chapters.ocr_copied'), type: 'success' },
                        }));
                    }
                    this.closeOcr();
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
                    if (!await this.$store.confirm.ask(this.$t('chapters.confirm_delete'))) return;
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
                    await this.reorder(index, index - 1);
                },

                async moveDown(index) {
                    if (index === this.chapters.length - 1) return;
                    await this.reorder(index, index + 1);
                },

                async reorder(oldIndex, newIndex) {
                    const previous = this.chapters;
                    const updated = [...this.chapters];
                    const [moved] = updated.splice(oldIndex, 1);
                    updated.splice(newIndex, 0, moved);
                    this.chapters = updated;

                    try {
                        await window.auth.fetchJson(`/api/books/${this.bookId}/chapters/reorder`, {
                            method: 'POST',
                            body: JSON.stringify({ ids: this.chapters.map(c => c.id) }),
                        });
                    } catch (e) {
                        this.chapters = previous;
                    }
                },

                /**
                 * Fetch the exported PDF and draw it into the modal. Reflects saved
                 * state only — unsaved chapter or settings edits are not in the
                 * document yet.
                 */
                async openPreview() {
                    this.stopPreview();
                    this.preview = { open: true, loading: true, pages: 0, error: null, detail: null };
                    this.previewAbort = new AbortController();
                    const { signal } = this.previewAbort;

                    try {
                        // no-store: the document is rebuilt on every request, so a
                        // cached copy is always stale.
                        const data = await window.auth.fetchJson(
                            `/api/books/${this.bookId}/preview`, { cache: 'no-store' },
                        );
                        if (signal.aborted) return;
                        if (!data?.pdf) throw new Error(`no pdf in response (keys: ${Object.keys(data ?? {})})`);

                        // Let the modal paint first: the page width is measured off it.
                        await this.$nextTick();

                        this.preview.pages = await window.pdfPreview.render(
                            base64ToBytes(data.pdf), this.$refs.previewPages, signal,
                        );
                    } catch (e) {
                        // fetchBlob already toasts server errors; only render failures
                        // need a message inside the modal.
                        if (!signal.aborted) {
                            console.error('PDF preview failed:', e);
                            this.preview.error = this.$t('preview.error');
                            this.preview.detail = `${e.name}: ${e.message}`;
                        }
                    } finally {
                        if (!signal.aborted) this.preview.loading = false;
                    }
                },

                closePreview() {
                    this.stopPreview();
                    this.preview = { open: false, loading: false, pages: 0, error: null, detail: null };
                },

                /** Cancel an in-flight render and drop the drawn pages. */
                stopPreview() {
                    this.previewAbort?.abort();
                    this.previewAbort = null;
                    this.$refs.previewPages?.replaceChildren();
                },

                exportPdf() {
                    this.exporting = true;
                    // Direct navigation: browser sends session cookie, server returns
                    // Content-Disposition: attachment so the page stays and file downloads.
                    window.location.href = `/api/books/${this.bookId}/export`;
                    setTimeout(() => { this.exporting = false; }, 2000);
                },

                fieldError(f) { return this.errors[f]?.[0] || ''; },
            };
        }
    </script>
@endpush
