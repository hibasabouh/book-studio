@extends('layouts.app')

@php
    use App\Enums\Role;
@endphp

@section('content')
    <div x-data="adminUserDetailsPage({{ $userId }})" x-init="init()" class="mx-auto flex max-w-6xl flex-col gap-6 sm:flex-row" x-cloak>
        @include('partials.admin-sidebar', ['active' => 'users'])

        <div class="min-w-0 flex-1 space-y-6">
            <a href="/admin/users" class="inline-flex items-center gap-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                <svg class="h-4 w-4 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
                <span x-text="$t('admin.back_to_users')"></span>
            </a>

            <template x-if="user">
                <div class="space-y-6">
                    {{-- Header --}}
                    <div class="flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5 sm:flex-row sm:items-center">
                        <template x-if="user.avatar">
                            <img :src="user.avatar" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-1 ring-gray-200">
                        </template>
                        <template x-if="!user.avatar">
                            <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xl font-semibold text-gray-400"
                                  x-text="(user.name || '?').charAt(0).toUpperCase()"></span>
                        </template>
                        <div class="min-w-0 flex-1">
                            <h1 class="truncate text-2xl font-semibold tracking-tight text-gray-900" x-text="user.name"></h1>
                            <p class="truncate text-sm text-gray-500" x-text="user.email"></p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"
                                      x-text="user.role"></span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-700': user.status === 'active',
                                          'bg-gray-100 text-gray-600': user.status === 'inactive',
                                          'bg-red-100 text-red-700': user.status === 'suspended',
                                      }"
                                      x-text="$t(`admin.user_status_${user.status}`)"></span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                      :class="user.email_verified_at ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                                      x-text="user.email_verified_at ? $t('admin.status_verified') : $t('admin.status_unverified')"></span>
                                <span class="text-xs text-gray-400"
                                      x-text="`${$t('admin.col_joined')}: ${user.created_at ? new Date(user.created_at).toLocaleDateString() : ''}`"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                            <p class="text-sm text-gray-500" x-text="$t('admin.stat_books')"></p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.books_total ?? '—'"></p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                            <p class="text-sm text-gray-500" x-text="$t('admin.stat_chapters')"></p>
                            <p class="mt-2 text-3xl font-semibold text-gray-900" x-text="stats.chapters_total ?? '—'"></p>
                        </div>
                    </div>

                    {{-- Edit form --}}
                    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <h2 class="text-base font-semibold text-gray-900" x-text="$t('admin.edit_user_title')"></h2>
                        <p class="mt-1 text-sm text-gray-500" x-text="$t('admin.edit_user_subtitle')"></p>

                        <form @submit.prevent="saveUser" class="mt-4 space-y-4">
                            {{-- Avatar --}}
                            <div class="flex items-center gap-4">
                                <template x-if="avatarPreview || user.avatar">
                                    <img :src="avatarPreview || user.avatar" alt="" class="h-14 w-14 rounded-full object-cover ring-1 ring-gray-200">
                                </template>
                                <template x-if="!avatarPreview && !user.avatar">
                                    <span class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-lg font-semibold text-gray-400"
                                          x-text="(form.name || '?').charAt(0).toUpperCase()"></span>
                                </template>
                                <div>
                                    <label class="cursor-pointer rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50">
                                        <span x-text="$t('profile.change_avatar')"></span>
                                        <input type="file" accept="image/*" class="hidden" @change="onAvatarChange($event)">
                                    </label>
                                    <p class="mt-2 text-xs text-gray-400" x-text="$t('profile.avatar_subtitle')"></p>
                                    <p class="mt-1 text-xs text-red-600" x-show="fieldError('avatar')" x-text="fieldError('avatar')"></p>
                                </div>
                            </div>

                            <x-input-field name="name" tkey="field.name" model="form.name" error="fieldError('name')" required />
                            <x-input-field name="email" tkey="field.email" type="email" model="form.email" error="fieldError('email')" required />
                            <x-input-field name="password" tkey="profile.new_password" type="password" model="form.password" error="fieldError('password')" autocomplete="new-password" />
                            <x-input-field name="password_confirmation" tkey="profile.confirm_password" type="password" model="form.password_confirmation" autocomplete="new-password" />
                            <p class="-mt-2 text-xs text-gray-400" x-text="$t('admin.password_hint')"></p>

                            <button type="submit" :disabled="saving"
                                    class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                                    x-text="saving ? $t('profile.saving') : $t('profile.save')"></button>
                        </form>
                    </section>

                    {{-- Books --}}
                    <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
                        <h2 class="text-base font-semibold text-gray-900" x-text="$t('admin.user_books')"></h2>

                        <p x-show="books.length === 0" class="mt-4 text-sm text-gray-400" x-text="$t('admin.user_no_books')"></p>

                        <ul x-show="books.length > 0" class="mt-4 divide-y divide-gray-100">
                            <template x-for="book in books" :key="book.id">
                                <li class="flex items-center justify-between py-3">
                                    <div class="min-w-0">
                                        <a :href="`/books/${book.id}`" class="truncate font-medium text-gray-900 hover:text-emerald-600" x-text="book.title"></a>
                                        <p class="truncate text-xs text-gray-400" x-text="book.description"></p>
                                    </div>
                                    <span class="ms-4 shrink-0 text-xs text-gray-500"
                                          x-text="`${book.chapters_count ?? 0} ${$t('books.chapters_count')}`"></span>
                                </li>
                            </template>
                        </ul>
                    </section>
                </div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function adminUserDetailsPage(userId) {
            return {
                userId,
                user: null,
                stats: { books_total: 0, chapters_total: 0 },
                books: [],
                loaded: false,
                form: { name: '', email: '', password: '', password_confirmation: '' },
                avatarFile: null,
                avatarPreview: null,
                errors: {},
                saving: false,
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
                        const data = await window.auth.fetchJson(`/api/admin/users/${this.userId}`);
                        this.user = data.user;
                        this.stats = data.stats;
                        this.books = data.books;
                        this.form.name = this.user.name;
                        this.form.email = this.user.email;
                    } catch (e) {
                        if (e.status === 403) window.location.href = '/dashboard';
                        else if (e.status === 404) window.location.href = '/admin/users';
                    }
                },
                fieldError(f) { return this.errors[f]?.[0] || ''; },
                onAvatarChange(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.avatarFile = file;
                    this.avatarPreview = URL.createObjectURL(file);
                },
                async saveUser() {
                    this.saving = true;
                    this.errors = {};
                    try {
                        // Multipart (with method spoofing) so the avatar file rides along.
                        const body = new FormData();
                        body.append('_method', 'PATCH');
                        body.append('name', this.form.name);
                        body.append('email', this.form.email);
                        if (this.avatarFile) body.append('avatar', this.avatarFile);
                        if (this.form.password) {
                            body.append('password', this.form.password);
                            body.append('password_confirmation', this.form.password_confirmation);
                        }
                        const data = await window.auth.fetchJson(`/api/admin/users/${this.userId}`, {
                            method: 'POST',
                            body,
                        });
                        this.user = data.data;
                        this.form.name = this.user.name;
                        this.form.email = this.user.email;
                        this.form.password = '';
                        this.form.password_confirmation = '';
                        this.avatarFile = null;
                        this.avatarPreview = null;
                        window.dispatchEvent(new CustomEvent('app:toast', {
                            detail: { message: this.$t('admin.user_saved'), type: 'success' },
                        }));
                    } catch (e) {
                        if (e.status === 422) this.errors = e.data?.errors || {};
                    } finally {
                        this.saving = false;
                    }
                },
            };
        }
    </script>
@endpush
