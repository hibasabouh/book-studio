@extends('layouts.guest')

@section('content')
    <div x-data="forgotPasswordPage()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900" x-text="$t('forgot.title')"></h2>
            <p class="mt-1 text-sm text-gray-500" x-text="step === 1 ? $t('forgot.subtitle_email') : $t('forgot.subtitle_code')"></p>
        </div>

        <template x-if="error">
            <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
        </template>
        <template x-if="message">
            <div class="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-600/20" x-text="message"></div>
        </template>

        {{-- Step 1: request a code by email --}}
        <form x-show="step === 1" @submit.prevent="requestCode" class="space-y-4">
            <x-input-field name="email" tkey="field.email" type="email" model="email" error="fieldError('email')" required autocomplete="email" />
            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                    x-text="loading ? $t('forgot.sending') : $t('forgot.send_code')"></button>
        </form>

        {{-- Step 2: enter code + new password --}}
        <form x-show="step === 2" @submit.prevent="reset" class="space-y-4">
            <x-input-field name="code" tkey="field.code" model="form.code"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" error="fieldError('code')" required />
            <x-input-field name="password" tkey="field.password" type="password" model="form.password" error="fieldError('password')" required autocomplete="new-password" />
            <x-input-field name="password_confirmation" tkey="field.confirm_password" type="password" model="form.password_confirmation" required autocomplete="new-password" />
            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:opacity-50"
                    x-text="loading ? $t('forgot.resetting') : $t('forgot.submit')"></button>
        </form>

        <p class="text-center text-sm">
            <a href="/login" class="font-medium text-emerald-600 hover:text-emerald-500" x-text="$t('forgot.back_to_login')"></a>
        </p>
    </div>
@endsection

@push('scripts')
    <script>
        function forgotPasswordPage() {
            return {
                step: 1,
                userId: null,
                email: '',
                form: { code: '', password: '', password_confirmation: '' },
                errors: {},
                error: null,
                message: null,
                loading: false,

                fieldError(f) { return this.errors[f]?.[0] || ''; },

                async requestCode() {
                    this.loading = true;
                    this.error = null;
                    this.message = null;
                    this.errors = {};
                    try {
                        const data = await window.auth.fetchJson('/api/forgot-password', {
                            method: 'POST',
                            body: JSON.stringify({ email: this.email }),
                        });
                        this.userId = data.user_id;
                        this.message = data.message;
                        this.step = 2;
                    } catch (e) {
                        if (e.status === 422) {
                            this.errors = e.data?.errors || {};
                            this.error = e.data?.message || this.$store.i18n.t('forgot.error');
                        }
                    } finally {
                        this.loading = false;
                    }
                },

                async reset() {
                    this.loading = true;
                    this.error = null;
                    this.message = null;
                    this.errors = {};
                    try {
                        const data = await window.auth.fetchJson('/api/reset-password', {
                            method: 'POST',
                            body: JSON.stringify({ user_id: this.userId, ...this.form }),
                        });
                        this.message = data.message;
                        setTimeout(() => (window.location.href = '/login'), 1200);
                    } catch (e) {
                        if (e.status === 422) {
                            this.errors = e.data?.errors || {};
                            this.error = e.data?.message || this.$store.i18n.t('forgot.error');
                        }
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
