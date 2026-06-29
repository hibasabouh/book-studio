/**
 * Sessionless auth helper.
 *
 * The web UI is a thin Blade shell: it stores the Sanctum bearer token in
 * localStorage and attaches it to every authenticated fetch. There is no
 * session cookie and no session-backed CSRF — auth state is purely the
 * presence/validity of the token, confirmed by `GET /api/user`.
 */

import { getLocale } from './i18n';

const TOKEN_KEY = 'book_studio_token';

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
    // Mirror in a plain cookie so the server-side web.auth middleware can validate it.
    document.cookie = `${TOKEN_KEY}=${token}; path=/; max-age=31536000; SameSite=Lax`;
}

export function clearToken() {
    localStorage.removeItem(TOKEN_KEY);
    document.cookie = `${TOKEN_KEY}=; path=/; max-age=0; SameSite=Lax`;
}

export function isAuthenticated() {
    return !!getToken();
}

/**
 * fetch() wrapper that attaches the bearer token and JSON headers, parses the
 * JSON body, and throws a rich error (with `status` and `data`) on non-2xx.
 *
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>} parsed JSON body (or null for empty responses)
 */
export async function fetchJson(url, options = {}) {
    // For FormData (file uploads) let the browser set the multipart Content-Type.
    const isFormData = typeof FormData !== 'undefined' && options.body instanceof FormData;

    const headers = {
        Accept: 'application/json',
        'X-Locale': getLocale(),
        ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers || {}),
    };

    const token = getToken();
    if (token) {
        headers.Authorization = `Bearer ${token}`;
    }

    const response = await fetch(url, { ...options, headers });

    if (response.status === 401) {
        // Token is missing/expired — drop it so the UI reflects logged-out state.
        clearToken();
    }

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        throw Object.assign(new Error(data?.message || `Request failed (${response.status})`), {
            status: response.status,
            data,
        });
    }

    return data;
}
