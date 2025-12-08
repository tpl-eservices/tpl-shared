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
            // Correct endpoint: /v1/libraries/{library_id}/sessions/{session_id}
            $url = "{$this->biblioApiBaseUrl}/v1/libraries/{$this->libraryId}/sessions/{$sessionId}";

            $response = Http::timeout(5)
                ->retry(2, 500, throw: false)
                ->withoutVerifying() // Disable SSL verification for local development
                ->get($url, [
                    'api_key' => $this->apiKey,
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
     */
    public function fetchUserProfile(string $sessionId): ?array
    {
        // Validate session to get user info
        $borrower = $this->validateSession($sessionId);

        // Example response structure (commented for reference):
        // $borrower = [
        //     "user" => [
        //       "id" => "2412321",
        //       "name" => "exampleuser",
        //       "borrowers" => ["examplepl" => "123456"]
        //     ]
        // ];

        if (! $borrower || ! isset($borrower['user']['id'])) {
            return null;
        }

        $borrowerId = $borrower['user']['id']; // or $borrower['user']['borrowers']['examplepl'];

        return $this->fetchBorrowerInfo($borrowerId);
    }
}
