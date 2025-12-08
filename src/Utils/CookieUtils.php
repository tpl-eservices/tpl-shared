<?php

namespace Tpl\Shared\Utils;

use Illuminate\Http\Request;

class CookieUtils
{
    /**
     * Get raw cookie value bypassing Laravel's encryption/decryption.
     *
     * This is useful for reading cookies set by external systems (like BiblioCommons)
     * that are not encrypted by Laravel.
     */
    public static function getRaw(string $name, Request $request): ?string
    {
        // First, try to get from $_COOKIE superglobal
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }

        // Fallback: parse raw cookie header manually
        $cookieHeader = $request->header('Cookie');
        if ($cookieHeader) {
            $cookies = [];
            parse_str(str_replace('; ', '&', $cookieHeader), $cookies);

            return $cookies[$name] ?? null;
        }

        return null;
    }

    /**
     * Check if a raw cookie exists.
     */
    public static function hasRaw(string $name, Request $request): bool
    {
        return self::getRaw($name, $request) !== null;
    }

    /**
     * Get multiple raw cookies at once.
     *
     * @param  array<string>  $names
     * @return array<string, string|null>
     */
    public static function getRawMany(array $names, Request $request): array
    {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = self::getRaw($name, $request);
        }

        return $result;
    }
}
