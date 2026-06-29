<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->cookie('book_studio_token');

        if (! $token || ! PersonalAccessToken::findToken($token)?->tokenable) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
