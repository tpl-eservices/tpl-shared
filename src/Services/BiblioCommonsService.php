<?php

namespace Tpl\Shared\Services;

use Illuminate\Support\Facades\Log;
use Tpl\Shared\Services\Concerns\MakesHttpRequests;

/**
 * BiblioCommons API service for fetching title and availability data.
 *
 * This service wraps the BiblioCommons API and provides methods for:
 * - Fetching title details
 * - Searching titles
 * - Fetching copy/location information
 * - Checking stacks eligibility
 * - Fetching library and location/branch information
 */
class BiblioCommonsService
{
    use MakesHttpRequests;

    private const STACKS_LOCATION_ID = 'TRLS';

    /**
     * Location IDs that represent stacks/storage areas rather than public branches.
     *
     * @var array<int, string>
     */
    public const STACKS_LOCATION_IDS = [
        'TRLS',   // TRL Stacks
        'SACDS',  // Arthur Conan Doyle Stacks
        'SARS',   // Special Art Room Stacks
        'SBRS',   // Special Baldwin Room Stacks
    ];

    /**
     * Location IDs that represent services rather than physical branches.
     *
     * Note: HLS (Home Library Service) and Bookmobile (BKONE) are intentionally excluded
     * from this list so they appear in branch listings.
     *
     * @var array<int, string>
     */
    public const SERVICE_LOCATION_IDS = [
        // 'HLS',    // Home Library Service - removed to include in branches
        // 'BKONE',  // Bookmobile - removed to include in branches
        'IBBY',   // IBBY (duplicate of North York Central)
    ];

    /**
     * Supported locales for API responses.
     *
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['en-CA', 'en-US', 'es-ES', 'fr-CA', 'ru-RU', 'zh-CN'];

    private const STACKS_AVAILABLE_STATUS = 'AVAILABLE_BY_REQUEST';

    private const STACKS_COLLECTION_IDENTIFIER = 'Stacks Request';

    /**
     * Format IDs that indicate serial/periodical publications.
     *
     * @var array<int, string>
     */
    private const SERIAL_FORMAT_IDS = ['MAG', 'NEWSPAPER', 'EJ'];

    /**
     * Get full title details from BiblioCommons.
     *
     * @return array{id: string, title: array<string, mixed>, source: string}|null
     */
    public function getTitle(string $titleId): ?array
    {
        try {
            $response = $this->httpClient()->get(
                config('services.bibliocommons.api_url').'/titles/'.$titleId,
                [
                    'api_key' => config('services.bibliocommons.titles_api_key'),
                    'library' => config('services.bibliocommons.library_id', 'tpl'),
                ]
            );

            if ($response->failed()) {
                Log::info('BiblioCommons getTitle failed', [
                    'titleId' => $titleId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{id: string, title: array<string, mixed>} $data */
            $data = $response->json();
            $data['source'] = 'bibliocommons';

            return $data;
        } catch (\Exception $e) {
            Log::error('BiblioCommons getTitle exception', [
                'titleId' => $titleId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search titles in BiblioCommons.
     *
     * @param  array{limit?: int, page?: int, search_type?: string, metadata?: bool}  $options
     * @return array{titles: array<int, array<string, mixed>>, pagination?: array<string, mixed>}|null
     */
    public function searchTitles(string $query, array $options = []): ?array
    {
        try {
            $params = [
                'api_key' => config('services.bibliocommons.titles_api_key'),
                'library' => config('services.bibliocommons.library_id', 'tpl'),
                'q' => $query,
                'limit' => $options['limit'] ?? 10,
                'page' => $options['page'] ?? 1,
            ];

            if (isset($options['search_type'])) {
                $params['search_type'] = $options['search_type'];
            }

            if (isset($options['metadata']) && $options['metadata']) {
                $params['include_metadata'] = 'true';
            }

            $response = $this->httpClient()->get(
                config('services.bibliocommons.api_url').'/titles',
                $params
            );

            if ($response->failed()) {
                Log::info('BiblioCommons searchTitles failed', [
                    'query' => $query,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{titles: array<int, array<string, mixed>>, pagination?: array<string, mixed>} */
            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons searchTitles exception', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get copy/location information for a title.
     *
     * @return array{copies: array<int, array{location: array{id: string, name: string}, status: array{id: string, name: string}, collection?: string}>}|null
     */
    public function getCopies(string $titleId): ?array
    {
        try {
            $response = $this->httpClient()->get(
                config('services.bibliocommons.api_url').'/titles/'.$titleId.'/copies',
                [
                    'api_key' => config('services.bibliocommons.titles_api_key'),
                    'library' => config('services.bibliocommons.library_id', 'tpl'),
                ]
            );

            if ($response->failed()) {
                Log::info('BiblioCommons getCopies failed', [
                    'titleId' => $titleId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{copies: array<int, array{location: array{id: string, name: string}, status: array{id: string, name: string}, collection?: string}>} */
            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons getCopies exception', [
                'titleId' => $titleId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if a title is eligible for stacks request.
     *
     * A title is stacks-eligible if it has at least one copy at TRL Stacks (TRLS)
     * with status AVAILABLE_BY_REQUEST.
     */
    public function isStacksEligible(string $titleId): bool
    {
        return $this->getStacksCopy($titleId) !== null;
    }

    /**
     * Get the stacks-eligible copy for a title, if any exists.
     *
     * A copy is stacks-eligible if it:
     * - Is at TRLS location
     * - Has AVAILABLE_BY_REQUEST status
     * - Has a collection containing "Stacks Request" (not open shelf items)
     *
     * @return array{location: array{id: string, name: string}, status: array{id: string, name: string}, collection?: string}|null
     */
    public function getStacksCopy(string $titleId): ?array
    {
        $copiesData = $this->getCopies($titleId);

        if ($copiesData === null || empty($copiesData['copies'])) {
            return null;
        }

        foreach ($copiesData['copies'] as $copy) {
            $locationId = $copy['location']['id'];
            $statusId = $copy['status']['id'];
            $collection = $copy['collection'] ?? '';

            if ($locationId === self::STACKS_LOCATION_ID
            && str_contains($statusId, self::STACKS_AVAILABLE_STATUS)
            && str_contains($collection, self::STACKS_COLLECTION_IDENTIFIER)) {
                return $copy;
            }
        }

        return null;
    }

    /**
     * Get detailed stacks eligibility status for a title.
     *
     * Returns structured information about why an item is or isn't eligible,
     * allowing the UI to show specific messages to users.
     *
     * @return array{eligible: bool, reason?: string, status?: string, statusName?: string, message: string}
     */
    public function getStacksEligibilityStatus(string $titleId): array
    {
        $copiesData = $this->getCopies($titleId);

        // No copies data at all (API error or invalid item)
        if ($copiesData === null || empty($copiesData['copies'])) {
            return [
                'eligible' => false,
                'reason' => 'no_copies',
                'message' => 'Unable to retrieve availability information for this item.',
            ];
        }

        // Look for TRLS copies, prioritizing ones with "Stacks Request" collection
        $stacksCopy = null;
        $openShelfCopy = null;

        foreach ($copiesData['copies'] as $copy) {
            if ($copy['location']['id'] !== self::STACKS_LOCATION_ID) {
                continue;
            }

            $collection = $copy['collection'] ?? '';
            if (str_contains($collection, self::STACKS_COLLECTION_IDENTIFIER)) {
                $stacksCopy = $copy;
                break; // Found a real stacks copy, stop searching
            } else {
                // TRLS location but not in closed stacks (e.g., open shelf)
                $openShelfCopy = $openShelfCopy ?? $copy;
            }
        }

        // No closed stacks copy found (either no TRLS copy, or only open shelf)
        if ($stacksCopy === null) {
            return [
                'eligible' => false,
                'reason' => 'not_in_stacks',
                'message' => 'This item is not held in the closed stacks at Toronto Reference Library.',
            ];
        }

        // We have a real closed stacks copy - check its status
        $statusId = $stacksCopy['status']['id'];
        $statusName = $stacksCopy['status']['name'];

        // Stacks copy is available by request
        if (str_contains($statusId, self::STACKS_AVAILABLE_STATUS)) {
            return [
                'eligible' => true,
                'message' => 'This item is available for stacks request.',
            ];
        }

        // Stacks copy exists but has a different status
        $message = match (true) {
            str_contains($statusId, 'CHECKED_OUT'), str_contains($statusId, 'DUE') => 'This item is currently checked out.',
            str_contains($statusId, 'IN_TRANSIT') => 'This item is currently in transit.',
            str_contains($statusId, 'ON_HOLDSHELF') => 'This item is currently on hold for another patron.',
            str_contains($statusId, 'MISSING') => 'This item is currently reported as missing.',
            str_contains($statusId, 'LOST') => 'This item is currently reported as lost.',
            str_contains($statusId, 'IN_REPAIR'), str_contains($statusId, 'MENDING') => 'This item is currently being repaired.',
            str_contains($statusId, 'IN_PROCESS') => 'This item is currently being processed and not yet available.',
            default => "This item is not currently available (status: {$statusName}).",
        };

        return [
            'eligible' => false,
            'reason' => 'unavailable',
            'status' => $statusId,
            'statusName' => $statusName,
            'message' => $message,
        ];
    }

    /**
     * Determine if a title is a serial/periodical publication.
     *
     * Serials include magazines, newspapers, and journals that are published
     * periodically with volume/issue numbers. Users requesting serials from
     * the stacks should specify which volume/issue they need.
     *
     * Detection is based on:
     * - Format ID (MAG, NEWSPAPER, EJ)
     * - Physical description containing "volumes" (plural)
     * - Call number containing "SERIAL"
     *
     * @param  array<string, mixed>  $title  Title data from getTitle() response
     */
    public function isSerial(array $title): bool
    {
        // Check format ID
        $formatId = $title['format']['id'] ?? '';
        if (in_array($formatId, self::SERIAL_FORMAT_IDS, true)) {
            return true;
        }

        // Check physical_description for "volumes" (plural indicates serial)
        $descriptions = $title['physical_description'] ?? [];
        foreach ($descriptions as $description) {
            if (is_string($description) && str_contains(strtolower($description), 'volumes')) {
                return true;
            }
        }

        // Check call number for "SERIAL"
        $callNumber = $title['call_number'] ?? '';
        if (str_contains(strtoupper($callNumber), 'SERIAL')) {
            return true;
        }

        return false;
    }

    /**
     * Get library information.
     *
     * @param  string|null  $libraryId  Library ID (defaults to configured library)
     * @return array{library: array{id: string, name: string, abbrev: string, catalog_url: string}}|null
     */
    public function getLibrary(?string $libraryId = null): ?array
    {
        $libraryId ??= config('services.bibliocommons.library_id', 'tpl');

        try {
            $response = $this->httpClient()->get(
                config('services.bibliocommons.api_url').'/libraries/'.$libraryId,
                [
                    'api_key' => config('services.bibliocommons.titles_api_key'),
                ]
            );

            if ($response->failed()) {
                Log::info('BiblioCommons getLibrary failed', [
                    'libraryId' => $libraryId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{library: array{id: string, name: string, abbrev: string, catalog_url: string}} */
            return $response->json();
        } catch (\Exception $e) {
            Log::error('BiblioCommons getLibrary exception', [
                'libraryId' => $libraryId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get all locations/branches for a library with optional filtering.
     *
     * The BiblioCommons API only supports locale as a server-side parameter.
     * All other filtering is performed client-side after fetching all locations.
     *
     * @param  array{
     *     locale?: string,
     *     ids?: array<int, string>,
     *     excludeIds?: array<int, string>,
     *     nameContains?: string,
     *     idPattern?: string,
     *     namePattern?: string,
     *     excludeStacks?: bool,
     *     excludeServices?: bool,
     *     excludeSpecialCollections?: bool,
     *     branchesOnly?: bool,
     * }  $options  Filtering options
     * @param  string|null  $libraryId  Library ID (defaults to configured library)
     * @return array{locations: array<int, array{id: string, name: string}>}|null
     */
    public function getLocations(array $options = [], ?string $libraryId = null): ?array
    {
        $libraryId ??= config('services.bibliocommons.library_id', 'tpl');

        try {
            $params = [
                'api_key' => config('services.bibliocommons.titles_api_key'),
            ];

            // Add locale if specified and valid
            if (isset($options['locale']) && in_array($options['locale'], self::SUPPORTED_LOCALES, true)) {
                $params['locale'] = $options['locale'];
            }

            $response = $this->httpClient()->get(
                config('services.bibliocommons.api_url').'/libraries/'.$libraryId.'/locations',
                $params
            );

            if ($response->failed()) {
                Log::info('BiblioCommons getLocations failed', [
                    'libraryId' => $libraryId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{locations: array<int, array{id: string, name: string}>} $data */
            $data = $response->json();

            // Merge additional branches (before filtering)
            $data['locations'] = $this->mergeAdditionalBranches($data['locations']);

            // Apply client-side filters
            $data['locations'] = $this->filterLocations($data['locations'], $options);

            return $data;
        } catch (\Exception $e) {
            Log::error('BiblioCommons getLocations exception', [
                'libraryId' => $libraryId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get a single location by ID.
     *
     * @param  string|null  $libraryId  Library ID (defaults to configured library)
     * @return array{id: string, name: string}|null
     */
    public function getLocation(string $locationId, ?string $libraryId = null): ?array
    {
        $result = $this->getLocations(['ids' => [$locationId]], $libraryId);

        if ($result === null || empty($result['locations'])) {
            return null;
        }

        return $result['locations'][0];
    }

    /**
     * Get only physical branch locations (excluding stacks, services, and special collections).
     *
     * This is a convenience method that filters out:
     * - Stacks locations (TRLS, SACDS, SARS, SBRS)
     * - Service locations (HLS, IBBY) - Note: HLS and Bookmobile are now included in branches
     * - Special Collections locations
     *
     * @param  array{
     *     locale?: string,
     *     ids?: array<int, string>,
     *     excludeIds?: array<int, string>,
     *     nameContains?: string,
     *     idPattern?: string,
     *     namePattern?: string,
     * }  $options  Additional filtering options
     * @param  string|null  $libraryId  Library ID (defaults to configured library)
     * @return array{locations: array<int, array{id: string, name: string}>}|null
     */
    public function getBranches(array $options = [], ?string $libraryId = null): ?array
    {
        $options['branchesOnly'] = true;
        $options['excludeSpecialCollections'] = true;

        return $this->getLocations($options, $libraryId);
    }

    /**
     * Get only stacks locations.
     *
     * @param  string|null  $libraryId  Library ID (defaults to configured library)
     * @return array{locations: array<int, array{id: string, name: string}>}|null
     */
    public function getStacksLocations(?string $libraryId = null): ?array
    {
        return $this->getLocations(['ids' => self::STACKS_LOCATION_IDS], $libraryId);
    }

    /**
     * Determine the type of a location by its ID.
     *
     * @return string One of: 'Stacks', 'Service', or 'Branch'
     */
    public function getLocationType(string $locationId): string
    {
        if (in_array($locationId, self::STACKS_LOCATION_IDS, true)) {
            return 'Stacks';
        }

        if (in_array($locationId, self::SERVICE_LOCATION_IDS, true)) {
            return 'Service';
        }

        return 'Branch';
    }

    /**
     * Merge additional branches from configuration into the locations list.
     *
     * @param  array<int, array{id: string, name: string}>  $locations
     * @return array<int, array{id: string, name: string}>
     */
    private function mergeAdditionalBranches(array $locations): array
    {
        $additionalBranches = config('services.bibliocommons.additional_branches', []);

        if (empty($additionalBranches)) {
            return $locations;
        }

        $existingIds = array_column($locations, 'id');
        $mergedLocations = $locations;

        foreach ($additionalBranches as $branch) {
            // Only add if not already present in the API response
            if (! in_array($branch['id'], $existingIds, true)) {
                $mergedLocations[] = [
                    'id' => $branch['id'],
                    'name' => $branch['name'],
                ];
            }
        }

        return $mergedLocations;
    }

    /**
     * Apply client-side filters to locations array.
     *
     * @param  array<int, array{id: string, name: string}>  $locations
     * @param  array{
     *     ids?: array<int, string>,
     *     excludeIds?: array<int, string>,
     *     nameContains?: string,
     *     idPattern?: string,
     *     namePattern?: string,
     *     excludeStacks?: bool,
     *     excludeServices?: bool,
     *     excludeSpecialCollections?: bool,
     *     branchesOnly?: bool,
     * }  $options
     * @return array<int, array{id: string, name: string}>
     */
    private function filterLocations(array $locations, array $options): array
    {
        // branchesOnly is a convenience flag that sets excludeStacks and excludeServices
        if ($options['branchesOnly'] ?? false) {
            $options['excludeStacks'] = true;
            $options['excludeServices'] = true;
        }

        return array_values(array_filter($locations, function (array $location) use ($options): bool {
            $id = $location['id'];
            $name = $location['name'];

            // Filter by specific IDs (whitelist)
            if (isset($options['ids']) && ! in_array($id, $options['ids'], true)) {
                return false;
            }

            // Exclude specific IDs (blacklist)
            if (isset($options['excludeIds']) && in_array($id, $options['excludeIds'], true)) {
                return false;
            }

            // Exclude stacks locations
            if (($options['excludeStacks'] ?? false) && in_array($id, self::STACKS_LOCATION_IDS, true)) {
                return false;
            }

            // Exclude service locations
            if (($options['excludeServices'] ?? false) && in_array($id, self::SERVICE_LOCATION_IDS, true)) {
                return false;
            }

            // Exclude special collections locations
            if (($options['excludeSpecialCollections'] ?? false) && (
                str_contains($name, 'Special Collections') ||
                str_contains($name, 'Special Art Room Stacks') ||
                str_contains($name, 'Special Baldwin Room Stacks')
            )) {
                return false;
            }

            // Filter by name substring (case-insensitive)
            if (isset($options['nameContains']) && ! str_contains(strtolower($name), strtolower($options['nameContains']))) {
                return false;
            }

            // Filter by ID regex pattern
            if (isset($options['idPattern']) && ! preg_match($options['idPattern'], $id)) {
                return false;
            }

            // Filter by name regex pattern
            if (isset($options['namePattern']) && ! preg_match($options['namePattern'], $name)) {
                return false;
            }

            return true;
        }));
    }
}
