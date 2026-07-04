/**
 * Session-based auth helper for the web frontend.
 *
 * Auth state is carried by the Laravel session cookie (httpOnly, set server-side
 * on login). All same-origin fetch calls automatically include the cookie, and
 * Sanctum's EnsureFrontendRequestsAreStateful middleware authenticates them via
 * the session. CSRF is handled via the XSRF-TOKEN cookie that Laravel sets when
 * the session starts.
 */

import { getLocale, translate } from './i18n';

function xsrfToken() {
    const match = document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='));
    return match ? decodeURIComponent(match.split('=')[1]) : null;
}

/**
 * fetch() wrapper that attaches JSON + locale headers, adds the XSRF token for
 * CSRF-protected requests, parses the response body, and throws a rich error
 * (with `status` and `data`) on non-2xx.
 *
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<any>} parsed JSON body (or null for empty responses)
 */
export async function fetchJson(url, options = {}) {
    const isFormData = typeof FormData !== 'undefined' && options.body instanceof FormData;

    const headers = {
        Accept: 'application/json',
        'X-Locale': getLocale(),
        ...(options.body && !isFormData ? { 'Content-Type': 'application/json' } : {}),
        ...(options.headers || {}),
    };

    const xsrf = xsrfToken();
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

    let response;
    try {
        response = await fetch(url, { ...options, headers });
    } catch (networkError) {
        window.dispatchEvent(new CustomEvent('app:toast', {
            detail: { message: translate('errors.network'), type: 'error' },
        }));
        throw Object.assign(new Error(translate('errors.network')), { status: 0, data: null });
    }

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (!response.ok) {
        // Auto-toast every non-422 error. 422s carry field-level validation messages
        // that each form handles inline — they are intentionally excluded here.
        if (response.status !== 422) {
            window.dispatchEvent(new CustomEvent('app:toast', {
                detail: { message: data?.message || `Request failed (${response.status})`, type: 'error' },
            }));
        }

        throw Object.assign(new Error(data?.message || `Request failed (${response.status})`), {
            status: response.status,
            data,
        });
    }

    return data;
}
