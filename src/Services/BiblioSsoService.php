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

            $response = Http::timeout(10)
                ->retry(1, 100, throw: false)
                ->withoutVerifying()
                ->get($url, [
                    'api_key' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('BiblioCommons session validation failed', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons session validation error', [
                'message' => $e->getMessage(),
                'session_id' => substr($sessionId, 0, 20).'...',
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

            $response = Http::timeout(10)
                ->retry(1, 100, throw: false)
                ->withoutVerifying()
                ->get($url, [
                    'api_key' => $this->apiKey,
                ]);

            if (! $response->successful()) {
                Log::warning('BiblioCommons fetching borrower info failed', [
                    'status' => $response->status(),
                    'borrower_id' => $borrowerId,
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
     * Actual API response structure (based on working implementation):
     * {
     *     "session": {
     *         "borrowers": {
     *             "tpl": "123456"
     *         }
     *     }
     * }
     */
    public function fetchUserProfile(string $sessionId): ?array
    {
        // Validate session to get borrowers info
        $sessionData = $this->validateSession($sessionId);

        if (! $sessionData || ! isset($sessionData['session']['borrowers'][$this->libraryId])) {
            Log::warning('BiblioCommons: No borrower ID found for library', [
                'library_id' => $this->libraryId,
                'session_data' => $sessionData,
            ]);

            return null;
        }

        // Get borrower ID from the borrowers hash using our library_id
        $borrowerId = $sessionData['session']['borrowers'][$this->libraryId];

        return $this->fetchBorrowerInfo($borrowerId);
    }

    /**
     * Fetches the library branches from BiblioCommons API.
     */
    private function getLibraryBranches(): array
    {
        // Cache branches for 1 hour (3600 seconds)
        return Cache::remember('library_branches', 3600, function () {
            // Fetch locations from BiblioCommons API
            $response = Http::get(config('services.bibliocommons.api_url').'/libraries/tpl/locations', [
                'api_key' => config('services.bibliocommons.titles_api_key'),
            ]);

            if ($response->failed()) {
                Log::alert('BiblioCommons Locations API failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($response->failed()) {
                    Log::alert('BiblioCommons Locations API failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return [];
                }

                $data = $response->json();

                return $data['locations'] ?? [];
            }

            return [];
        });
    }
}
