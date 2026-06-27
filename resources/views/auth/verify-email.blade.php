@extends('layouts.guest')

@section('content')
    <div x-data="verifyEmail()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900" x-text="$t('verify.title')">Verify your email</h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('verify.subtitle')">
                We emailed you a 6-digit verification code. Enter it below to activate your account.
            </p>
        </div>

        <template x-if="message">
            <div class="rounded-md px-3 py-2 text-sm ring-1"
                 :class="ok ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20'"
                 x-text="message"></div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <x-input-field name="code" tkey="field.code" label="Verification code" model="form.code"
                           inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />

            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <span x-text="loading ? $t('verify.submitting') : $t('verify.submit')"></span>
            </button>
        </form>

        <button type="button" @click="resend" :disabled="loading"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50"
                x-text="$t('verify.resend')">
        </button>
    </div>
@endsection

@push('scripts')
    <script>
        function verifyEmail() {
            return {
                form: { code: '' },
                loading: false,
                message: null,
                ok: false,
                async submit() {
                    this.loading = true;
                    this.message = null;
                    try {
                        const data = await window.auth.fetchJson('/api/email/verify-otp', {
                            method: 'POST',
                            body: JSON.stringify(this.form),
                        });
                        this.ok = true;
                        this.message = data.message;
                        // TODO: redirect to /books once the library exists (Commit 10).
                        setTimeout(() => (window.location.href = '/'), 800);
                    } catch (e) {
                        this.ok = false;
                        this.message = e.data?.message || this.$store.i18n.t('verify.error');
                    } finally {
                        this.loading = false;
                    }
                },
                async resend() {
                    this.loading = true;
                    this.message = null;
                    try {
                        const data = await window.auth.fetchJson('/api/email/resend-otp', { method: 'POST' });
                        this.ok = true;
                        this.message = data.message;
                    } catch (e) {
                        this.ok = false;
                        this.message = e.data?.message || 'Could not resend — please sign in first.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
