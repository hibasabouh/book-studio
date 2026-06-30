import './bootstrap';

import Alpine from 'alpinejs';
import { fetchJson } from './auth';
import { getLocale, setLocale, translate, isRtl, applyDir, SUPPORTED } from './i18n';

window.Alpine = Alpine;

// Expose fetchJson for inline Blade scripts.
window.auth = { fetchJson };

/**
 * Reactive UI locale. Components read text via the `$t` magic; changing
 * `$store.i18n.locale` re-renders every binding, so language switches live.
 */
Alpine.store('i18n', {
    locale: getLocale(),
    supported: SUPPORTED,

    get dir() {
        return isRtl(this.locale) ? 'rtl' : 'ltr';
    },

    t(key) {
        return translate(key, this.locale);
    },

    set(locale) {
        setLocale(locale);
        this.locale = locale;
    },
});

Alpine.magic('t', () => (key) => Alpine.store('i18n').t(key));

/**
 * Promise-based confirmation dialog, a drop-in replacement for window.confirm.
 * `ask()` opens the shared modal (see partials/confirm.blade.php) and resolves
 * to true/false once the user picks an action.
 */
Alpine.store('confirm', {
    open: false,
    message: '',
    confirmLabel: '',
    _resolve: null,

    ask(message, confirmLabel = null) {
        this.message = message;
        this.confirmLabel = confirmLabel ?? Alpine.store('i18n').t('common.delete');
        this.open = true;
        return new Promise((resolve) => { this._resolve = resolve; });
    },

    respond(result) {
        if (!this.open) return;
        this.open = false;
        const resolve = this._resolve;
        this._resolve = null;
        resolve?.(result);
    },
});

/**
 * Admin-only view mode. Admins can switch between the regular "user" experience
 * and the "admin" dashboard; the choice persists across page loads. For
 * non-admins it stays 'user' and the mode-gated UI never shows.
 */
Alpine.store('mode', {
    current: localStorage.getItem('mode') === 'admin' ? 'admin' : 'user',

    get isAdmin() {
        return this.current === 'admin';
    },

    set(mode) {
        this.current = mode === 'admin' ? 'admin' : 'user';
        localStorage.setItem('mode', this.current);
    },

    clear() {
        this.current = 'user';
        localStorage.removeItem('mode');
    },

    /** Landing path for a user, (re)setting their mode to match their role. */
    home(user) {
        const isAdmin = user?.role === 'admin';
        this.set(isAdmin ? 'admin' : 'user');

        return isAdmin ? '/admin' : '/dashboard';
    },
});

Alpine.store('toasts', {
    items: [],
    add(message, type = 'error') {
        const id = Date.now() + Math.random();
        this.items.push({ id, message, type });
        setTimeout(() => this.remove(id), 5000);
    },
    remove(id) {
        this.items = this.items.filter(t => t.id !== id);
    },
});

/**
 * Global, reactive auth state. Auth is session-based: the server sets an httpOnly
 * session cookie on login; `fetchUser` confirms it by calling GET /api/user.
 */
Alpine.store('auth', {
    user: null,
    ready: false,

    get check() {
        return !!this.user;
    },

    async fetchUser() {
        try {
            const data = await fetchJson('/api/user');
            this.user = data.user;
        } catch (e) {
            console.error(e);
            this.user = null;
        } finally {
            this.ready = true;
        }
    },

    login(user) {
        this.user = user;
        this.ready = true;
    },

    async logout() {
        try {
            await fetchJson('/logout', { method: 'POST' });
        } finally {
            this.user = null;
            Alpine.store('mode').clear();
        }
    },
});

document.addEventListener('alpine:init', () => {
    applyDir(getLocale());
    Alpine.store('auth').fetchUser();
});

Alpine.start();
