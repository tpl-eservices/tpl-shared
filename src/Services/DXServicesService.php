<?php

namespace Tpl\Shared\Services;

use Illuminate\Support\Facades\Log;
use Tpl\Shared\Services\Concerns\MakesHttpRequests;

/**
 * DXServices API service for catalog and customer operations.
 *
 * This service wraps the DXServices API and provides methods for:
 * - Fetching catalog bib data (fallback for BiblioCommons)
 * - Customer membership status lookup
 * - Placing stacks requests
 * - Generic action permission checking
 */
class DXServicesService
{
    use MakesHttpRequests;

    private const CHILD_ACCOUNT_TYPE = 'CHCHILD';

    /**
     * Get common headers for DXServices API requests.
     *
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return [
            'x-api-key' => config('services.dxservices.api_key'),
        ];
    }

    /**
     * Get catalog bib data from DXServices.
     *
     * @return array{resource: string, key: string, fields: array{title: string, callList?: array<int, array<string, mixed>>}}|null
     */
    public function getCatalogBib(string $titleId): ?array
    {
        try {
            $response = $this->httpClient($this->getHeaders())
                ->get(config('services.dxservices.api_url').'/api-gateway/tplws/api/v2/catalog-service/catalog/bib/'.$titleId);

            if ($response->failed()) {
                Log::info('DXServices getCatalogBib failed', [
                    'titleId' => $titleId,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{data: array{resource: string, key: string, fields: array{title: string, callList?: array<int, array<string, mixed>>}}} $json */
            $json = $response->json();

            return $json['data'];
        } catch (\Exception $e) {
            Log::error('DXServices getCatalogBib exception', [
                'titleId' => $titleId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get membership status for a customer by barcode.
     *
     * Returns validation flags for action eligibility:
     * - validRenew: Card needs renewal (blocks actions)
     * - isEcard: Is an e-card account (blocks actions)
     * - isChild: Is a child account (blocks actions)
     *
     * @return array{validRenew: bool, isEcard: bool, isChild: bool}
     */
    public function getMembershipStatus(?string $barcode): array
    {
        $default = ['validRenew' => false, 'isEcard' => false, 'isChild' => false];

        if ($barcode === null || $barcode === '') {
            return $default;
        }

        try {
            $response = $this->httpClient($this->getHeaders())
                ->get(config('services.dxservices.customer_service_url').'/users/barcode/'.$barcode);

            if ($response->failed()) {
                Log::warning('DXServices getMembershipStatus failed', [
                    'barcode' => $barcode,
                    'status' => $response->status(),
                ]);

                return $default;
            }

            /** @var array{data: array{membershipInfo?: array{validRenew?: bool, accountType?: string}, ecard?: bool}} $json */
            $json = $response->json();
            $userData = $json['data'];

            return [
                'validRenew' => $userData['membershipInfo']['validRenew'] ?? false,
                'isEcard' => $userData['ecard'] ?? false,
                'isChild' => ($userData['membershipInfo']['accountType'] ?? '') === self::CHILD_ACCOUNT_TYPE,
            ];
        } catch (\Exception $e) {
            Log::warning('DXServices getMembershipStatus exception', [
                'barcode' => $barcode,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    /**
     * Check if a user can perform a specific action based on their membership status.
     *
     * This method provides generic action permission checking that can be used for
     * stacks requests, holds, renewals, or other restricted library actions.
     *
     * @param  string|null  $barcode  The user's library barcode
     * @param  string  $action  The action being checked (e.g., 'stacks request', 'place hold', 'renew item')
     * @return array{allowed: bool, reason: string|null, message: string|null}
     */
    public function canPerformAction(?string $barcode, string $action = 'perform this action'): array
    {
        $status = $this->getMembershipStatus($barcode);

        if ($status['validRenew']) {
            return [
                'allowed' => false,
                'reason' => 'renewal_required',
                'message' => "Your library card is valid for renewal. Please renew your membership at https://membership.tpl.ca before attempting to {$action}.",
            ];
        }

        if ($status['isEcard']) {
            return [
                'allowed' => false,
                'reason' => 'ecard',
                'message' => 'This action is not currently available for your membership. Please visit your local branch or contact us for assistance.',
            ];
        }

        if ($status['isChild']) {
            return [
                'allowed' => false,
                'reason' => 'child_account',
                'message' => "This action is not available for children's accounts. Please visit your local branch or contact us for assistance.",
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'message' => null,
        ];
    }

    /**
     * Check if a user can place stacks requests (legacy method for backward compatibility).
     *
     * @deprecated Use canPerformAction($barcode, 'place a stacks request') instead
     *
     * @return array{allowed: bool, reason: string|null, message: string|null}
     */
    public function canPlaceStacksRequest(?string $barcode): array
    {
        return $this->canPerformAction($barcode, 'place a stacks request');
    }

    /**
     * Get full customer data by barcode.
     *
     * @return array<string, mixed>|null
     */
    public function getCustomerByBarcode(string $barcode): ?array
    {
        if (empty($barcode)) {
            return null;
        }

        try {
            $response = $this->httpClient($this->getHeaders())
                ->get(config('services.dxservices.customer_service_url').'/users/barcode/'.$barcode);

            if ($response->failed()) {
                Log::info('DXServices getCustomerByBarcode failed', [
                    'barcode' => $barcode,
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array{data: array<string, mixed>} $json */
            $json = $response->json();

            return $json['data'];
        } catch (\Exception $e) {
            Log::error('DXServices getCustomerByBarcode exception', [
                'barcode' => $barcode,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Place a stacks request.
     *
     * @param  array{patronBarcode: string, ckey: string, entryData1: string, entryData2?: string}  $request
     * @return array{success: bool, error?: bool, message?: string, errorCode?: string, errorMessage?: string, errorMessageDetail?: string}
     */
    public function placeStackRequest(array $request): array
    {
        try {
            $response = $this->httpClient($this->getHeaders())
                ->asJson()
                ->post(
                    config('services.dxservices.api_url').'/api-gateway/tplws/api/v2/catalog-service/stack/place',
                    $request
                );

            if ($response->failed()) {
                Log::error('DXServices placeStackRequest failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                /** @var array{errorCode?: string, errorMessage?: string, errorMessageDetail?: string, cause?: string|null} $errorBody */
                $errorBody = $response->json() ?? [];

                return [
                    'success' => false,
                    'error' => true,
                    'errorCode' => $errorBody['errorCode'] ?? 'UNKNOWN',
                    'errorMessage' => $errorBody['errorMessage'] ?? 'An error occurred while processing your request.',
                    'errorMessageDetail' => $errorBody['errorMessageDetail'] ?? '',
                ];
            }

            return [
                'success' => true,
                'error' => false,
                'message' => 'Stack request placed successfully',
            ];
        } catch (\Exception $e) {
            Log::error('DXServices placeStackRequest exception', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => true,
                'errorMessage' => 'An unexpected error occurred.',
            ];
        }
    }
}
