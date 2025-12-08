<?php

namespace Tpl\Shared\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Tpl\Shared\Utils\CookieUtils;

class BiblioGuard implements Guard
{
    use GuardHelpers;

    protected Request $request;

    protected string $cookieName = 'biblioSession';

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
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

        // Retrieve user by BiblioCommons session
        return $this->user = $this->provider->retrieveByCredentials([
            'biblio_session_id' => $sessionId,
        ]);
    }

    /**
     * Validate a user's credentials.
     */
    public function validate(array $credentials = []): bool
    {
        if (empty($credentials['biblio_session_id'])) {
            return false;
        }

        return $this->provider->retrieveByCredentials($credentials) !== null;
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
