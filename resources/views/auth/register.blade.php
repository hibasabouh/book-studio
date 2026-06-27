@extends('layouts.guest')

@section('content')
    <div x-data="registerForm()" class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight text-gray-900">Create your account</h2>
            <p class="mt-1 text-sm text-gray-500">Start building your books.</p>
        </div>

        <template x-if="error">
            <div class="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700 ring-1 ring-red-600/20" x-text="error"></div>
        </template>

        <form @submit.prevent="submit" class="space-y-4">
            <x-input-field name="name" label="Name" model="form.name" error="fieldError('name')" required autocomplete="name" />

            <x-input-field name="email" label="Email" type="email" model="form.email" error="fieldError('email')" required autocomplete="email" />

            <x-input-field name="password" label="Password" type="password" model="form.password" error="fieldError('password')" required autocomplete="new-password" />

            <x-input-field name="password_confirmation" label="Confirm password" type="password" model="form.password_confirmation" required autocomplete="new-password" />

            <button type="submit" :disabled="loading"
                    class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                <span x-text="loading ? 'Creating account…' : 'Create account'"></span>
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            Already have an account?
            <a href="/login" class="font-medium text-indigo-600 hover:text-indigo-500">Sign in</a>
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
                        const data = await window.auth.fetchJson('/api/register', {
                            method: 'POST',
                            body: JSON.stringify(this.form),
                        });
                        window.auth.setToken(data.token);
                        // TODO: redirect to /books once the library exists (Commit 10).
                        window.location.href = '/';
                    } catch (e) {
                        if (e.status === 422) {
                            this.errors = e.data?.errors || {};
                            this.error = e.data?.message || 'Please fix the errors below.';
                        } else {
                            this.error = e.data?.message || 'Unable to register. Please try again.';
                        }
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
@endpush
