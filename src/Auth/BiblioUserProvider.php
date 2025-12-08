<?php

namespace Tpl\Shared\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Tpl\Shared\Services\BiblioSsoService;

class BiblioUserProvider implements UserProvider
{
    protected string $apiUrl;

    protected string $apiKey;

    protected string $libraryId;

    public function __construct(
        protected BiblioSsoService $biblioSso,
        protected string $model
    ) {
        $this->apiUrl = config('services.bibliocommons.api_base_url', 'https://api.bibliocommons.com');
        $this->apiKey = config('services.bibliocommons.api_key');
        $this->libraryId = config('services.bibliocommons.library_id', 'tpl');
    }

    /**
     * Retrieve a user by their unique identifier (borrower ID).
     *
     * Fetches fresh data from BiblioCommons API each time.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        // $identifier is the BiblioCommons borrower ID
        // Fetch fresh data from API
        $borrowerInfo = $this->biblioSso->fetchBorrowerInfo($identifier);

        if (! $borrowerInfo || ! isset($borrowerInfo['borrower'])) {
            return null;
        }

        return $this->createUserFromApiData($borrowerInfo['borrower']);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * Not used for SSO - session management is external.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    /**
     * Update the "remember me" token for the given user in storage.
     *
     * No database storage - session management is external.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        // No database storage, do nothing
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * Not used since we don't store users in database.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        return null;
    }

    /**
     * Validate a user against the given credentials.
     *
     * For BiblioCommons SSO, validation is handled by BiblioCommons.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // Validation is handled by BiblioCommons, not by Laravel
        return true;
    }

    /**
     * Rehash the user's password if required and supported.
     *
     * Not applicable - no passwords for SSO users.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // No passwords for SSO
    }

    /**
     * Create a User model instance from BiblioCommons API data.
     *
     * Creates a transient user object (not persisted to database).
     */
    protected function createUserFromApiData(array $data): Authenticatable
    {
        $class = '\\'.ltrim($this->model, '\\');
        $user = new $class;

        // Map BiblioCommons borrower data to User model
        $user->id = $data['id'];
        $user->name = isset($data['first_name'], $data['last_name'])
            ? trim($data['first_name'].' '.$data['last_name'])
            : ($data['name'] ?? 'BiblioCommons User');
        $user->email = $data['email'] ?? '';
        $user->password = ''; // No password for SSO users
        $user->email_verified_at = now(); // Assume verified through BiblioCommons

        // Mark as existing to prevent save attempts
        $user->exists = true;

        return $user;
    }
}
