<?php

namespace Tpl\Shared\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tpl\Shared\Utils\CookieUtils;

class AuthenticateBiblioCommons
{
    /**
     * Handle an incoming request.
     *
     * Check for BiblioCommons session and authenticate user if valid.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('BiblioCommons authentication middleware invoked');

        // Skip if already authenticated
        if (Auth::guard('biblio')->check()) {
            Log::info('User already authenticated via BiblioCommons guard');

            return $next($request);
        }

        // Check for BiblioCommons session cookie
        $sessionCookieName = config('auth.guards.biblio.session_cookie', 'bc_session');
        $sessionId = CookieUtils::getRaw($sessionCookieName, $request);

        if (! $sessionId) {
            Log::info('No BiblioCommons session cookie found', [
                'cookie_name' => $sessionCookieName,
            ]);

            // No session cookie, redirect to BiblioCommons login
            return $this->redirectToBiblioCommonsLogin($request);
        }

        Log::info('BiblioCommons session cookie found', [
            'cookie_name' => $sessionCookieName,
        ]);

        // Attempt authentication via BiblioCommons guard
        $user = Auth::guard('biblio')->user();

        if ($user) {
            Log::info('User authenticated via BiblioCommons', [
                'user_id' => $user->id,
                'user_name' => $user->name,
            ]);

            // Optionally log into default session guard
            if (config('auth.biblio_auto_login_session', false)) {
                Auth::login($user);
                Log::info('User logged into default session guard');
            }

            return $next($request);
        }

        Log::warning('BiblioCommons authentication failed', [
            'session_cookie' => $sessionCookieName,
        ]);

        // Authentication failed, redirect to BiblioCommons login
        return $this->redirectToBiblioCommonsLogin($request);
    }

    /**
     * Redirect to BiblioCommons login page.
     */
    protected function redirectToBiblioCommonsLogin(Request $request): Response
    {
        $libraryId = config('services.bibliocommons.library_id', 'tpl');
        $currentUrl = urlencode($request->fullUrl());
        $loginUrl = "https://{$libraryId}.bibliocommons.com/user/login?destination={$currentUrl}";

        Log::info('Redirecting to BiblioCommons login', [
            'login_url' => $loginUrl,
        ]);

        return redirect($loginUrl);
    }
}
