<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Block users whose email address is not yet verified.
     * Returns 403 for JSON clients, redirects web clients to the notice page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                abort(403, __('messages.email_not_verified'));
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
