@extends('layouts.app')

@section('content')
    <div x-data="profilePage()" x-init="init()" class="mx-auto max-w-2xl space-y-8" x-cloak>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900" x-text="$t('profile.title')"></h1>

        {{-- Avatar --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-base font-semibold text-gray-900" x-text="$t('profile.avatar_title')"></h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('profile.avatar_subtitle')"></p>

            <div class="mt-4 flex items-center gap-4">
                <template x-if="$store.auth.user?.avatar">
                    <img :src="$store.auth.user.avatar" alt="" class="h-16 w-16 rounded-full object-cover ring-1 ring-gray-200">
                </template>
                <template x-if="!$store.auth.user?.avatar">
                    <span class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-xl font-semibold text-gray-400"
                          x-text="($store.auth.user?.name || '?').charAt(0).toUpperCase()"></span>
                </template>

                <label class="cursor-pointer rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50">
                    <span x-text="uploadingAvatar ? $t('profile.uploading') : $t('profile.change_avatar')"></span>
                    <input type="file" accept="image/*" class="hidden" @change="uploadAvatar($event)" :disabled="uploadingAvatar">
                </label>
            </div>
            <p class="mt-2 text-xs text-red-600" x-show="avatarError" x-text="avatarError"></p>
        </section>

        {{-- Account information --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-base font-semibold text-gray-900" x-text="$t('profile.info_title')"></h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('profile.info_subtitle')"></p>

            <template x-if="infoMessage">
                <div class="mt-4 rounded-md px-3 py-2 text-sm ring-1"
                     :class="infoOk ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20'"
                     x-text="infoMessage"></div>
            </template>

            <form @submit.prevent="saveInfo" class="mt-4 space-y-4">
                <x-input-field name="name" tkey="field.name" model="profile.name" error="fieldError('name')" required />
                <x-input-field name="email" tkey="field.email" type="email" model="profile.email" error="fieldError('email')" required />
                <button type="submit" :disabled="savingInfo"
                        class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                        x-text="savingInfo ? $t('profile.saving') : $t('profile.save')"></button>
            </form>
        </section>

        {{-- Password --}}
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-900/5">
            <h2 class="text-base font-semibold text-gray-900" x-text="$t('profile.password_title')"></h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('profile.password_subtitle')"></p>

            <template x-if="pwMessage">
                <div class="mt-4 rounded-md px-3 py-2 text-sm ring-1"
                     :class="pwOk ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20'"
                     x-text="pwMessage"></div>
            </template>

            <form @submit.prevent="savePassword" class="mt-4 space-y-4">
                {{-- Only ask for the current password when the account actually has one. --}}
                <div x-show="$store.auth.user?.has_password">
                    <x-input-field name="current_password" tkey="profile.current_password" type="password" model="password.current_password" error="pwError('current_password')" autocomplete="current-password" />
                </div>
                <x-input-field name="new_password" tkey="profile.new_password" type="password" model="password.password" error="pwError('password')" required autocomplete="new-password" />
                <x-input-field name="new_password_confirmation" tkey="profile.confirm_password" type="password" model="password.password_confirmation" required autocomplete="new-password" />
                <button type="submit" :disabled="savingPassword"
                        class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                        x-text="savingPassword ? $t('profile.saving_password') : ($store.auth.user?.has_password ? $t('profile.update_password') : $t('profile.set_password'))"></button>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        function profilePage() {
            return {
                profile: { name: '', email: '' },
                password: { current_password: '', password: '', password_confirmation: '' },
                infoErrors: {}, pwErrors: {},
                infoMessage: null, infoOk: false,
                pwMessage: null, pwOk: false,
                savingInfo: false, savingPassword: false,
                uploadingAvatar: false, avatarError: null,

                init() {
                    const sync = () => {
                        const u = this.$store.auth.user;
                        if (u) {
                            this.profile.name = u.name;
                            this.profile.email = u.email;
                        }
                    };
                    // Redirect to login once we know the user is not authenticated.
                    this.$watch('$store.auth.ready', () => {
                        if (this.$store.auth.ready && !this.$store.auth.check) {
                            window.location.href = '/login';
                        }
                    });
                    this.$watch('$store.auth.user', sync);
                    sync();
                },

                fieldError(f) { return this.infoErrors[f]?.[0] || ''; },
                pwError(f) { return this.pwErrors[f]?.[0] || ''; },

                async uploadAvatar(event) {
                    const file = event.target.files[0];
                    if (!file) return;
                    this.uploadingAvatar = true;
                    this.avatarError = null;
                    try {
                        const body = new FormData();
                        body.append('avatar', file);
                        const data = await window.auth.fetchJson('/api/profile/avatar', { method: 'POST', body });
                        this.$store.auth.user = data.user;
                    } catch (e) {
                        this.avatarError = e.data?.errors?.avatar?.[0] || e.data?.message || this.$store.i18n.t('profile.error');
                    } finally {
                        this.uploadingAvatar = false;
                        event.target.value = '';
                    }
                },

                async saveInfo() {
                    this.savingInfo = true;
                    this.infoMessage = null;
                    this.infoErrors = {};
                    try {
                        const data = await window.auth.fetchJson('/api/profile', {
                            method: 'PATCH',
                            body: JSON.stringify(this.profile),
                        });
                        this.$store.auth.user = data.user;
                        if (data.email_changed) {
                            window.location.href = '/email/verify';
                            return;
                        }
                        this.infoOk = true;
                        this.infoMessage = data.message;
                    } catch (e) {
                        this.infoOk = false;
                        if (e.status === 422) this.infoErrors = e.data?.errors || {};
                        this.infoMessage = e.data?.message || this.$store.i18n.t('profile.error');
                    } finally {
                        this.savingInfo = false;
                    }
                },

                async savePassword() {
                    this.savingPassword = true;
                    this.pwMessage = null;
                    this.pwErrors = {};
                    try {
                        const data = await window.auth.fetchJson('/api/profile/password', {
                            method: 'PUT',
                            body: JSON.stringify(this.password),
                        });
                        this.pwOk = true;
                        this.pwMessage = data.message;
                        this.password = { current_password: '', password: '', password_confirmation: '' };
                    } catch (e) {
                        this.pwOk = false;
                        if (e.status === 422) this.pwErrors = e.data?.errors || {};
                        this.pwMessage = e.data?.message || this.$store.i18n.t('profile.error');
                    } finally {
                        this.savingPassword = false;
                    }
                },
            };
        }
    </script>
@endpush
