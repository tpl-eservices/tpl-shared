<?php

namespace Tpl\Shared\Services;

use Illuminate\Support\Facades\Log;

/**
 * Fake BiblioCommons SSO service for local development and testing.
 *
 * This service bypasses actual BiblioCommons API calls and returns mock data.
 * It is used when BIBLIOCOMMONS_MOCK_ENABLED=true in local/testing environments.
 */
class FakeBiblioSsoService extends BiblioSsoService
{
    /**
     * Validate a BiblioCommons session ID.
     *
     * @return array<string, mixed>|null
     */
    public function validateSession(string $sessionId): ?array
    {
        Log::info('[MOCK] BiblioCommons session validation bypassed', [
            'session_id' => substr($sessionId, 0, 10).'...',
        ]);

        $libraryId = config('services.bibliocommons.library_id', 'tpl');
        $mockUserId = config('services.bibliocommons.mock.user_id', '123456');

        return [
            'session' => [
                'borrowers' => [
                    $libraryId => $mockUserId,
                ],
            ],
        ];
    }

    /**
     * Fetch borrower information from mock data.
     *
     * @return array<string, mixed>|null
     */
    public function fetchBorrowerInfo(string $borrowerId): ?array
    {
        Log::info('[MOCK] BiblioCommons borrower info bypassed - returning mock user', [
            'borrower_id' => $borrowerId,
        ]);

        return [
            'borrower' => $this->getMockBorrowerData(),
        ];
    }

    /**
     * Fetch user profile using mock data.
     *
     * @return array<string, mixed>|null
     */
    public function fetchUserProfile(string $sessionId): ?array
    {
        Log::info('[MOCK] BiblioCommons user profile bypassed - returning mock user');

        return [
            'borrower' => $this->getMockBorrowerData(),
        ];
    }

    /**
     * Get mock borrower data from configuration.
     *
     * @return array<string, mixed>
     */
    protected function getMockBorrowerData(): array
    {
        return [
            'id' => config('services.bibliocommons.mock.user_id', '123456'),
            'first_name' => config('services.bibliocommons.mock.first_name', 'Test'),
            'last_name' => config('services.bibliocommons.mock.last_name', 'User'),
            'email' => config('services.bibliocommons.mock.email', 'test@example.com'),
            'phone' => config('services.bibliocommons.mock.phone', '416-123-4567'),
            'barcode' => config('services.bibliocommons.mock.barcode', '21385000000001'),
            'location' => [
                'id' => config('services.bibliocommons.mock.location_id', 'TRL'),
                'name' => config('services.bibliocommons.mock.location_name', 'Toronto Reference Library'),
            ],
        ];
    }
}
