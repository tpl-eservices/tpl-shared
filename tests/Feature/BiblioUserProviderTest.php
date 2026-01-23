<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Auth\BiblioUserProvider;
use Tpl\Shared\Services\BiblioSsoService;

beforeEach(function (): void {
    config([
        'services.bibliocommons.api_base_url' => 'https://api.bibliocommons.com',
        'services.bibliocommons.api_key' => 'test-api-key',
        'services.bibliocommons.library_id' => 'tpl',
    ]);
});

it('retrieves user by borrower id', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'borrower' => [
                'id' => '123456',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'barcode' => '29999012345678',
            ],
        ], 200),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('123456');

    expect($user)->toBeInstanceOf(Authenticatable::class)
        ->and((string) $user->getAttribute('id'))->toBe('123456')
        ->and($user->getAttribute('name'))->toBe('John Doe')
        ->and($user->getAttribute('email'))->toBe('john@example.com')
        ->and($user->getAttribute('barcode'))->toBe('29999012345678');
});

it('returns null when borrower not found', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([], 404),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('invalid-id');

    expect($user)->toBeNull();
});

it('returns null when response missing borrower data', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'error' => 'Not found',
        ], 200),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('123456');

    expect($user)->toBeNull();
});

it('handles user with name field instead of first/last name', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'borrower' => [
                'id' => '123456',
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
        ], 200),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('123456');

    expect($user->getAttribute('name'))->toBe('Jane Smith');
});

it('uses default name when no name data provided', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'borrower' => [
                'id' => '123456',
            ],
        ], 200),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('123456');

    expect($user->getAttribute('name'))->toBe('BiblioCommons User');
});

it('marks user as existing to prevent save attempts', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/libraries/*/borrowers/*' => Http::response([
            'borrower' => [
                'id' => '123456',
                'name' => 'Test User',
            ],
        ], 200),
    ]);

    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $user = $provider->retrieveById('123456');

    // The exists property prevents Eloquent from trying to INSERT
    expect($user->exists)->toBeTrue();
});

it('returns null for retrieveByToken (not supported)', function (): void {
    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    expect($provider->retrieveByToken('123', 'token'))->toBeNull();
});

it('returns null for retrieveByCredentials (not supported)', function (): void {
    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    expect($provider->retrieveByCredentials(['email' => 'test@test.com']))->toBeNull();
});

it('always validates credentials as true (validation is external)', function (): void {
    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $mockUser = Mockery::mock(Authenticatable::class);

    expect($provider->validateCredentials($mockUser, []))->toBeTrue();
});

it('updateRememberToken does nothing (no database storage)', function (): void {
    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $mockUser = Mockery::mock(Authenticatable::class);

    // Should not throw - just does nothing
    $provider->updateRememberToken($mockUser, 'token');

    expect(true)->toBeTrue(); // Test passes if no exception
});

it('rehashPasswordIfRequired does nothing (no passwords for SSO)', function (): void {
    $biblioSso = app(BiblioSsoService::class);
    $provider = new BiblioUserProvider($biblioSso, AuthenticatableUser::class);

    $mockUser = Mockery::mock(Authenticatable::class);

    // Should not throw - just does nothing
    $provider->rehashPasswordIfRequired($mockUser, [], false);

    expect(true)->toBeTrue(); // Test passes if no exception
});
