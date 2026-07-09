<?php

use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\DXServicesService;

beforeEach(function (): void {
    config([
        'services.dxservices.api_url' => 'https://dxservices.example.com',
        'services.dxservices.customer_service_url' => 'https://dxservices.example.com',
        'services.dxservices.api_key' => 'test-dx-api-key',
    ]);
});

it('gets catalog bib data successfully', function (): void {
    Http::fake([
        'https://dxservices.example.com/api-gateway/tplws/api/v2/catalog-service/catalog/bib/12345*' => Http::response([
            'data' => [
                'resource' => 'bib',
                'key' => '12345',
                'fields' => [
                    'title' => 'Test Book',
                    'callList' => [
                        ['callNumber' => 'ABC123'],
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getCatalogBib('12345');

    expect($result)->toBeArray()
        ->and($result['resource'])->toBe('bib')
        ->and($result['key'])->toBe('12345')
        ->and($result['fields']['title'])->toBe('Test Book');
});

it('returns null when getting catalog bib fails', function (): void {
    Http::fake([
        'https://dxservices.example.com/api-gateway/tplws/api/v2/catalog-service/catalog/bib/invalid*' => Http::response([], 404),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getCatalogBib('invalid');

    expect($result)->toBeNull();
});

it('gets membership status successfully', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'ADULT',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getMembershipStatus('123456789');

    expect($result)->toBeArray()
        ->and($result['validRenew'])->toBeFalse()
        ->and($result['isEcard'])->toBeFalse()
        ->and($result['isChild'])->toBeFalse();
});

it('returns default membership status for null barcode', function (): void {
    $service = app(DXServicesService::class);
    $result = $service->getMembershipStatus(null);

    expect($result)->toBeArray()
        ->and($result['validRenew'])->toBeFalse()
        ->and($result['isEcard'])->toBeFalse()
        ->and($result['isChild'])->toBeFalse();
});

it('returns default membership status for empty barcode', function (): void {
    $service = app(DXServicesService::class);
    $result = $service->getMembershipStatus('');

    expect($result)->toBeArray()
        ->and($result['validRenew'])->toBeFalse()
        ->and($result['isEcard'])->toBeFalse()
        ->and($result['isChild'])->toBeFalse();
});

it('detects child account correctly', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'CHCHILD',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getMembershipStatus('123456789');

    expect($result['isChild'])->toBeTrue();
});

it('detects ecard account correctly', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'ADULT',
                ],
                'ecard' => true,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getMembershipStatus('123456789');

    expect($result['isEcard'])->toBeTrue();
});

it('allows action for valid membership', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'ADULT',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->canPerformAction('123456789', 'place a hold');

    expect($result)->toBeArray()
        ->and($result['allowed'])->toBeTrue()
        ->and($result['reason'])->toBeNull()
        ->and($result['message'])->toBeNull();
});

it('blocks action for renewal required', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => true,
                    'accountType' => 'ADULT',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->canPerformAction('123456789', 'place a stacks request');

    expect($result)->toBeArray()
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('renewal_required')
        ->and($result['message'])->toContain('Your library card is valid for renewal')
        ->and($result['message'])->toContain('before attempting to place a stacks request');
});

it('blocks action for ecard account', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'ADULT',
                ],
                'ecard' => true,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->canPerformAction('123456789', 'request this item');

    expect($result)->toBeArray()
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('ecard')
        ->and($result['message'])->toContain('This action is not currently available for your membership');
});

it('blocks action for child account', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'CHCHILD',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->canPerformAction('123456789', 'borrow this material');

    expect($result)->toBeArray()
        ->and($result['allowed'])->toBeFalse()
        ->and($result['reason'])->toBe('child_account')
        ->and($result['message'])->toContain('This action is not available for children\'s accounts');
});

it('maintains backward compatibility with canPlaceStacksRequest', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'membershipInfo' => [
                    'validRenew' => false,
                    'accountType' => 'ADULT',
                ],
                'ecard' => false,
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->canPlaceStacksRequest('123456789');

    expect($result)->toBeArray()
        ->and($result['allowed'])->toBeTrue()
        ->and($result['message'])->toBeNull();
});

it('gets customer data successfully', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/123456789*' => Http::response([
            'data' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'email' => 'john@example.com',
                'barcode' => '123456789',
            ],
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getCustomerByBarcode('123456789');

    expect($result)->toBeArray()
        ->and($result['firstName'])->toBe('John')
        ->and($result['lastName'])->toBe('Doe')
        ->and($result['barcode'])->toBe('123456789');
});

it('returns null when getting customer fails', function (): void {
    Http::fake([
        'https://dxservices.example.com/users/barcode/invalid*' => Http::response([], 404),
    ]);

    $service = app(DXServicesService::class);
    $result = $service->getCustomerByBarcode('invalid');

    expect($result)->toBeNull();
});

it('returns null for empty barcode in getCustomerByBarcode', function (): void {
    $service = app(DXServicesService::class);
    $result = $service->getCustomerByBarcode('');

    expect($result)->toBeNull();
});

it('places stack request successfully', function (): void {
    Http::fake([
        'https://dxservices.example.com/api-gateway/tplws/api/v2/catalog-service/stack/place*' => Http::response([
            'success' => true,
        ], 200),
    ]);

    $service = app(DXServicesService::class);
    $request = [
        'patronBarcode' => '123456789',
        'ckey' => '12345',
        'entryData1' => 'Volume 1',
    ];

    $result = $service->placeStackRequest($request);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeTrue()
        ->and($result['error'])->toBeFalse()
        ->and($result['message'])->toBe('Stack request placed successfully');
});

it('handles stack request failure with error details', function (): void {
    Http::fake([
        'https://dxservices.example.com/api-gateway/tplws/api/v2/catalog-service/stack/place*' => Http::response([
            'errorCode' => 'ITEM_NOT_FOUND',
            'errorMessage' => 'The requested item could not be found',
            'errorMessageDetail' => 'Title with ckey 99999 does not exist in catalog',
        ], 400),
    ]);

    $service = app(DXServicesService::class);
    $request = [
        'patronBarcode' => '123456789',
        'ckey' => '99999',
        'entryData1' => 'Volume 1',
    ];

    $result = $service->placeStackRequest($request);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeFalse()
        ->and($result['error'])->toBeTrue()
        ->and($result['errorCode'])->toBe('ITEM_NOT_FOUND')
        ->and($result['errorMessage'])->toBe('The requested item could not be found')
        ->and($result['errorMessageDetail'])->toBe('Title with ckey 99999 does not exist in catalog');
});

it('handles stack request with HTTP failure', function (): void {
    Http::fake([
        'https://dxservices.example.com/api-gateway/tplws/api/v2/catalog-service/stack/place*' => Http::response([], 500),
    ]);

    $service = app(DXServicesService::class);
    $request = [
        'patronBarcode' => '123456789',
        'ckey' => '12345',
        'entryData1' => 'Volume 1',
    ];

    $result = $service->placeStackRequest($request);

    expect($result)->toBeArray()
        ->and($result['success'])->toBeFalse()
        ->and($result['error'])->toBeTrue()
        ->and($result['errorCode'])->toBe('UNKNOWN');
});

it('uses configuration values correctly', function (): void {
    config([
        'services.dxservices.api_url' => 'https://custom-dx.example.com',
        'services.dxservices.api_key' => 'custom-key',
    ]);

    Http::fake();

    $service = app(DXServicesService::class);
    $service->getCatalogBib('12345');

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'custom-dx.example.com') &&
               str_contains($request->url(), 'catalog-service/catalog/bib/12345');
    });
});

it('uses correct API headers', function (): void {
    Http::fake();

    $service = app(DXServicesService::class);
    $service->getCustomerByBarcode('123456789');

    Http::assertSent(function ($request): bool {
        return $request->hasHeader('x-api-key') &&
               $request->header('x-api-key')[0] === 'test-dx-api-key';
    });
});
