{{--
    Language switcher. Bound to the reactive i18n store, so picking a language
    updates every $t() binding instantly (no reload) and flips dir for Arabic.

    The native/forms-plugin arrow is removed (appearance-none + bg-none) and
    replaced with our own chevron positioned with the logical `end-2` so it sits
    correctly in both LTR and RTL.
--}}
<div x-data class="relative inline-block">
    <select @change="$store.i18n.set($event.target.value)"
            aria-label="Language"
            class="appearance-none rounded-md border-gray-300 bg-none py-1.5 pe-8 ps-3 text-sm text-gray-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="en" :selected="$store.i18n.locale === 'en'">English</option>
        <option value="ar" :selected="$store.i18n.locale === 'ar'">العربية</option>
        <option value="fr" :selected="$store.i18n.locale === 'fr'">Français</option>
    </select>

    <svg class="pointer-events-none absolute inset-y-0 end-2 my-auto h-4 w-4 text-gray-400"
         viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
    </svg>
</div>
