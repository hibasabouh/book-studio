{{--
    "Continue with Google" using Google Identity Services (ID-token flow).
    Renders only when a client ID is configured. The button itself is drawn by
    Google inside an iframe, so it can't be styled with our CSS — appearance is
    controlled via the data-* attributes below. Google returns the ID token to
    the global handleGoogleCredential() callback, which verifies it server-side.
--}}
@if (config('services.google.client_id'))
    <div class="space-y-4">
        <div class="relative">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase tracking-wide">
                <span class="bg-white px-2 text-gray-400" x-text="$t('common.or_continue_with')">or continue with</span>
            </div>
        </div>

        <div class="flex justify-center">
            <div id="g_id_onload"
                 data-client_id="{{ config('services.google.client_id') }}"
                 data-callback="handleGoogleCredential"
                 data-auto_prompt="false"></div>

            <div class="g_id_signin"
                 data-type="standard"
                 data-theme="outline"
                 data-size="large"
                 data-shape="pill"
                 data-text="continue_with"
                 data-logo_alignment="center"
                 data-width="320"></div>
        </div>
    </div>

    @push('head')
        <script src="https://accounts.google.com/gsi/client" async></script>
    @endpush

    @push('scripts')
        <script>
            // Invoked by the Google Identity Services button.
            // response.credential is the Google ID token, verified server-side.
            window.handleGoogleCredential = async function (response) {
                try {
                    const data = await window.auth.fetchJson('/api/auth/google', {
                        method: 'POST',
                        body: JSON.stringify({ id_token: response.credential }),
                    });
                    window.auth.setToken(data.token);
                    // Google accounts are pre-verified — straight in.
                    // TODO: redirect to /books once the library exists (Commit 10).
                    window.location.href = '/';
                } catch (e) {
                    alert(e.data?.message || window.Alpine.store('i18n').t('google.error'));
                }
            };
        </script>
    @endpush
@endif
