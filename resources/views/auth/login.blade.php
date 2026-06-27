@extends('layouts.guest')

@section('content')
    <div x-data="loginForm()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Sign in to your account</h2>
            <p class="mt-1 text-sm text-gray-500">Welcome back.</p>
        </div>

        <template x-if="error">
            <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <x-input-field name="email" label="Email" type="email" model="form.email" required autocomplete="email" />

            <x-input-field name="password" label="Password" type="password" model="form.password" required autocomplete="current-password" />

            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <span x-text="loading ? 'Signing in…' : 'Sign in'"></span>
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            No account?
            <a href="/register" class="font-medium text-indigo-600 hover:text-indigo-500">Create one</a>
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
                        // TODO: redirect to /books once the library exists (Commit 10).
                        window.location.href = '/';
                    } catch (e) {
                        this.error = e.data?.message || 'Unable to sign in. Please try again.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
