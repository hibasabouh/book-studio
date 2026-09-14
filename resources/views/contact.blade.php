@extends('layouts.app')

@section('content')
    <div x-data="contactForm()" class="mx-auto max-w-2xl py-8" x-cloak>
        <div class="text-center">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900" x-text="$t('contact.title')"></h1>
            <p class="mx-auto mt-3 max-w-xl text-base text-gray-600" x-text="$t('contact.subtitle')"></p>
        </div>

        <div class="mt-8 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:p-8">
            <template x-if="sent">
                <div class="py-6 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="mt-4 text-lg font-semibold text-gray-900" x-text="$t('contact.sent_title')"></h2>
                    <p class="mt-1 text-sm text-gray-600" x-text="$t('contact.sent_body')"></p>
                    <button type="button" @click="reset()"
                            class="mt-6 rounded-md px-4 py-2 text-sm font-semibold text-emerald-600 ring-1 ring-emerald-200 hover:bg-emerald-50"
                            x-text="$t('contact.send_another')"></button>
                </div>
            </template>

            <template x-if="!sent">
                <div class="space-y-6">
                    <template x-if="error">
                        <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
                    </template>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-input-field name="name" tkey="field.name" label="Name" model="form.name" error="fieldError('name')" required autocomplete="name" />
                            <x-input-field name="email" tkey="field.email" label="Email" type="email" model="form.email" error="fieldError('email')" required autocomplete="email" />
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700" x-text="$t('contact.field_category')"></label>
                            <select id="category" x-model="form.category"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                                <option value="bug" x-text="$t('contact.category_bug')"></option>
                                <option value="feature" x-text="$t('contact.category_feature')"></option>
                                <option value="general" x-text="$t('contact.category_general')"></option>
                            </select>
                            <p class="mt-1 text-xs text-red-600" x-show="fieldError('category')" x-text="fieldError('category')"></p>
                        </div>

                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700" x-text="$t('contact.field_message')"></label>
                            <textarea id="message" x-model="form.message" rows="5" required
                                      :placeholder="$t('contact.field_message_placeholder')"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"></textarea>
                            <p class="mt-1 text-xs text-red-600" x-show="fieldError('message')" x-text="fieldError('message')"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700" x-text="$t('contact.field_attachments')"></label>
                            <label class="mt-1 flex w-full cursor-pointer items-center justify-center rounded-md border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500 hover:border-emerald-400 hover:text-emerald-600">
                                <span x-text="$t('contact.field_attachments_hint')"></span>
                                <input type="file" class="hidden" multiple
                                       accept="image/jpeg,image/png,image/webp,application/pdf,text/plain,.doc,.docx"
                                       @change="addAttachments($event)">
                            </label>

                            <ul class="mt-2 space-y-1" x-show="attachments.length">
                                <template x-for="(file, index) in attachments" :key="index">
                                    <li class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-1.5 text-sm text-gray-700">
                                        <span class="truncate" x-text="file.name"></span>
                                        <button type="button" @click="removeAttachment(index)" :aria-label="$t('contact.remove_attachment')"
                                                class="ms-2 shrink-0 text-gray-400 hover:text-red-600">&times;</button>
                                    </li>
                                </template>
                            </ul>
                            <p class="mt-1 text-xs text-red-600" x-show="attachmentError()" x-text="attachmentError()"></p>
                        </div>

                        <button type="submit" :disabled="loading"
                                class="flex w-full justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50">
                            <span x-text="loading ? $t('contact.submitting') : $t('contact.submit')"></span>
                        </button>
                    </form>
                </div>
            </template>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm font-medium text-gray-700" x-text="$t('contact.direct_title')"></p>
            <p class="mt-1 text-sm text-gray-500" x-html="$t('contact.direct_body')"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function contactForm() {
            return {
                form: { name: '', email: '', category: 'general', message: '' },
                attachments: [],
                error: null,
                errors: {},
                loading: false,
                sent: false,
                fieldError(field) {
                    return this.errors[field]?.[0] || '';
                },
                attachmentError() {
                    const key = Object.keys(this.errors).find(k => k === 'attachments' || k.startsWith('attachments.'));
                    return key ? this.errors[key][0] : '';
                },
                addAttachments(event) {
                    const files = Array.from(event.target.files || []);
                    this.attachments = [...this.attachments, ...files].slice(0, 5);
                    event.target.value = '';
                },
                removeAttachment(index) {
                    this.attachments.splice(index, 1);
                },
                async submit() {
                    this.loading = true;
                    this.error = null;
                    this.errors = {};
                    try {
                        const body = new FormData();
                        body.append('name', this.form.name);
                        body.append('email', this.form.email);
                        body.append('category', this.form.category);
                        body.append('message', this.form.message);
                        this.attachments.forEach(file => body.append('attachments[]', file));

                        await window.auth.fetchJson('/contact', { method: 'POST', body });
                        this.sent = true;
                    } catch (e) {
                        if (e.status === 422) {
                            this.errors = e.data?.errors || {};
                            this.error = e.data?.message || this.$store.i18n.t('contact.error');
                        }
                    } finally {
                        this.loading = false;
                    }
                },
                reset() {
                    this.form = { name: '', email: '', category: 'general', message: '' };
                    this.attachments = [];
                    this.sent = false;
                },
            };
        }
    </script>
@endpush
