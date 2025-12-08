<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Services\BiblioSsoService;

beforeEach(function (): void {
    // Set required config
    config([
        'services.bibliocommons.api_base_url' => 'https://api.bibliocommons.com',
        'services.bibliocommons.api_key' => 'test-api-key',
        'services.bibliocommons.library_id' => 'tpl',
    ]);

    // Clear all cookies before each test
    foreach (array_keys($_COOKIE) as $key) {
        unset($_COOKIE[$key]);
    }
});

afterEach(function (): void {
    // Clean up cookies after each test
    foreach (array_keys($_COOKIE) as $key) {
        unset($_COOKIE[$key]);
    }
});

it('authenticates user using BiblioCommons session from cookie', function (): void {
    // Simulate BiblioCommons setting a cookie
    $_COOKIE['biblioSession'] = 'test-session-id-from-cookie';

    // Mock the BiblioCommons API responses
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'user' => [
                'id' => '2412321',
                'name' => 'testuser',
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => true,
            'borrower' => [
                'id' => '123456',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ], 200),
    ]);

    // Simulate auth callback that reads cookie and validates session
    $sessionId = getRawCookie('biblioSession');
    expect($sessionId)->toBe('test-session-id-from-cookie');

    $biblioSso = app(BiblioSsoService::class);
    $profile = $biblioSso->fetchUserProfile($sessionId);

    expect($profile)->toBeArray()
        ->and($profile['successful'])->toBeTrue()
        ->and($profile['borrower']['name'])->toBe('John Doe')
        ->and($profile['borrower']['email'])->toBe('john@example.com');
});

it('handles authentication flow with cookie from request header', function (): void {
    // Create request with BiblioCommons cookie in header
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => 'biblioSession=header-session-id; other=value',
    ]);

    // Mock the BiblioCommons API responses
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'user' => [
                'id' => '9876543',
                'name' => 'headeruser',
                'borrowers' => ['tpl' => '654321'],
            ],
        ], 200),
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'successful' => true,
            'borrower' => [
                'id' => '654321',
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
        ], 200),
    ]);

    // Simulate auth callback
    $sessionId = getRawCookie('biblioSession', $request);
    expect($sessionId)->toBe('header-session-id');

    $biblioSso = app(BiblioSsoService::class);
    $profile = $biblioSso->fetchUserProfile($sessionId);

    expect($profile)->toBeArray()
        ->and($profile['borrower']['name'])->toBe('Jane Smith');
});

it('returns null when BiblioCommons cookie is missing', function (): void {
    $request = Request::create('/');
    $sessionId = getRawCookie('biblioSession', $request);

    expect($sessionId)->toBeNull();
});

it('handles failed authentication with valid cookie', function (): void {
    $_COOKIE['biblioSession'] = 'invalid-session-id';

    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([], 401),
    ]);

    $sessionId = getRawCookie('biblioSession');
    $biblioSso = app(BiblioSsoService::class);
    $profile = $biblioSso->fetchUserProfile($sessionId);

    expect($profile)->toBeNull();
});


