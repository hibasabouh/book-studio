<?php

use App\Exceptions\LocalizedErrorRenderer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Resolve the request locale (en/ar/fr) from the X-Locale header; global so
        // errors raised before a route matches (404/405/503) are translated too.
        $middleware->prepend(\App\Http\Middleware\SetLocale::class);

        // Written by the JS frontend in plain text (see resources/js/i18n.js).
        $middleware->encryptCookies(except: ['locale']);

        // Sanctum stateful SPA: authenticate web-frontend API requests via session
        // cookie instead of bearer token. Mobile / external clients still use tokens.
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'active' => \App\Http\Middleware\EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(fn (Throwable $e, Request $request) => app(LocalizedErrorRenderer::class)($e, $request));
    })->create();
