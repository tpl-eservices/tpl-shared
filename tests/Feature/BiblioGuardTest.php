<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tpl\Shared\Auth\BiblioGuard;
use Tpl\Shared\Services\BiblioSsoService;

beforeEach(function (): void {
    config([
        'services.bibliocommons.api_base_url' => 'https://api.bibliocommons.com',
        'services.bibliocommons.api_key' => 'test-api-key',
        'services.bibliocommons.library_id' => 'tpl',
    ]);

    // Clear cookies
    foreach (array_keys($_COOKIE) as $key) {
        unset($_COOKIE[$key]);
    }
});

afterEach(function (): void {
    foreach (array_keys($_COOKIE) as $key) {
        unset($_COOKIE[$key]);
    }
});

it('returns null when no session cookie exists', function (): void {
    $provider = Mockery::mock(UserProvider::class);
    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->user())->toBeNull();
});

it('returns null when session validation fails', function (): void {
    $_COOKIE['bc_session'] = 'invalid-session';

    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([], 401),
    ]);

    $provider = Mockery::mock(UserProvider::class);
    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->user())->toBeNull();
});

it('returns user when session is valid', function (): void {
    $_COOKIE['bc_session'] = 'valid-session';

    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'session' => [
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
    ]);

    $mockUser = Mockery::mock(Authenticatable::class);

    $provider = Mockery::mock(UserProvider::class);
    $provider->shouldReceive('retrieveById')
        ->with('123456')
        ->andReturn($mockUser);

    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->user())->toBe($mockUser);
});

it('caches user after first retrieval', function (): void {
    $_COOKIE['bc_session'] = 'valid-session';

    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'session' => [
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
    ]);

    $mockUser = Mockery::mock(Authenticatable::class);

    $provider = Mockery::mock(UserProvider::class);
    // Should only be called once due to caching
    $provider->shouldReceive('retrieveById')
        ->once()
        ->with('123456')
        ->andReturn($mockUser);

    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    // Call user() twice
    $guard->user();
    $guard->user();
});

it('validates credentials with valid session', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'session' => [
                'borrowers' => ['tpl' => '123456'],
            ],
        ], 200),
    ]);

    $mockUser = Mockery::mock(Authenticatable::class);

    $provider = Mockery::mock(UserProvider::class);
    $provider->shouldReceive('retrieveById')
        ->with('123456')
        ->andReturn($mockUser);

    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->validate(['biblio_session_id' => 'valid-session']))->toBeTrue();
});

it('fails validation with empty credentials', function (): void {
    $provider = Mockery::mock(UserProvider::class);
    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->validate([]))->toBeFalse();
    expect($guard->validate(['biblio_session_id' => '']))->toBeFalse();
});

it('fails validation when session is invalid', function (): void {
    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([], 401),
    ]);

    $provider = Mockery::mock(UserProvider::class);
    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->validate(['biblio_session_id' => 'invalid-session']))->toBeFalse();
});

it('can set and get cookie name', function (): void {
    $provider = Mockery::mock(UserProvider::class);
    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);

    expect($guard->getCookieName())->toBe('bc_session');

    $guard->setCookieName('custom_cookie');

    expect($guard->getCookieName())->toBe('custom_cookie');
});

it('uses custom cookie name for session lookup', function (): void {
    $_COOKIE['my_custom_session'] = 'custom-session-id';

    Http::fake([
        'api.bibliocommons.com/v1/sessions/*' => Http::response([
            'session' => [
                'borrowers' => ['tpl' => '999'],
            ],
        ], 200),
    ]);

    $mockUser = Mockery::mock(Authenticatable::class);

    $provider = Mockery::mock(UserProvider::class);
    $provider->shouldReceive('retrieveById')
        ->with('999')
        ->andReturn($mockUser);

    $request = Request::create('/');
    $biblioSso = app(BiblioSsoService::class);

    $guard = new BiblioGuard($provider, $request, $biblioSso);
    $guard->setCookieName('my_custom_session');

    expect($guard->user())->toBe($mockUser);
});
