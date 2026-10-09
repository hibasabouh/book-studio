<?php

use Illuminate\Database\QueryException;
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
        // Resolve the request locale (en/ar/fr) from the X-Locale header.
        $middleware->web(prepend: [\App\Http\Middleware\SetLocale::class]);
        $middleware->api(prepend: [\App\Http\Middleware\SetLocale::class]);

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
        // Never leak SQL to the client, even with APP_DEBUG on; the full error is still logged.
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('messages.server_error')], 500);
            }
        });
    })->create();
