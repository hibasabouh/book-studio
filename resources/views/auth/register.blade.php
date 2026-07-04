@extends('layouts.guest')

@section('content')
    <div x-data="registerForm()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900" x-text="$t('register.title')">Create your account</h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('register.subtitle')">Start building your books.</p>
        </div>

        <template x-if="error">
            <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <x-input-field name="name" tkey="field.name" label="Name" model="form.name" error="fieldError('name')" required autocomplete="name" />

            <x-input-field name="email" tkey="field.email" label="Email" type="email" model="form.email" error="fieldError('email')" required autocomplete="email" />

            <x-input-field name="password" tkey="field.password" label="Password" type="password" model="form.password" error="fieldError('password')" required autocomplete="new-password" toggleable />

            <x-input-field name="password_confirmation" tkey="field.confirm_password" label="Confirm password" type="password" model="form.password_confirmation" required autocomplete="new-password" toggleable />

            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <span x-text="loading ? $t('register.submitting') : $t('register.submit')"></span>
            </button>
        </form>

        <x-google-auth-button />

        <p class="text-center text-sm text-gray-500">
            <span x-text="$t('register.have_account')"></span>
            <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('register.sign_in')"></a>
        </p>
    </div>
@endsection

@push('scripts')
    <script>
        function registerForm() {
            return {
                form: { name: '', email: '', password: '', password_confirmation: '' },
                error: null,
                errors: {},
                loading: false,
                fieldError(field) {
                    return this.errors[field]?.[0] || '';
                },
                async submit() {
                    this.loading = true;
                    this.error = null;
                    this.errors = {};
                    try {
                        const data = await window.auth.fetchJson('/register', {
                            method: 'POST',
                            body: JSON.stringify(this.form),
                        });
                        this.$store.auth.login(data.user);
                        // New accounts are unverified — go enter the emailed OTP.
                        window.location.href = '/email/verify';
                    } catch (e) {
                        if (e.status === 422) {
                            this.errors = e.data?.errors || {};
                            this.error = e.data?.message || this.$store.i18n.t('register.error');
                        }
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
