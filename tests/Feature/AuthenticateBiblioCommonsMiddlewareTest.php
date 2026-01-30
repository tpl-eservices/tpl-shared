<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tpl\Shared\Http\Middleware\AuthenticateBiblioCommons;

beforeEach(function (): void {
    config([
        'services.bibliocommons.library_id' => 'tpl',
        'auth.guards.biblio.session_cookie' => 'bc_session',
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

it('redirects to login when no session cookie exists', function (): void {
    $request = Request::create('/protected-page');
    $middleware = new AuthenticateBiblioCommons;

    // Mock the Auth guard to return not authenticated
    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false);

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))
        ->toContain('tpl.bibliocommons.com/user/login')
        ->toContain(urlencode('/protected-page'));
});

it('allows request when user is already authenticated', function (): void {
    $request = Request::create('/protected-page');
    $middleware = new AuthenticateBiblioCommons;

    // Mock the Auth guard to return authenticated
    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(true);

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows request when session cookie is valid and user authenticates', function (): void {
    $_COOKIE['bc_session'] = 'valid-session';

    $request = Request::create('/protected-page');
    $middleware = new AuthenticateBiblioCommons;

    $mockUser = Mockery::mock(Authenticatable::class);
    $mockUser->shouldReceive('getAuthIdentifier')->andReturn('12345');

    // Mock the Auth guard sequence
    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false); // Not already authenticated
    Auth::shouldReceive('user')
        ->andReturn($mockUser); // But authentication succeeds

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('redirects to login when session cookie exists but authentication fails', function (): void {
    $_COOKIE['bc_session'] = 'invalid-session';

    $request = Request::create('/protected-page');
    $middleware = new AuthenticateBiblioCommons;

    // Mock the Auth guard to return failed authentication
    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false);
    Auth::shouldReceive('user')
        ->andReturn(null); // Authentication fails

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->headers->get('Location'))
        ->toContain('tpl.bibliocommons.com/user/login');
});

it('uses configured library id in redirect url', function (): void {
    config(['services.bibliocommons.library_id' => 'nypl']);

    $request = Request::create('/my-page');
    $middleware = new AuthenticateBiblioCommons;

    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false);

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->headers->get('Location'))
        ->toContain('nypl.bibliocommons.com/user/login');
});

it('includes full url as destination in redirect', function (): void {
    $request = Request::create('https://example.com/page?foo=bar&baz=qux');
    $middleware = new AuthenticateBiblioCommons;

    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false);

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    $location = $response->headers->get('Location');
    expect($location)->toContain('destination=');
    // The full URL should be encoded in the destination param
    expect($location)->toContain(urlencode('https://example.com/page'));
});

it('uses custom session cookie name from config', function (): void {
    config(['auth.guards.biblio.session_cookie' => 'my_custom_cookie']);
    $_COOKIE['my_custom_cookie'] = 'session-id';

    $request = Request::create('/protected-page');
    $middleware = new AuthenticateBiblioCommons;

    $mockUser = Mockery::mock(Authenticatable::class);
    $mockUser->shouldReceive('getAuthIdentifier')->andReturn('12345');

    Auth::shouldReceive('guard')
        ->with('biblio')
        ->andReturnSelf();
    Auth::shouldReceive('check')
        ->andReturn(false);
    Auth::shouldReceive('user')
        ->andReturn($mockUser);

    $response = $middleware->handle($request, fn ($req) => response('OK'));

    expect($response->getContent())->toBe('OK');
});
