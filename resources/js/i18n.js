/**
 * Client-side UI localization (en/ar/fr).
 *
 * The chosen locale lives in localStorage and is also sent to the API as the
 * X-Locale header (see auth.js). UI strings are swapped reactively via the
 * Alpine `$t` magic, so switching language never requires a page reload.
 */
import en from './i18n/en.json';
import ar from './i18n/ar.json';
import fr from './i18n/fr.json';

const DICTS = { en, ar, fr };

export const SUPPORTED = ['en', 'ar', 'fr'];
export const RTL_LOCALES = ['ar'];
export const DEFAULT_LOCALE = 'en';

export function getLocale() {
    const stored = localStorage.getItem('locale');
    return SUPPORTED.includes(stored) ? stored : DEFAULT_LOCALE;
}

export function setLocale(locale) {
    if (SUPPORTED.includes(locale)) {
        localStorage.setItem('locale', locale);
        applyDir(locale);
    }
}

export function isRtl(locale = getLocale()) {
    return RTL_LOCALES.includes(locale);
}

export function translate(key, locale = getLocale()) {
    return DICTS[locale]?.[key] ?? DICTS[DEFAULT_LOCALE][key] ?? key;
}

/** Reflect the locale on <html> (lang + dir) for RTL support. */
export function applyDir(locale = getLocale()) {
    document.documentElement.lang = locale;
    document.documentElement.dir = isRtl(locale) ? 'rtl' : 'ltr';
}
