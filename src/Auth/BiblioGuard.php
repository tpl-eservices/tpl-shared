<?php

namespace Tpl\Shared\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Tpl\Shared\Services\BiblioSsoService;
use Tpl\Shared\Utils\CookieUtils;

class BiblioGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;

    protected BiblioSsoService $biblioSso;

    protected string $cookieName = 'bc_session';

    public function __construct(
        UserProvider $provider,
        Request $request,
        BiblioSsoService $biblioSso
    ) {
        $this->provider = $provider;
        $this->request = $request;
        $this->biblioSso = $biblioSso;
    }

    /**
     * Get the currently authenticated user.
     */
    public function user()
    {
        // Return if already retrieved
        if (! is_null($this->user)) {
            return $this->user;
        }

        // Get BiblioCommons session from cookie
        $sessionId = CookieUtils::getRaw($this->cookieName, $this->request);

        if (! $sessionId) {
            return null;
        }

        // Validate session and get user data from BiblioCommons
        $sessionData = $this->biblioSso->validateSession($sessionId);

        // Actual BiblioCommons API response: { "session": { "borrowers": {"tpl": "123456"} } }
        $libraryId = config('services.bibliocommons.library_id', 'tpl');

        if (! $sessionData || ! isset($sessionData['session']['borrowers'][$libraryId])) {
            return null;
        }

        // Get borrower ID from the borrowers hash using library_id as key
        $borrowerId = $sessionData['session']['borrowers'][$libraryId];

        return $this->user = $this->provider->retrieveById($borrowerId);
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['biblio_session_id'])) {
            return false;
        }

        // Validate session with BiblioCommons
        $sessionData = $this->biblioSso->validateSession($credentials['biblio_session_id']);

        $libraryId = config('services.bibliocommons.library_id', 'tpl');

        if (! $sessionData || ! isset($sessionData['session']['borrowers'][$libraryId])) {
            return false;
        }

        // Check if we can retrieve user by borrower ID
        $borrowerId = $sessionData['session']['borrowers'][$libraryId];

        return $this->provider->retrieveById($borrowerId) !== null;
    }

    /**
     * Set the BiblioCommons cookie name.
     */
    public function setCookieName(string $name): self
    {
        $this->cookieName = $name;

        return $this;
    }

    /**
     * Get the BiblioCommons cookie name.
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }
}
