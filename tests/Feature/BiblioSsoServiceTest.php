<?php

use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\BiblioSsoService;

beforeEach(function (): void {
    config([
        'services.bibliocommons.api_base_url' => 'https://api.bibliocommons.com',
        'services.bibliocommons.api_key' => 'test-api-key',
        'services.bibliocommons.library_id' => 'tpl',
    ]);
});

it('validates a session successfully', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'user' => [
                'id' => '2412321',
                'name' => 'exampleuser',
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->validateSession('test-session-id');

    expect($result)->toBeArray()
        ->and($result['user']['id'])->toBe('2412321')
        ->and($result['user']['name'])->toBe('exampleuser');
});

it('returns null when session validation fails', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([], 401),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->validateSession('invalid-session-id');

    expect($result)->toBeNull();
});

it('fetches borrower info successfully', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => true,
            'borrower' => [
                'id' => '123456',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ], 200),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->fetchBorrowerInfo('123456');

    expect($result)->toBeArray()
        ->and($result['successful'])->toBeTrue()
        ->and($result['borrower']['id'])->toBe('123456');
});

it('returns null when fetching borrower info fails', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([], 404),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->fetchBorrowerInfo('invalid-id');

    expect($result)->toBeNull();
});

it('returns the response even when borrower info has error field', function (): void {
    // The service returns whatever JSON the API returns - it doesn't check internal fields
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => false,
            'error' => 'Borrower not found',
        ], 200),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->fetchBorrowerInfo('invalid-id');

    // Service returns the JSON as-is since HTTP was 200
    expect($result)->toBeArray()
        ->and($result['successful'])->toBeFalse();
});

it('fetches user profile successfully', function (): void {
    // The service expects session.borrowers structure (not user.borrowers)
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'session' => [
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'borrower' => [
                'id' => '123456',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ], 200),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->fetchUserProfile('test-session-id');

    expect($result)->toBeArray()
        ->and($result['borrower']['name'])->toBe('John Doe');
});

it('returns null when user profile session validation fails', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([], 401),
    ]);

    $service = app(BiblioSsoService::class);
    $result = $service->fetchUserProfile('invalid-session-id');

    expect($result)->toBeNull();
});

it('uses configuration values correctly', function (): void {
    config([
        'services.bibliocommons.api_base_url' => 'https://custom-api.example.com',
        'services.bibliocommons.api_key' => 'custom-key',
        'services.bibliocommons.library_id' => 'custom-library',
    ]);

    Http::fake();

    $service = new BiblioSsoService;
    $service->validateSession('test-id');

    Http::assertSent(function ($request): bool {
        return str_contains($request->url(), 'custom-api.example.com') &&
               str_contains($request->url(), 'api_key=custom-key');
    });
});
