import './bootstrap';

import Alpine from 'alpinejs';
import { getToken, setToken, clearToken, fetchJson } from './auth';

window.Alpine = Alpine;

// Expose the low-level helpers for inline Blade scripts that don't need the store.
window.auth = { getToken, setToken, clearToken, fetchJson };

/**
 * Global, reactive auth state. Components read `$store.auth.check` to know if a
 * user is logged in and `$store.auth.user` for the current user. It is sessionless:
 * everything derives from the bearer token in localStorage.
 */
Alpine.store('auth', {
    token: getToken(),
    user: null,
    ready: false,

    get check() {
        return !!this.token;
    },

    /** Confirm the token by loading the current user. Logs out on failure. */
    async fetchUser() {
        if (!this.token) {
            this.ready = true;
            return;
        }
        try {
            this.user = await fetchJson('/api/user');
        } catch (e) {
            this.logout();
        } finally {
            this.ready = true;
        }
    },

    /** Persist a freshly issued token and load the user. */
    async login(token) {
        setToken(token);
        this.token = token;
        await this.fetchUser();
    },

    logout() {
        clearToken();
        this.token = null;
        this.user = null;
    },
});

document.addEventListener('alpine:init', () => {
    Alpine.store('auth').fetchUser();
});

Alpine.start();
