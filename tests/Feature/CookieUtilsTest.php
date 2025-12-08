<?php

use Illuminate\Http\Request;
use Tpl\Shared\Utils\CookieUtils;

it('gets raw cookie from $_COOKIE superglobal', function (): void {
    $_COOKIE['test_cookie'] = 'test_value';

    $request = Request::create('/');
    $result = CookieUtils::getRaw('test_cookie', $request);

    expect($result)->toBe('test_value');

    unset($_COOKIE['test_cookie']);
});

it('gets raw cookie from cookie header', function (): void {
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => 'test_cookie=header_value; another_cookie=another_value',
    ]);

    $result = CookieUtils::getRaw('test_cookie', $request);

    expect($result)->toBe('header_value');
});

it('returns null when cookie does not exist', function (): void {
    $request = Request::create('/');
    $result = CookieUtils::getRaw('nonexistent_cookie', $request);

    expect($result)->toBeNull();
});

it('prefers $_COOKIE over header', function (): void {
    $_COOKIE['test_cookie'] = 'superglobal_value';

    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => 'test_cookie=header_value',
    ]);

    $result = CookieUtils::getRaw('test_cookie', $request);

    expect($result)->toBe('superglobal_value');

    unset($_COOKIE['test_cookie']);
});

it('checks if raw cookie exists', function (): void {
    $_COOKIE['existing_cookie'] = 'value';

    $request = Request::create('/');

    expect(CookieUtils::hasRaw('existing_cookie', $request))->toBeTrue()
        ->and(CookieUtils::hasRaw('nonexistent_cookie', $request))->toBeFalse();

    unset($_COOKIE['existing_cookie']);
});

it('gets multiple raw cookies at once', function (): void {
    $_COOKIE['cookie1'] = 'value1';
    $_COOKIE['cookie2'] = 'value2';

    $request = Request::create('/');
    $result = CookieUtils::getRawMany(['cookie1', 'cookie2', 'cookie3'], $request);

    expect($result)->toBe([
        'cookie1' => 'value1',
        'cookie2' => 'value2',
        'cookie3' => null,
    ]);

    unset($_COOKIE['cookie1'], $_COOKIE['cookie2']);
});

it('handles cookies with special characters', function (): void {
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => 'special_cookie=value%20with%20spaces; normal=test',
    ]);

    $result = CookieUtils::getRaw('special_cookie', $request);

    // parse_str automatically decodes URL-encoded values
    expect($result)->toBe('value with spaces');
});

it('works with global helper function', function (): void {
    $_COOKIE['helper_cookie'] = 'helper_value';

    $result = getRawCookie('helper_cookie');

    expect($result)->toBe('helper_value');

    unset($_COOKIE['helper_cookie']);
});

it('global helper accepts explicit request', function (): void {
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => 'explicit_cookie=explicit_value',
    ]);

    $result = getRawCookie('explicit_cookie', $request);

    expect($result)->toBe('explicit_value');
});

it('handles empty cookie header', function (): void {
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_COOKIE' => '',
    ]);

    $result = CookieUtils::getRaw('any_cookie', $request);

    expect($result)->toBeNull();
});
