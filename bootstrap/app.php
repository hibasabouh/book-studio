<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sessionless by design: auth is bearer-token only (Sanctum). Remove the
        // session/CSRF middleware from the web group so no session is ever started
        // and no session cookie is set. Web routes are thin Blade shells that fetch
        // data with an Authorization: Bearer token, so none of this is needed.
        $middleware->web(remove: [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Resolve the request locale (en/ar/fr) from the X-Locale header.
        $middleware->web(prepend: [\App\Http\Middleware\SetLocale::class]);
        $middleware->api(prepend: [\App\Http\Middleware\SetLocale::class]);

        // The auth token cookie is written by JS (plain text), so skip encryption for it.
        $middleware->encryptCookies(except: ['book_studio_token']);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'web.auth' => \App\Http\Middleware\AuthenticateFromCookie::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
