<?php

use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\BiblioCommonsService;

beforeEach(function (): void {
    config([
        'services.bibliocommons.api_url' => 'https://api.bibliocommons.com',
        'services.bibliocommons.titles_api_key' => 'test-api-key',
        'services.bibliocommons.library_id' => 'tpl',
    ]);
});

it('gets title details successfully', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345*' => Http::response([
            'id' => '12345',
            'title' => [
                'id' => '12345',
                'name' => 'Test Book',
                'format' => ['id' => 'BOOK'],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getTitle('12345');

    expect($result)->toBeArray()
        ->and($result['id'])->toBe('12345')
        ->and($result['source'])->toBe('bibliocommons')
        ->and($result['title']['name'])->toBe('Test Book');
});

it('returns null when getting title fails', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/invalid*' => Http::response([], 404),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getTitle('invalid');

    expect($result)->toBeNull();
});

it('searches titles successfully', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles*' => Http::response([
            'titles' => [
                [
                    'id' => '12345',
                    'title' => 'Test Book 1',
                ],
                [
                    'id' => '67890',
                    'title' => 'Test Book 2',
                ],
            ],
            'pagination' => [
                'page' => 1,
                'limit' => 10,
                'total' => 2,
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->searchTitles('test');

    expect($result)->toBeArray()
        ->and($result['titles'])->toHaveCount(2)
        ->and($result['titles'][0]['title'])->toBe('Test Book 1')
        ->and($result['pagination']['total'])->toBe(2);
});

it('returns null when search fails', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles*' => Http::response([], 500),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->searchTitles('test');

    expect($result)->toBeNull();
});

it('gets copies successfully', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRL', 'name' => 'Toronto Reference Library'],
                    'status' => ['id' => 'AVAILABLE', 'name' => 'Available'],
                    'collection' => 'Main Collection',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getCopies('12345');

    expect($result)->toBeArray()
        ->and($result['copies'])->toHaveCount(1)
        ->and($result['copies'][0]['location']['id'])->toBe('TRL')
        ->and($result['copies'][0]['status']['name'])->toBe('Available');
});

it('returns null when getting copies fails', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/invalid/copies*' => Http::response([], 404),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getCopies('invalid');

    expect($result)->toBeNull();
});

it('checks stacks eligibility correctly', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRLS', 'name' => 'TRL Stacks'],
                    'status' => ['id' => 'AVAILABLE_BY_REQUEST', 'name' => 'Available by Request'],
                    'collection' => 'Stacks Request',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->isStacksEligible('12345');

    expect($result)->toBeTrue();
});

it('returns false when not stacks eligible', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRL', 'name' => 'Toronto Reference Library'],
                    'status' => ['id' => 'CHECKED_OUT', 'name' => 'Checked Out'],
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->isStacksEligible('12345');

    expect($result)->toBeFalse();
});

it('gets stacks copy when available', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRLS', 'name' => 'TRL Stacks'],
                    'status' => ['id' => 'AVAILABLE_BY_REQUEST', 'name' => 'Available by Request'],
                    'collection' => 'Stacks Request',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getStacksCopy('12345');

    expect($result)->toBeArray()
        ->and($result['location']['id'])->toBe('TRLS')
        ->and($result['status']['id'])->toBe('AVAILABLE_BY_REQUEST')
        ->and($result['collection'])->toBe('Stacks Request');
});

it('returns null when no stacks copy available', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRL', 'name' => 'Toronto Reference Library'],
                    'status' => ['id' => 'AVAILABLE', 'name' => 'Available'],
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getStacksCopy('12345');

    expect($result)->toBeNull();
});

it('gets detailed stacks eligibility status when eligible', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRLS', 'name' => 'TRL Stacks'],
                    'status' => ['id' => 'AVAILABLE_BY_REQUEST', 'name' => 'Available by Request'],
                    'collection' => 'Stacks Request',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getStacksEligibilityStatus('12345');

    expect($result)->toBeArray()
        ->and($result['eligible'])->toBeTrue()
        ->and($result['message'])->toBe('This item is available for stacks request.');
});

it('gets detailed stacks eligibility status when not in stacks', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/titles/12345/copies*' => Http::response([
            'copies' => [
                [
                    'location' => ['id' => 'TRL', 'name' => 'Toronto Reference Library'],
                    'status' => ['id' => 'AVAILABLE', 'name' => 'Available'],
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getStacksEligibilityStatus('12345');

    expect($result)->toBeArray()
        ->and($result['eligible'])->toBeFalse()
        ->and($result['reason'])->toBe('not_in_stacks')
        ->and($result['message'])->toBe('This item is not held in the closed stacks at Toronto Reference Library.');
});

it('identifies serial correctly by format', function (): void {
    $service = app(BiblioCommonsService::class);

    $title = [
        'format' => ['id' => 'MAG'],
    ];

    expect($service->isSerial($title))->toBeTrue();
});

it('identifies serial correctly by physical description', function (): void {
    $service = app(BiblioCommonsService::class);

    $title = [
        'format' => ['id' => 'BOOK'],
        'physical_description' => ['volumes 1-10'],
    ];

    expect($service->isSerial($title))->toBeTrue();
});

it('identifies serial correctly by call number', function (): void {
    $service = app(BiblioCommonsService::class);

    $title = [
        'format' => ['id' => 'BOOK'],
        'physical_description' => ['300 pages'],
        'call_number' => 'SERIAL ABC123',
    ];

    expect($service->isSerial($title))->toBeTrue();
});

it('identifies non-serial correctly', function (): void {
    $service = app(BiblioCommonsService::class);

    $title = [
        'format' => ['id' => 'BOOK'],
        'physical_description' => ['300 pages'],
        'call_number' => 'ABC123',
    ];

    expect($service->isSerial($title))->toBeFalse();
});

it('gets library information successfully', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/libraries/tpl*' => Http::response([
            'library' => [
                'id' => 'tpl',
                'name' => 'Toronto Public Library',
                'abbrev' => 'TPL',
                'catalog_url' => 'https://www.torontopubliclibrary.ca',
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getLibrary();

    expect($result)->toBeArray()
        ->and($result['library']['id'])->toBe('tpl')
        ->and($result['library']['name'])->toBe('Toronto Public Library');
});

it('returns null when getting library fails', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/libraries/invalid*' => Http::response([], 404),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getLibrary('invalid');

    expect($result)->toBeNull();
});

it('gets locations successfully', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/libraries/tpl/locations*' => Http::response([
            'locations' => [
                [
                    'id' => 'TRL',
                    'name' => 'Toronto Reference Library',
                ],
                [
                    'id' => 'NCL',
                    'name' => 'North York Central Library',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getLocations();

    expect($result)->toBeArray()
        ->and($result['locations'])->toHaveCount(2)
        ->and($result['locations'][0]['id'])->toBe('TRL')
        ->and($result['locations'][1]['name'])->toBe('North York Central Library');
});

it('filters locations by name', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/libraries/tpl/locations*' => Http::response([
            'locations' => [
                [
                    'id' => 'TRL',
                    'name' => 'Toronto Reference Library',
                ],
                [
                    'id' => 'NCL',
                    'name' => 'North York Central Library',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getLocations(['nameContains' => 'Reference']);

    expect($result)->toBeArray()
        ->and($result['locations'])->toHaveCount(1)
        ->and($result['locations'][0]['id'])->toBe('TRL');
});

it('gets branches only', function (): void {
    Http::fake([
        'https://api.bibliocommons.com/libraries/tpl/locations*' => Http::response([
            'locations' => [
                [
                    'id' => 'TRL',
                    'name' => 'Toronto Reference Library',
                ],
                [
                    'id' => 'TRLS',
                    'name' => 'TRL Stacks',
                ],
                [
                    'id' => 'HLS',
                    'name' => 'Home Library Service',
                ],
                [
                    'id' => 'BKONE',
                    'name' => 'Bookmobile',
                ],
            ],
        ], 200),
    ]);

    $service = app(BiblioCommonsService::class);
    $result = $service->getBranches();

    expect($result)->toBeArray()
        ->and($result['locations'])->toHaveCount(3) // HLS and Bookmobile now included in branches
        ->and($result['locations'][0]['id'])->toBe('TRL')
        ->and($result['locations'][1]['id'])->toBe('HLS')
        ->and($result['locations'][2]['id'])->toBe('BKONE');
});

it('determines location type correctly', function (): void {
    $service = app(BiblioCommonsService::class);

    expect($service->getLocationType('TRLS'))->toBe('Stacks');
    expect($service->getLocationType('HLS'))->toBe('Branch'); // HLS now treated as branch
    expect($service->getLocationType('BKONE'))->toBe('Branch'); // Bookmobile now treated as branch
    expect($service->getLocationType('TRL'))->toBe('Branch');
});

it('uses configuration values correctly', function (): void {
    config([
        'services.bibliocommons.api_url' => 'https://custom-api.example.com',
        'services.bibliocommons.titles_api_key' => 'custom-key',
        'services.bibliocommons.library_id' => 'custom-library',
    ]);

    Http::fake();

    $service = app(BiblioCommonsService::class);
    $service->getTitle('12345');

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'custom-api.example.com') &&
               str_contains($request->url(), 'api_key=custom-key') &&
               str_contains($request->url(), 'library=custom-library');
    });
});
