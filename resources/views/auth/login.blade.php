@extends('layouts.guest')

@section('content')
    <div x-data="loginForm()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900" x-text="$t('login.title')">Sign in to your account</h2>
            <p class="mt-1 text-sm text-gray-500" x-text="$t('login.subtitle')">Welcome back.</p>
        </div>

        <template x-if="error">
            <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <x-input-field name="email" tkey="field.email" label="Email" type="email" model="form.email" required autocomplete="email" />

            <x-input-field name="password" tkey="field.password" label="Password" type="password" model="form.password" required autocomplete="current-password" />

            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <span x-text="loading ? $t('login.submitting') : $t('login.submit')"></span>
            </button>
        </form>

        <x-google-auth-button />

        <p class="text-center text-sm text-gray-500">
            <span x-text="$t('login.no_account')"></span>
            <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500" x-text="$t('login.create_one')"></a>
        </p>
    </div>
@endsection

@push('scripts')
    <script>
        function loginForm() {
            return {
                form: { email: '', password: '' },
                error: null,
                loading: false,
                async submit() {
                    this.loading = true;
                    this.error = null;
                    try {
                        const data = await window.auth.fetchJson('/api/login', {
                            method: 'POST',
                            body: JSON.stringify(this.form),
                        });
                        window.auth.setToken(data.token);
                        window.location.href = '/dashboard';
                    } catch (e) {
                        this.error = e.data?.message || this.$store.i18n.t('login.error');
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
