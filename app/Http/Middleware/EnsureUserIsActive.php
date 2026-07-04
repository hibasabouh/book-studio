<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Block inactive/suspended users from performing any authenticated action.
     * The account is signed out and returned a 403 (JSON) or redirected to the
     * login page (web) with a message explaining why access is denied.
     *
     * Logout is intentionally left reachable so a blocked user can still end
     * their session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($messageKey = $user->status->blockMessageKey()) !== null) {
            $message = __($messageKey);

            $this->logout($request);

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }

    /**
     * Tear down the current session or access token so the block takes effect
     * immediately rather than only on the next login.
     */
    protected function logout(Request $request): void
    {
        // Real bearer token (API/mobile) — revoke just the token used here.
        if ($request->user()->currentAccessToken() instanceof PersonalAccessToken) {
            $request->user()->currentAccessToken()->delete();

            return;
        }

        // Session (web) client — invalidate the session entirely.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
