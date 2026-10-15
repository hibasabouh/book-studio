<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Resolve the request locale from the X-Locale header and apply it.
     *
     * Sessionless and header-driven: clients (web fetch + mobile) send their
     * chosen locale as X-Locale. Plain browser navigations can't set headers, so
     * the `locale` cookie mirrored by the web frontend is the fallback. Only locales
     * listed in config('app.supported_locales') are honored; anything else keeps the default.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale') ?? $request->cookie('locale');

        if ($locale && in_array($locale, config('app.supported_locales', ['en']), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
