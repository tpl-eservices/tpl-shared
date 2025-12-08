<?php

namespace Tpl\Shared\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Tpl\Shared\Services\BiblioSsoService;

class BiblioUserProvider implements UserProvider
{
    public function __construct(
        protected BiblioSsoService $biblioSso,
        protected string $model
    ) {}

    /**
     * Retrieve a user by their unique identifier.
     */
    public function retrieveById($identifier): ?Authenticatable
    {
        return $this->createModel()->newQuery()->find($identifier);
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     */
    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $model = $this->createModel();

        return $model->newQuery()
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();
    }

    /**
     * Update the "remember me" token for the given user in storage.
     */
    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $user->setRememberToken($token);
        $user->save();
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * This method validates the BiblioCommons session and fetches user data.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (! isset($credentials['biblio_session_id'])) {
            return null;
        }

        // Fetch user profile from BiblioCommons
        $profile = $this->biblioSso->fetchUserProfile($credentials['biblio_session_id']);

        if (! $profile || ! isset($profile['borrower'])) {
            return null;
        }

        $borrower = $profile['borrower'];

        // Find or create user in local database
        $model = $this->createModel();

        return $model->newQuery()->firstOrCreate(
            ['biblio_id' => $borrower['id']],
            [
                'name' => $borrower['name'] ?? '',
                'email' => $borrower['email'] ?? '',
            ]
        );
    }

    /**
     * Validate a user against the given credentials.
     *
     * For BiblioCommons, validation happens during retrieval.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        // Validation already happened in retrieveByCredentials
        return true;
    }

    /**
     * Rehash the user's password if required and supported.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Not applicable for BiblioCommons SSO
    }

    /**
     * Create a new instance of the model.
     */
    protected function createModel(): Authenticatable
    {
        $class = '\\'.ltrim($this->model, '\\');

        return new $class;
    }
}
