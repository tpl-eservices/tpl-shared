<?php

namespace Tpl\Shared\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BiblioSsoService
{
    protected string $biblioApiBaseUrl;

    protected string $apiKey;

    protected string $libraryId;

    public function __construct()
    {
        $this->biblioApiBaseUrl = config('services.bibliocommons.api_base_url', 'https://api.bibliocommons.com');
        $this->apiKey = config('services.bibliocommons.api_key');
        $this->libraryId = config('services.bibliocommons.library_id', 'tpl');
    }

    /**
     * Validate a BiblioCommons session ID.
     */
    public function validateSession(string $sessionId): ?array
    {
        try {
            // Correct endpoint per documentation: /v1/sessions/{id} (no library_id in path)
            $url = "{$this->biblioApiBaseUrl}/v1/sessions/{$sessionId}";

            Log::info('BiblioCommons: Validating session', [
                'url' => $url,
                'session_id' => $sessionId,
            ]);

            $response = Http::timeout(5)
                ->retry(2, 500, throw: false)
                ->withoutVerifying() // Disable SSL verification for local development
                ->get($url, [
                    'api_key' => $this->apiKey,
                ]);

            Log::info('BiblioCommons: Session API response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if (! $response->successful()) {
                Log::warning('BiblioCommons session validation failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'url' => $url,
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons session validation error', [
                'message' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return null;
        }
    }

    /**
     * Fetch borrower information from BiblioCommons API.
     */
    public function fetchBorrowerInfo(string $borrowerId): ?array
    {
        try {
            $url = "{$this->biblioApiBaseUrl}/v1/libraries/{$this->libraryId}/borrowers/{$borrowerId}";

            $response = Http::timeout(5)
                ->retry(2, 500, throw: false)
                ->withoutVerifying() // Disable SSL verification for local development
                ->get($url, [
                    'api_key' => $this->apiKey,
                ]);

            if (! $response->successful() || ! $response['successful']) {
                Log::warning('BiblioCommons fetching borrower info failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'url' => $url,
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons fetching borrower info error', [
                'message' => $e->getMessage(),
                'borrower_id' => $borrowerId,
            ]);

            return null;
        }
    }

    /**
     * Fetch user profile by validating session and retrieving borrower info.
     *
     * Per documentation:
     * 1. Call /v1/sessions/{id} to get user info and borrowers hash
     * 2. Extract borrower ID from the borrowers hash using library_id
     * 3. Call /v1/libraries/{library_id}/borrowers/{id} to get full borrower info
     */
    public function fetchUserProfile(string $sessionId): ?array
    {
        // Validate session to get user info
        $sessionData = $this->validateSession($sessionId);

        // Sessions API response structure per documentation:
        // {
        //     "user": {
        //         "id": "2412321",
        //         "name": "exampleuser",
        //         "borrowers": {"examplepl": "123456"}
        //     }
        // }

        if (! $sessionData || ! isset($sessionData['user']['borrowers'][$this->libraryId])) {
            Log::warning('BiblioCommons: No borrower ID found for library', [
                'library_id' => $this->libraryId,
                'session_data' => $sessionData,
            ]);

            return null;
        }

        // Get borrower ID from the borrowers hash using our library_id
        $borrowerId = $sessionData['user']['borrowers'][$this->libraryId];

        return $this->fetchBorrowerInfo($borrowerId);
    }
}
