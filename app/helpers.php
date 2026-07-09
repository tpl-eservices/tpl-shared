<?php

use Illuminate\Http\Request;
use Tpl\Shared\Utils\CookieUtils;

if (! function_exists('tplSharedAsset')) {
    /**
     * Resolve the hashed asset filename from the shared package manifest.
     * Usage: tplSharedAsset('css'), tplSharedAsset('js'), etc.
     */
    function tplSharedAsset(string $extension = 'css'): ?string
    {
        $manifestPath = public_path('vendor/tpl-shared/build/manifest.json');
        if (! file_exists($manifestPath)) {
            return null;
        }
        $contents = file_get_contents($manifestPath);
        if ($contents === false) {
            return null;
        }
        $manifest = json_decode($contents, true);
        foreach ($manifest as $entry) {
            if (isset($entry['file']) && str_ends_with($entry['file'], '.'.$extension)) {
                return asset('vendor/tpl-shared/build/'.$entry['file']);
            }
        }

        return null;
    }
}

if (! function_exists('getRawCookie')) {
    /**
     * Get raw cookie value bypassing Laravel's encryption/decryption.
     *
     * This is useful for reading cookies set by external systems (like BiblioCommons)
     * that are not encrypted by Laravel.
     */
    function getRawCookie(string $name, ?Request $request = null): ?string
    {
        $request = $request ?? request();

        return CookieUtils::getRaw($name, $request);
    }
}
