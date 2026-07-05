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

// Page shells served behind the `auth` middleware (see routes/web.php). A 401
// on one of these means the session expired, so the visitor is sent to /login;
// on public pages a 401 is expected for guests and is ignored.
const PROTECTED_PREFIXES = ['/dashboard', '/profile', '/admin', '/books'];

function isProtectedPage() {
    const path = window.location.pathname;
    return PROTECTED_PREFIXES.some(p => path === p || path.startsWith(p + '/'));
}

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
        // 401s never toast: on protected pages the session expired, so go to
        // /login; on public pages they are expected for guests and ignored.
        if (response.status === 401) {
            if (isProtectedPage()) window.location.href = '/login';
        }
        // Auto-toast every other non-422 error. 422s carry field-level validation
        // messages that each form handles inline — intentionally excluded here.
        else if (response.status !== 422) {
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
