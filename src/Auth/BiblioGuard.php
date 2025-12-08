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

    protected string $cookieName = 'biblioSession';

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

        // BiblioCommons API returns: { "session": { "borrower": { "id": "..." } } }
        if (! $sessionData || ! isset($sessionData['session']['borrower']['id'])) {
            return null;
        }

        // Retrieve user by borrower ID from BiblioCommons API
        $borrowerId = $sessionData['session']['borrower']['id'];

        return $this->user = $this->provider->retrieveById($borrowerId);
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['biblio_session_id'])) {
            return false;
        }

        // Validate session with BiblioCommons
        $sessionData = $this->biblioSso->validateSession($credentials['biblio_session_id']);

        $libraryId = config('services.bibliocommons.library_id', 'tpl');

        if (! $sessionData || ! isset($sessionData['user']['borrowers'][$libraryId])) {
            return false;
        }

        // Check if we can retrieve user by borrower ID
        $borrowerId = $sessionData['user']['borrowers'][$libraryId];

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
